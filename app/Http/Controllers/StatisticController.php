<?php

namespace App\Http\Controllers;

use App\Models\Trade;
use App\Models\TradingAccount;
use App\Services\StatisticService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StatisticController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'date_from' => $request->input('date_from', Carbon::now()->startOfMonth()->toDateString()),
            'date_to' => $request->input('date_to', Carbon::now()->toDateString()),
            'pair' => $request->input('pair'),
            'result' => $request->input('result'),
            'direction' => $request->input('direction'),
            'session' => $request->input('session'),
            'account_id' => $request->input('account_id', auth()->user()?->active_account_id),
        ];

        $trades = Trade::query()
            ->forUser()
            ->when($filters['date_from'], fn ($query, $value) => $query->whereDate('date', '>=', $value))
            ->when($filters['date_to'], fn ($query, $value) => $query->whereDate('date', '<=', $value))
            ->when($filters['pair'], fn ($query, $value) => $query->where('pair', $value))
            ->when($filters['result'], fn ($query, $value) => $query->where('result', $value))
            ->when($filters['direction'], fn ($query, $value) => $query->where('direction', $value))
            ->when($filters['session'], fn ($query, $value) => $query->where('session', $value))
            ->when($filters['account_id'], fn ($query, $value) => $query->where('trading_account_id', $value))
            ->orderBy('date')
            ->get();

        $service = new StatisticService($trades);

        return Inertia::render('Statistics/Index', [
            'filters' => $filters,
            'summary' => $service->summary(),
            'pair_performance' => $service->performanceByPair(),
            'setup_performance' => $service->performanceBySetup(),
            'session_performance' => $service->performanceBySession(),
            'timeframe_performance' => $service->performanceByTimeframe(),
            'day_performance' => $service->performanceByDayOfWeek(),
            'mistake_distribution' => $service->mistakeDistribution(),
            'accounts' => TradingAccount::query()->forUser()->where('is_active', true)->get(['id', 'name', 'account_type']),
            'pairs' => Trade::query()->forUser()->select('pair')->distinct()->orderBy('pair')->pluck('pair'),
        ]);
    }
}
