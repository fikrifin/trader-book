<?php

namespace App\Services;

use App\Models\Instrument;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

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
}
