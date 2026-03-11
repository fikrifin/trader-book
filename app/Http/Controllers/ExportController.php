<?php

namespace App\Http\Controllers;

use App\Exports\TradesExport;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function trades(Request $request): BinaryFileResponse
    {
        $validated = $request->validate([
            'format' => ['nullable', 'in:csv,xlsx'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'pair' => ['nullable', 'string', 'max:50'],
            'result' => ['nullable', 'in:win,loss,breakeven,partial'],
            'session' => ['nullable', 'in:asia,london,new_york,overlap'],
            'direction' => ['nullable', 'in:buy,sell'],
            'account_id' => ['nullable', 'integer'],
        ]);

        $format = data_get($validated, 'format') === 'csv' ? 'csv' : 'xlsx';

        return (new TradesExport($validated))
            ->download('trades-export.'.$format);
    }
}
