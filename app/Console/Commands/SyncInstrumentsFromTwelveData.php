<?php

namespace App\Console\Commands;

use App\Models\Instrument;
use App\Models\User;
use App\Services\TwelveDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SyncInstrumentsFromTwelveData extends Command
{
    protected $signature = 'instruments:sync-twelvedata
        {--keywords= : Comma-separated keyword list, example: BTC,EUR,XAU}
        {--limit= : Maximum result per keyword}
        {--category= : Optional category filter (forex,commodity,crypto,index,stock)}';

    protected $description = 'Sync default instruments from Twelve Data for all users';

    public function handle(TwelveDataService $twelveDataService): int
    {
        if (! $twelveDataService->isConfigured()) {
            $this->error('TWELVEDATA_API_KEY belum dikonfigurasi.');

            return self::FAILURE;
        }

        $category = $this->option('category');
        if (filled($category) && ! in_array($category, ['forex', 'commodity', 'crypto', 'index', 'stock'], true)) {
            $this->error('Nilai --category tidak valid.');

            return self::FAILURE;
        }

        $limit = (int) ($this->option('limit') ?: config('services.twelvedata.sync_limit', 20));
        $limit = max(1, min($limit, 50));

        $rawKeywords = (string) ($this->option('keywords') ?: config('services.twelvedata.sync_keywords', 'BTC,EUR,XAU'));
        $keywords = collect(explode(',', $rawKeywords))
            ->map(fn (string $item) => trim($item))
            ->filter()
            ->unique()
            ->values();

        if ($keywords->isEmpty()) {
            $this->error('Daftar keyword kosong. Set TWELVEDATA_SYNC_KEYWORDS atau gunakan --keywords.');

            return self::FAILURE;
        }

        $allResults = collect();

        foreach ($keywords as $keyword) {
            try {
                $results = collect($twelveDataService->searchSymbols($keyword, $limit));
            } catch (RuntimeException $exception) {
                $this->warn("Keyword {$keyword} gagal: {$exception->getMessage()}");
                Log::warning('twelvedata.sync.keyword-failed', [
                    'keyword' => $keyword,
                    'message' => $exception->getMessage(),
                ]);
                continue;
            }

            if (filled($category)) {
                $results = $results->where('category', $category);
            }

            $allResults = $allResults->merge($results);
        }

        $symbols = $allResults
            ->filter(fn (array $item) => filled($item['symbol'] ?? null))
            ->unique('symbol')
            ->values();

        if ($symbols->isEmpty()) {
            $this->warn('Tidak ada instrument yang ditemukan dari Twelve Data.');

            return self::SUCCESS;
        }

        $users = User::query()->select('id')->cursor();
        $created = 0;
        $updated = 0;

        foreach ($users as $user) {
            foreach ($symbols as $item) {
                $existing = Instrument::query()->where([
                    'user_id' => $user->id,
                    'symbol' => $item['symbol'],
                ])->first();

                if ($existing) {
                    $existing->update([
                        'name' => $item['name'],
                        'category' => $item['category'],
                        'is_active' => true,
                    ]);
                    $updated++;

                    continue;
                }

                Instrument::query()->create([
                    'user_id' => $user->id,
                    'symbol' => $item['symbol'],
                    'name' => $item['name'],
                    'category' => $item['category'],
                    'is_active' => true,
                ]);
                $created++;
            }
        }

        Log::info('twelvedata.sync.completed', [
            'created' => $created,
            'updated' => $updated,
            'keywords' => $keywords->all(),
            'category' => $category,
            'limit' => $limit,
            'total_symbols' => $symbols->count(),
        ]);

        $this->info("Sinkronisasi selesai. {$created} ditambahkan, {$updated} diperbarui.");

        return self::SUCCESS;
    }
}
