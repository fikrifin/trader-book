<?php

namespace App\Http\Controllers;

use App\Models\MonthlyTarget;
use App\Models\Trade;
use App\Models\TradingAccount;
use App\Services\RiskRuleService;
use Carbon\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $user = auth()->user();

        $activeAccountId = $user?->active_account_id
            ?: TradingAccount::query()->forUser()->value('id');

        $baseQuery = Trade::query()
            ->forUser()
            ->when($activeAccountId, fn ($query) => $query->where('trading_account_id', $activeAccountId));

        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        $todayTrades = (clone $baseQuery)->whereDate('date', $today)->get();
        $monthTrades = (clone $baseQuery)->whereBetween('date', [$startOfMonth->toDateString(), Carbon::now()->toDateString()])->get();

        $totalWinMonth = $monthTrades->where('result', 'win')->count();
        $monthWinRate = $monthTrades->count() > 0
            ? round(($totalWinMonth / $monthTrades->count()) * 100, 2)
            : 0;

        $grossProfit = (float) $monthTrades->where('profit_loss', '>', 0)->sum('profit_loss');
        $grossLoss = abs((float) $monthTrades->where('profit_loss', '<', 0)->sum('profit_loss'));
        $profitFactor = $grossLoss > 0 ? round($grossProfit / $grossLoss, 2) : ($grossProfit > 0 ? 'INF' : 0);

        $dailyGroups = (clone $baseQuery)
            ->whereDate('date', '>=', Carbon::now()->subDays(90)->toDateString())
            ->orderBy('date')
            ->get()
            ->groupBy(fn ($trade) => Carbon::parse($trade->date)->toDateString())
            ->map(fn ($group, $date) => [
                'date' => $date,
                'pl' => round((float) $group->sum('profit_loss'), 2),
            ])
            ->values();

        $running = 0;
        $equityCurve = $dailyGroups->map(function ($row) use (&$running) {
            $running += (float) $row['pl'];

            return [
                'date' => $row['date'],
                'cumulative_pl' => round($running, 2),
            ];
        })->values();

        $dailyPlChart = $dailyGroups->where('date', '>=', Carbon::now()->subDays(30)->toDateString())->values();

        $recentTrades = (clone $baseQuery)->latest('date')->latest('id')->take(5)->get();

        $target = null;
        if ($activeAccountId) {
            $target = MonthlyTarget::query()
                ->forUser()
                ->where('trading_account_id', $activeAccountId)
                ->where('year', Carbon::now()->year)
                ->where('month', Carbon::now()->month)
                ->first();
        }

        $monthPl = round((float) $monthTrades->sum('profit_loss'), 2);
        $targetProgress = [
            'target_profit' => $target?->target_profit,
            'actual_profit' => $monthPl,
            'progress_pct' => $target && (float) $target->target_profit > 0
                ? round(($monthPl / (float) $target->target_profit) * 100, 2)
                : 0,
        ];

        $riskStatus = ['today_trade_count' => 0, 'today_loss' => 0, 'warnings' => [], 'is_blocked' => false];
        if ($activeAccountId) {
            $account = TradingAccount::query()->forUser()->find($activeAccountId);
            if ($account) {
                $riskStatus = app(RiskRuleService::class)->checkDailyStatus($account, $today->toDateString());
            }
        }

        return Inertia::render('Dashboard/Index', [
            'today_summary' => [
                'trade_count' => $todayTrades->count(),
                'pl' => round((float) $todayTrades->sum('profit_loss'), 2),
            ],
            'month_summary' => [
                'trade_count' => $monthTrades->count(),
                'pl' => $monthPl,
                'win_rate' => $monthWinRate,
                'profit_factor' => $profitFactor,
            ],
            'equity_curve' => $equityCurve,
            'daily_pl_chart' => $dailyPlChart,
            'recent_trades' => $recentTrades,
            'target_progress' => $targetProgress,
            'risk_status' => $riskStatus,
        ]);
    }
}
