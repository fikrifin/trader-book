<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class StatisticService
{
    public function __construct(protected Collection $trades)
    {
    }

    public function summary(): array
    {
        return [
            'total_trades' => $this->trades->count(),
            'win_rate' => $this->winRate(),
            'profit_factor' => $this->profitFactor(),
            'expectancy' => $this->expectancy(),
            'average_win' => $this->averageWin(),
            'average_loss' => $this->averageLoss(),
            'max_drawdown' => $this->maxDrawdown(),
            'streaks' => $this->consecutiveWinLoss(),
            'net_vs_gross' => $this->netVsGross(),
            'followed_plan_rate' => $this->followedPlanRate(),
            'average_duration_minutes' => $this->averageDuration(),
        ];
    }

    public function winRate(): float
    {
        if ($this->trades->isEmpty()) {
            return 0;
        }

        $wins = $this->trades->where('result', 'win')->count();

        return round(($wins / $this->trades->count()) * 100, 2);
    }

    public function profitFactor(): float|string
    {
        $grossProfit = $this->trades->where('profit_loss', '>', 0)->sum('profit_loss');
        $grossLoss = abs((float) $this->trades->where('profit_loss', '<', 0)->sum('profit_loss'));

        if ($grossLoss <= 0.0) {
            return $grossProfit > 0 ? 'INF' : 0;
        }

        return round($grossProfit / $grossLoss, 2);
    }

    public function expectancy(): float
    {
        $winRate = $this->winRate() / 100;
        $lossRate = 1 - $winRate;

        return round(($winRate * $this->averageWin()) - ($lossRate * $this->averageLoss()), 2);
    }

    public function averageWin(): float
    {
        $wins = $this->trades->where('profit_loss', '>', 0);

        if ($wins->isEmpty()) {
            return 0;
        }

        return round((float) $wins->avg('profit_loss'), 2);
    }

    public function averageLoss(): float
    {
        $losses = $this->trades->where('profit_loss', '<', 0);

        if ($losses->isEmpty()) {
            return 0;
        }

        return round(abs((float) $losses->avg('profit_loss')), 2);
    }

    public function maxDrawdown(): float
    {
        if ($this->trades->isEmpty()) {
            return 0;
        }

        $ordered = $this->trades->sortBy(['date', 'id'])->values();

        $equity = 0.0;
        $peak = 0.0;
        $maxDrawdown = 0.0;

        foreach ($ordered as $trade) {
            $equity += (float) $trade->profit_loss;
            $peak = max($peak, $equity);

            if ($peak > 0) {
                $drawdown = (($equity - $peak) / $peak) * 100;
                $maxDrawdown = min($maxDrawdown, $drawdown);
            }
        }

        return round($maxDrawdown, 2);
    }

    public function consecutiveWinLoss(): array
    {
        $ordered = $this->trades->sortBy(['date', 'id'])->values();

        $currentWin = 0;
        $currentLoss = 0;
        $maxWin = 0;
        $maxLoss = 0;

        foreach ($ordered as $trade) {
            if ($trade->result === 'win') {
                $currentWin++;
                $currentLoss = 0;
            } elseif ($trade->result === 'loss') {
                $currentLoss++;
                $currentWin = 0;
            } else {
                $currentWin = 0;
                $currentLoss = 0;
            }

            $maxWin = max($maxWin, $currentWin);
            $maxLoss = max($maxLoss, $currentLoss);
        }

        return [
            'max_win_streak' => $maxWin,
            'max_loss_streak' => $maxLoss,
        ];
    }

    public function netVsGross(): array
    {
        $totalFees = (float) $this->trades->sum('commission') + abs((float) $this->trades->sum('swap'));

        return [
            'total_net' => round((float) $this->trades->sum('profit_loss'), 2),
            'total_gross' => round((float) $this->trades->sum('profit_loss_gross'), 2),
            'total_fees' => round($totalFees, 2),
        ];
    }

    public function performanceByPair(): Collection
    {
        return $this->trades
            ->groupBy('pair')
            ->map(function (Collection $group, string $pair) {
                $total = $group->count();
                $wins = $group->where('result', 'win')->count();

                return [
                    'pair' => $pair,
                    'total_trades' => $total,
                    'wins' => $wins,
                    'losses' => $group->where('result', 'loss')->count(),
                    'win_rate' => $total > 0 ? round(($wins / $total) * 100, 2) : 0,
                    'avg_rr' => round((float) $group->avg('rr_ratio'), 2),
                    'net_pl' => round((float) $group->sum('profit_loss'), 2),
                ];
            })
            ->sortByDesc('net_pl')
            ->values();
    }

    public function performanceBySetup(): Collection
    {
        return $this->trades
            ->groupBy(fn ($trade) => $trade->setup ?: 'Unknown')
            ->map(function (Collection $group, string $setup) {
                $total = $group->count();
                $wins = $group->where('result', 'win')->count();

                return [
                    'setup' => $setup,
                    'total_trades' => $total,
                    'win_rate' => $total > 0 ? round(($wins / $total) * 100, 2) : 0,
                    'avg_rr' => round((float) $group->avg('rr_ratio'), 2),
                    'net_pl' => round((float) $group->sum('profit_loss'), 2),
                ];
            })
            ->sortByDesc('net_pl')
            ->values();
    }

    public function performanceBySession(): Collection
    {
        return $this->trades
            ->groupBy('session')
            ->map(fn (Collection $group, string $session) => [
                'session' => $session,
                'total_trades' => $group->count(),
                'net_pl' => round((float) $group->sum('profit_loss'), 2),
            ])
            ->values();
    }

    public function performanceByTimeframe(): Collection
    {
        return $this->trades
            ->groupBy(fn ($trade) => $trade->timeframe ?: 'Unknown')
            ->map(fn (Collection $group, string $timeframe) => [
                'timeframe' => $timeframe,
                'total_trades' => $group->count(),
                'net_pl' => round((float) $group->sum('profit_loss'), 2),
            ])
            ->sortByDesc('net_pl')
            ->values();
    }

    public function performanceByDayOfWeek(): Collection
    {
        return $this->trades
            ->groupBy(function ($trade) {
                return Carbon::parse($trade->date)->isoWeekday();
            })
            ->map(fn (Collection $group, int $isoDay) => [
                'day' => $isoDay,
                'label' => Carbon::create()->startOfWeek()->addDays($isoDay - 1)->translatedFormat('l'),
                'total_trades' => $group->count(),
                'net_pl' => round((float) $group->sum('profit_loss'), 2),
            ])
            ->sortBy('day')
            ->values();
    }

    public function averageDuration(): float
    {
        $durations = $this->trades
            ->filter(fn ($trade) => ! empty($trade->open_time) && ! empty($trade->close_time))
            ->map(function ($trade) {
                $open = Carbon::parse($trade->date.' '.$trade->open_time);
                $close = Carbon::parse($trade->date.' '.$trade->close_time);

                if ($close->lessThan($open)) {
                    $close = $close->addDay();
                }

                return $open->diffInMinutes($close);
            });

        if ($durations->isEmpty()) {
            return 0;
        }

        return round((float) $durations->avg(), 2);
    }

    public function followedPlanRate(): float
    {
        if ($this->trades->isEmpty()) {
            return 0;
        }

        $followed = $this->trades->where('followed_plan', true)->count();

        return round(($followed / $this->trades->count()) * 100, 2);
    }

    public function mistakeDistribution(): Collection
    {
        return $this->trades
            ->filter(fn ($trade) => filled($trade->mistake))
            ->groupBy(function ($trade) {
                return trim((string) $trade->mistake);
            })
            ->map(fn (Collection $group, string $mistake) => [
                'mistake' => $mistake,
                'count' => $group->count(),
            ])
            ->sortByDesc('count')
            ->values();
    }
}
