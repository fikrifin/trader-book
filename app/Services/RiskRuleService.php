<?php

namespace App\Services;

use App\Models\Trade;
use App\Models\TradingAccount;

class RiskRuleService
{
    public function checkDailyStatus(TradingAccount $account, string $date): array
    {
        $todayTrades = Trade::where('trading_account_id', $account->id)
            ->whereDate('date', $date)
            ->get();

        $todayLoss = abs($todayTrades->where('profit_loss', '<', 0)->sum('profit_loss'));
        $todayCount = $todayTrades->count();

        $warnings = [];
        $isBlocked = false;

        if ($account->max_trades_per_day && $todayCount >= $account->max_trades_per_day) {
            $warnings[] = "Sudah mencapai batas {$account->max_trades_per_day} trade hari ini.";
            $isBlocked = true;
        }

        if ($account->max_daily_loss && $todayLoss >= $account->max_daily_loss) {
            $warnings[] = "Sudah mencapai batas max daily loss {$account->currency}{$account->max_daily_loss}.";
            $isBlocked = true;
        }

        return [
            'today_trade_count' => $todayCount,
            'today_loss' => $todayLoss,
            'warnings' => $warnings,
            'is_blocked' => $isBlocked,
        ];
    }
}
