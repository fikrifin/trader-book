<?php

namespace App\Console\Commands;

use App\Models\Instrument;
use App\Services\TwelveDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshInstrumentPricesFromTwelveData extends Command
{
    protected $signature = 'instruments:refresh-prices
        {--user= : Optional user id filter}
        {--limit=0 : Maximum instruments per run (0 = unlimited)}';

    protected $description = 'Refresh cached instrument prices from Twelve Data for active instruments';

    public function handle(TwelveDataService $twelveDataService): int
    {
        if (! $twelveDataService->isConfigured()) {
            $this->error('TWELVEDATA_API_KEY belum dikonfigurasi.');

            return self::FAILURE;
        }

        $query = Instrument::query()
            ->where('is_active', true)
            ->orderBy('id');

        $userId = $this->option('user');
        if (filled($userId)) {
            $query->where('user_id', (int) $userId);
        }

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $query->limit($limit);
        }

        $instruments = $query->get();

        if ($instruments->isEmpty()) {
            $this->warn('Tidak ada instrument aktif untuk di-refresh.');

            return self::SUCCESS;
        }

        $updated = 0;
        $failed = 0;

        foreach ($instruments as $instrument) {
            $result = $twelveDataService->refreshInstrumentPrice($instrument);

            if ($result['price'] === null) {
                $failed++;
                continue;
            }

            $updated++;
        }

        $metrics = $twelveDataService->metrics();
        $failedThreshold = (int) config('services.twelvedata.alert_failed_threshold', 10);

        Log::info('twelvedata.refresh-prices.completed', [
            'updated' => $updated,
            'failed' => $failed,
            'user_filter' => filled($userId) ? (int) $userId : null,
            'limit' => $limit,
            'price_success_count' => $metrics['price_success_count'],
            'price_failed_count' => $metrics['price_failed_count'],
        ]);

        if ($failed >= $failedThreshold) {
            Log::error('twelvedata.refresh-prices.failed-threshold', [
                'failed' => $failed,
                'threshold' => $failedThreshold,
            ]);
        }

        $this->info("Refresh harga selesai. {$updated} sukses, {$failed} gagal.");

        return self::SUCCESS;
    }
}
