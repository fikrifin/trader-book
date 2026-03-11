<?php

namespace Database\Seeders;

use App\Models\DailyJournal;
use App\Models\Instrument;
use App\Models\Setup;
use App\Models\Trade;
use App\Models\TradingAccount;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'currency_preference' => 'USD',
            'timezone' => 'Asia/Jakarta',
        ]);

        $account = TradingAccount::query()->create([
            'user_id' => $user->id,
            'name' => 'Main Demo Account',
            'broker' => 'Demo Broker',
            'account_type' => 'demo',
            'initial_balance' => 10000,
            'current_balance' => 10000,
            'currency' => 'USD',
            'max_daily_loss' => 300,
            'max_trades_per_day' => 5,
            'is_active' => true,
        ]);

        $user->update(['active_account_id' => $account->id]);

        $xau = Instrument::query()->create([
            'user_id' => $user->id,
            'symbol' => 'XAUUSD',
            'name' => 'Gold Spot',
            'category' => 'commodity',
            'pip_value' => 0.01,
            'is_active' => true,
        ]);

        $setup = Setup::query()->create([
            'user_id' => $user->id,
            'name' => 'Breakout + Retest',
            'description' => 'Daily trend breakout with confirmation retest',
            'is_active' => true,
        ]);

        $baseDate = Carbon::today()->subDays(6);

        for ($index = 0; $index < 7; $index++) {
            $date = $baseDate->copy()->addDays($index);
            $profitLoss = $index % 2 === 0 ? 120 + ($index * 10) : -80 - ($index * 5);
            $commission = 4.5;
            $swap = 0;

            Trade::query()->create([
                'user_id' => $user->id,
                'trading_account_id' => $account->id,
                'instrument_id' => $xau->id,
                'setup_id' => $setup->id,
                'date' => $date->toDateString(),
                'open_time' => '09:00:00',
                'close_time' => '11:30:00',
                'session' => 'london',
                'pair' => 'XAUUSD',
                'direction' => $index % 3 === 0 ? 'sell' : 'buy',
                'entry_price' => 2300 + $index,
                'stop_loss' => 2295 + $index,
                'take_profit_1' => 2310 + $index,
                'close_price' => 2308 + $index,
                'lot_size' => 0.5,
                'risk_amount' => 100,
                'commission' => $commission,
                'swap' => $swap,
                'result' => $profitLoss >= 0 ? 'win' : 'loss',
                'profit_loss_gross' => $profitLoss + $commission,
                'profit_loss' => $profitLoss,
                'pips' => $profitLoss >= 0 ? 80 : -50,
                'rr_ratio' => $profitLoss >= 0 ? 1.6 : 0.8,
                'rr_planned' => 2,
                'setup' => 'Breakout + Retest',
                'timeframe' => 'M15',
                'followed_plan' => true,
                'mistake' => $profitLoss < 0 ? 'Entry too early' : null,
                'notes' => 'Seeded sample trade',
                'tags' => ['seed', 'sample'],
            ]);

            DailyJournal::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'trading_account_id' => $account->id,
                    'date' => $date->toDateString(),
                ],
                [
                    'mood_before' => 3 + ($index % 3),
                    'plan' => 'Follow setup and risk management.',
                    'review' => $profitLoss >= 0 ? 'Execution was disciplined.' : 'Need more patience on entry.',
                    'followed_risk_rules' => true,
                ]
            );
        }

        $account->recalculateBalance();
    }
}
