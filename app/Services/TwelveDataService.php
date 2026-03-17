<?php

namespace App\Services;

use App\Models\Instrument;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class TwelveDataService
{
    protected const METRIC_SUCCESS = 'twelvedata:metrics:price_success_count';

    protected const METRIC_FAILED = 'twelvedata:metrics:price_failed_count';

    public function isConfigured(): bool
    {
        return filled(config('services.twelvedata.key'));
    }

    public function searchSymbols(string $query, int $limit = 20): array
    {
        $response = $this->requestWithRetry('/symbol_search', [
            'symbol' => $query,
            'outputsize' => max(1, min($limit, 120)),
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gagal mengambil data instrument dari Twelve Data.');
        }

        $payload = $response->json();

        if (isset($payload['status']) && $payload['status'] === 'error') {
            throw new RuntimeException($payload['message'] ?? 'Twelve Data mengembalikan error saat sinkronisasi instrument.');
        }

        return collect($payload['data'] ?? [])->map(function (array $item): array {
            $symbol = (string) ($item['symbol'] ?? '');
            $name = (string) ($item['instrument_name'] ?? $symbol);
            $type = strtolower((string) ($item['instrument_type'] ?? ''));

            return [
                'symbol' => trim($symbol),
                'name' => trim($name),
                'category' => $this->mapCategory($type),
            ];
        })->filter(fn (array $item) => filled($item['symbol']) && filled($item['name']))
            ->values()
            ->all();
    }

    public function getPrice(string $symbol): array
    {
        $cacheKey = 'twelvedata:price:'.md5($symbol);

        return Cache::remember($cacheKey, now()->addSeconds(10), function () use ($symbol) {
            $response = $this->requestWithRetry('/price', [
                'symbol' => $symbol,
            ]);

            if (! $response->successful()) {
                $this->recordPriceFailure($symbol, 'HTTP '.$response->status());

                return [
                    'symbol' => $symbol,
                    'price' => null,
                    'error' => 'Gagal mengambil harga.',
                ];
            }

            $payload = $response->json();

            if (isset($payload['status']) && $payload['status'] === 'error') {
                $this->recordPriceFailure($symbol, (string) ($payload['message'] ?? 'Provider error'));

                return [
                    'symbol' => $symbol,
                    'price' => null,
                    'error' => $payload['message'] ?? 'Harga tidak tersedia.',
                ];
            }

            $this->recordPriceSuccess();

            return [
                'symbol' => $symbol,
                'price' => isset($payload['price']) ? (float) $payload['price'] : null,
                'error' => null,
            ];
        });
    }

    public function refreshInstrumentPrice(Instrument $instrument): array
    {
        $price = $this->getPrice($instrument->symbol);

        if ($price['price'] === null) {
            if ($instrument->last_price !== null) {
                return [
                    'symbol' => $instrument->symbol,
                    'price' => (float) $instrument->last_price,
                    'error' => $price['error'] ?? 'Menggunakan cached price.',
                    'source' => 'cache_fallback',
                    'updated_at' => optional($instrument->price_updated_at)?->toDateTimeString(),
                ];
            }

            return [
                ...$price,
                'updated_at' => now()->toDateTimeString(),
            ];
        }

        $previousPrice = $instrument->last_price !== null ? (float) $instrument->last_price : null;
        $newPrice = (float) $price['price'];

        $priceChangePct = null;
        if ($previousPrice !== null && $previousPrice > 0) {
            $priceChangePct = round((($newPrice - $previousPrice) / $previousPrice) * 100, 4);
        }

        $instrument->update([
            'last_price' => $newPrice,
            'price_change_pct' => $priceChangePct,
            'price_source' => 'twelvedata',
            'price_updated_at' => now(),
        ]);

        return [
            ...$price,
            'source' => 'twelvedata',
            'updated_at' => now()->toDateTimeString(),
        ];
    }

    public function metrics(): array
    {
        return [
            'price_success_count' => (int) Cache::get(self::METRIC_SUCCESS, 0),
            'price_failed_count' => (int) Cache::get(self::METRIC_FAILED, 0),
        ];
    }

    public function inferCategoryFromSymbolName(string $symbol, string $name = ''): string
    {
        $symbolNorm = strtoupper(trim($symbol));
        $nameNorm = strtoupper(trim($name));
        $combined = $symbolNorm.' '.$nameNorm;

        if (str_contains($combined, 'XAU') || str_contains($combined, 'GOLD') || str_contains($combined, 'XAG') || str_contains($combined, 'SILVER')) {
            return 'commodity';
        }

        if (str_contains($combined, 'BTC') || str_contains($combined, 'ETH') || str_contains($combined, 'USDT') || str_contains($combined, 'CRYPTO')) {
            return 'crypto';
        }

        if (preg_match('/^[A-Z]{3}\/[A-Z]{3}$/', $symbolNorm) === 1) {
            return 'forex';
        }

        if (str_contains($combined, 'INDEX') || str_contains($combined, 'NASDAQ') || str_contains($combined, 'SPX') || str_contains($combined, 'DOW')) {
            return 'index';
        }

        return 'stock';
    }

    public function buildAiMarketContext(Instrument $instrument, string $timeframe): array
    {
        $warnings = [];
        $qualityScore = 100;

        $inferredCategory = $this->inferCategoryFromSymbolName($instrument->symbol, $instrument->name);
        $identityConflict = $inferredCategory !== $instrument->category;

        if ($identityConflict) {
            $warnings[] = "Kategori instrument terdeteksi mismatch: {$instrument->category} vs inferred {$inferredCategory}.";
            $qualityScore -= 35;
        }

        if (! $this->isConfigured()) {
            $warnings[] = 'TWELVEDATA_API_KEY belum dikonfigurasi, data market terbatas cache lokal.';

            return [
                'identity' => [
                    'stored_category' => $instrument->category,
                    'inferred_category' => $inferredCategory,
                    'identity_conflict' => $identityConflict,
                ],
                'snapshot' => [
                    'last_price' => $instrument->last_price,
                    'price_change_pct' => $instrument->price_change_pct,
                    'price_updated_at' => optional($instrument->price_updated_at)->toDateTimeString(),
                ],
                'time_series' => null,
                'volatility' => null,
                'context_quality' => [
                    'score' => max(0, $qualityScore - 40),
                    'warnings' => $warnings,
                ],
            ];
        }

        try {
            $interval = $this->mapTimeframeToInterval($timeframe);
            $response = $this->requestWithRetry('/time_series', [
                'symbol' => $instrument->symbol,
                'interval' => $interval,
                'outputsize' => 60,
                'dp' => 6,
            ]);

            if (! $response->successful()) {
                $warnings[] = 'Gagal memuat time series dari provider.';
                $qualityScore -= 30;

                return [
                    'identity' => [
                        'stored_category' => $instrument->category,
                        'inferred_category' => $inferredCategory,
                        'identity_conflict' => $identityConflict,
                    ],
                    'snapshot' => [
                        'last_price' => $instrument->last_price,
                        'price_change_pct' => $instrument->price_change_pct,
                        'price_updated_at' => optional($instrument->price_updated_at)->toDateTimeString(),
                    ],
                    'time_series' => null,
                    'volatility' => null,
                    'context_quality' => [
                        'score' => max(0, $qualityScore),
                        'warnings' => $warnings,
                    ],
                ];
            }

            $payload = $response->json();
            $values = collect($payload['values'] ?? [])->map(function (array $row) {
                return [
                    'datetime' => $row['datetime'] ?? null,
                    'open' => isset($row['open']) ? (float) $row['open'] : null,
                    'high' => isset($row['high']) ? (float) $row['high'] : null,
                    'low' => isset($row['low']) ? (float) $row['low'] : null,
                    'close' => isset($row['close']) ? (float) $row['close'] : null,
                ];
            })->filter(fn (array $row) => $row['close'] !== null)->values();

            if ($values->count() < 20) {
                $warnings[] = 'Data historical kurang dari 20 candle.';
                $qualityScore -= 25;
            }

            $latest = $values->first();
            $recent20 = $values->take(20)->values();
            $recentCloses = $recent20->pluck('close')->filter();
            $firstClose = (float) ($recent20->last()['close'] ?? 0);
            $lastClose = (float) ($recent20->first()['close'] ?? 0);
            $trendPct20 = $firstClose > 0 ? round((($lastClose - $firstClose) / $firstClose) * 100, 4) : null;

            $returns = [];
            for ($i = 0; $i < $recentCloses->count() - 1; $i++) {
                $prev = (float) $recentCloses[$i + 1];
                $curr = (float) $recentCloses[$i];
                if ($prev > 0) {
                    $returns[] = (($curr - $prev) / $prev) * 100;
                }
            }

            $volatilityPct = null;
            if (count($returns) >= 5) {
                $mean = array_sum($returns) / count($returns);
                $variance = array_sum(array_map(fn ($x) => ($x - $mean) ** 2, $returns)) / count($returns);
                $volatilityPct = round(sqrt($variance), 4);
            } else {
                $warnings[] = 'Data return tidak cukup untuk menghitung volatilitas.';
                $qualityScore -= 10;
            }

            return [
                'identity' => [
                    'stored_category' => $instrument->category,
                    'inferred_category' => $inferredCategory,
                    'identity_conflict' => $identityConflict,
                ],
                'snapshot' => [
                    'provider_price' => $latest['close'] ?? null,
                    'provider_time' => $latest['datetime'] ?? null,
                    'last_price_cached' => $instrument->last_price,
                    'price_change_pct_cached' => $instrument->price_change_pct,
                ],
                'time_series' => [
                    'interval' => $interval,
                    'candle_count' => $values->count(),
                    'trend_pct_20' => $trendPct20,
                    'high_20' => $recent20->pluck('high')->filter()->max(),
                    'low_20' => $recent20->pluck('low')->filter()->min(),
                ],
                'volatility' => [
                    'stddev_return_pct_20' => $volatilityPct,
                ],
                'context_quality' => [
                    'score' => max(0, $qualityScore),
                    'warnings' => $warnings,
                ],
            ];
        } catch (Throwable $exception) {
            $warnings[] = 'Gagal membangun market context: '.$exception->getMessage();

            return [
                'identity' => [
                    'stored_category' => $instrument->category,
                    'inferred_category' => $inferredCategory,
                    'identity_conflict' => $identityConflict,
                ],
                'snapshot' => [
                    'last_price' => $instrument->last_price,
                    'price_change_pct' => $instrument->price_change_pct,
                    'price_updated_at' => optional($instrument->price_updated_at)->toDateTimeString(),
                ],
                'time_series' => null,
                'volatility' => null,
                'context_quality' => [
                    'score' => max(0, $qualityScore - 30),
                    'warnings' => $warnings,
                ],
            ];
        }
    }

    protected function client()
    {
        $apiKey = (string) config('services.twelvedata.key');
        $baseUrl = (string) config('services.twelvedata.base_url');

        if (blank($apiKey)) {
            throw new RuntimeException('TWELVEDATA_API_KEY belum diatur.');
        }

        return Http::baseUrl($baseUrl)
            ->acceptJson()
            ->asJson()
            ->timeout(15)
            ->withQueryParameters([
                'apikey' => $apiKey,
            ]);
    }

    protected function requestWithRetry(string $path, array $query): Response
    {
        $attempts = max(1, (int) config('services.twelvedata.retry_times', 3));
        $sleepMs = max(0, (int) config('services.twelvedata.retry_sleep_ms', 300));

        $response = $this->client()->get($path, $query);

        for ($i = 1; $i < $attempts; $i++) {
            if (! $this->shouldRetry($response)) {
                return $response;
            }

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }

            $response = $this->client()->get($path, $query);
        }

        return $response;
    }

    protected function shouldRetry(Response $response): bool
    {
        return in_array($response->status(), [429, 500, 502, 503, 504], true);
    }

    protected function recordPriceSuccess(): void
    {
        Cache::increment(self::METRIC_SUCCESS);
    }

    protected function recordPriceFailure(string $symbol, string $reason): void
    {
        Cache::increment(self::METRIC_FAILED);
        Cache::put('twelvedata:metrics:last_failed_symbol', $symbol, now()->addDay());
        Cache::put('twelvedata:metrics:last_failed_reason', $reason, now()->addDay());
        Cache::put('twelvedata:metrics:last_failed_at', now()->toDateTimeString(), now()->addDay());
    }

    protected function mapCategory(string $type): string
    {
        return match (true) {
            str_contains($type, 'currency') && ! str_contains($type, 'digital') => 'forex',
            str_contains($type, 'digital') || str_contains($type, 'crypto') => 'crypto',
            str_contains($type, 'commodity') => 'commodity',
            str_contains($type, 'index') => 'index',
            default => 'stock',
        };
    }

    protected function mapTimeframeToInterval(string $timeframe): string
    {
        return match (strtoupper(trim($timeframe))) {
            'M1' => '1min',
            'M5' => '5min',
            'M15' => '15min',
            'M30' => '30min',
            'H1' => '1h',
            'H4' => '4h',
            'D1' => '1day',
            default => '1h',
        };
    }
}
