<?php

namespace App\Console\Commands;

use App\Services\TwelveDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class TwelveDataHealthReport extends Command
{
    protected $signature = 'instruments:twelvedata-health {--reset : Reset metric counters after report}';

    protected $description = 'Show Twelve Data integration health metrics';

    public function handle(TwelveDataService $twelveDataService): int
    {
        $metrics = $twelveDataService->metrics();

        $this->line('Twelve Data Health Metrics');
        $this->line('--------------------------');
        $this->line('Price Success Count: '.$metrics['price_success_count']);
        $this->line('Price Failed Count: '.$metrics['price_failed_count']);
        $this->line('Last Failed Symbol: '.(string) Cache::get('twelvedata:metrics:last_failed_symbol', '-'));
        $this->line('Last Failed Reason: '.(string) Cache::get('twelvedata:metrics:last_failed_reason', '-'));
        $this->line('Last Failed At: '.(string) Cache::get('twelvedata:metrics:last_failed_at', '-'));

        if ($this->option('reset')) {
            Cache::forget('twelvedata:metrics:price_success_count');
            Cache::forget('twelvedata:metrics:price_failed_count');
            Cache::forget('twelvedata:metrics:last_failed_symbol');
            Cache::forget('twelvedata:metrics:last_failed_reason');
            Cache::forget('twelvedata:metrics:last_failed_at');
            $this->info('Metrics berhasil di-reset.');
        }

        return self::SUCCESS;
    }
}
