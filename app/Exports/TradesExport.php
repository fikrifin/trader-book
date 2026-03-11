<?php

namespace App\Exports;

use App\Models\Trade;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TradesExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    public function __construct(protected array $filters = [])
    {
    }

    public function query(): Builder
    {
        return Trade::query()
            ->forUser()
            ->when(data_get($this->filters, 'date_from'), fn ($query, $value) => $query->whereDate('date', '>=', $value))
            ->when(data_get($this->filters, 'date_to'), fn ($query, $value) => $query->whereDate('date', '<=', $value))
            ->when(data_get($this->filters, 'pair'), fn ($query, $value) => $query->where('pair', $value))
            ->when(data_get($this->filters, 'result'), fn ($query, $value) => $query->where('result', $value))
            ->when(data_get($this->filters, 'session'), fn ($query, $value) => $query->where('session', $value))
            ->when(data_get($this->filters, 'direction'), fn ($query, $value) => $query->where('direction', $value))
            ->when(data_get($this->filters, 'account_id'), fn ($query, $value) => $query->where('trading_account_id', $value))
            ->latest('date');
    }

    public function headings(): array
    {
        return [
            'Date',
            'Open Time',
            'Close Time',
            'Pair',
            'Direction',
            'Session',
            'Setup',
            'Timeframe',
            'Entry',
            'SL',
            'TP1',
            'Close',
            'Lot',
            'Risk Amount',
            'Commission',
            'Swap',
            'Result',
            'P/L Gross',
            'P/L Net',
            'RR Planned',
            'RR Actual',
            'Followed Plan',
            'Mistake',
            'Tags',
        ];
    }

    public function map($trade): array
    {
        return [
            $trade->date,
            $trade->open_time,
            $trade->close_time,
            $trade->pair,
            $trade->direction,
            $trade->session,
            $trade->setup,
            $trade->timeframe,
            $trade->entry_price,
            $trade->stop_loss,
            $trade->take_profit_1,
            $trade->close_price,
            $trade->lot_size,
            $trade->risk_amount,
            $trade->commission,
            $trade->swap,
            $trade->result,
            $trade->profit_loss_gross,
            $trade->profit_loss,
            $trade->rr_planned,
            $trade->rr_ratio,
            $trade->followed_plan ? 'Yes' : 'No',
            $trade->mistake,
            is_array($trade->tags) ? implode(', ', $trade->tags) : $trade->tags,
        ];
    }
}
