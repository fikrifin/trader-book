<?php

namespace App\Console\Commands;

use App\Models\Instrument;
use App\Services\TwelveDataService;
use Illuminate\Console\Command;

class NormalizeInstrumentCategories extends Command
{
    protected $signature = 'instruments:normalize-categories {--dry-run : Tampilkan perubahan tanpa menyimpan}';

    protected $description = 'Normalize instrument categories based on symbol/name inference';

    public function handle(TwelveDataService $twelveDataService): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;

        $instruments = Instrument::query()->orderBy('id')->get();

        foreach ($instruments as $instrument) {
            $inferred = $twelveDataService->inferCategoryFromSymbolName($instrument->symbol, $instrument->name);

            if ($inferred === $instrument->category) {
                continue;
            }

            $this->line("#{$instrument->id} {$instrument->symbol}: {$instrument->category} -> {$inferred}");

            if (! $dryRun) {
                $instrument->update(['category' => $inferred]);
                $updated++;
            }
        }

        if ($dryRun) {
            $this->info('Dry-run selesai. Tidak ada perubahan tersimpan.');
        } else {
            $this->info("Normalisasi selesai. {$updated} instrument diperbarui.");
        }

        return self::SUCCESS;
    }
}