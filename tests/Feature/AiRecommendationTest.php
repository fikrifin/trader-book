<?php

namespace Tests\Feature;

use App\Models\Instrument;
use App\Models\Trade;
use App\Models\TradingAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_page_is_displayed_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('ai.index'));

        $response->assertOk();
    }

    public function test_user_can_generate_ai_recommendation(): void
    {
        config()->set('services.ollama.api_key', 'test-key');
        config()->set('services.ollama.base_url', 'https://ollama.com');
        config()->set('services.ollama.model', 'gpt-oss:120b');

        $user = User::factory()->create();

        $instrument = Instrument::query()->create([
            'user_id' => $user->id,
            'symbol' => 'BTC/USD',
            'name' => 'Bitcoin / US Dollar',
            'category' => 'crypto',
            'is_active' => true,
            'last_price' => 100000,
        ]);

        Http::fake([
            'https://ollama.com/api/chat*' => Http::response([
                'model' => 'gpt-oss:120b',
                'message' => [
                    'content' => json_encode([
                        'market_bias' => 'bullish',
                        'recommended_action' => 'buy',
                        'entry_zone' => '99900-100100',
                        'stop_loss' => '98500',
                        'take_profit' => '102500',
                        'risk_pct' => 1.5,
                        'confidence' => 72,
                        'invalidation' => 'Price closes below 98500.',
                        'rationale' => ['Momentum positif.', 'Risk/reward memadai.'],
                        'checklist' => ['Tunggu pullback.', 'Konfirmasi volume.'],
                        'warning' => null,
                    ]),
                ],
                'prompt_eval_count' => 111,
                'eval_count' => 222,
                'total_duration' => 1000000,
            ], 200),
        ]);

        $response = $this->actingAs($user)->post(route('ai.recommendations.store'), [
            'instrument_id' => $instrument->id,
            'timeframe' => 'H1',
            'strategy_preference' => 'Momentum konservatif',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('ai_recommendations', [
            'user_id' => $user->id,
            'instrument_id' => $instrument->id,
            'status' => 'success',
            'provider' => 'ollama_cloud',
            'model' => 'gpt-oss:120b',
        ]);
    }

    public function test_blocked_risk_status_returns_no_trade(): void
    {
        config()->set('services.ollama.api_key', 'test-key');

        $user = User::factory()->create();

        $account = TradingAccount::query()->create([
            'user_id' => $user->id,
            'name' => 'Main',
            'broker' => 'Test Broker',
            'account_type' => 'demo',
            'initial_balance' => 1000,
            'current_balance' => 1000,
            'currency' => 'USD',
            'max_daily_loss' => 100,
            'max_trades_per_day' => 1,
            'is_active' => true,
        ]);

        $user->update(['active_account_id' => $account->id]);

        $instrument = Instrument::query()->create([
            'user_id' => $user->id,
            'symbol' => 'ETH/USD',
            'name' => 'Ethereum / US Dollar',
            'category' => 'crypto',
            'is_active' => true,
        ]);

        Trade::query()->create([
            'user_id' => $user->id,
            'trading_account_id' => $account->id,
            'instrument_id' => $instrument->id,
            'date' => now()->toDateString(),
            'session' => 'asia',
            'pair' => 'ETH/USD',
            'direction' => 'buy',
            'entry_price' => 100,
            'stop_loss' => 95,
            'take_profit_1' => 110,
            'lot_size' => 1,
            'result' => 'breakeven',
            'profit_loss' => 0,
            'profit_loss_gross' => 0,
        ]);

        $response = $this->actingAs($user)->post(route('ai.recommendations.store'), [
            'instrument_id' => $instrument->id,
            'timeframe' => 'H1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('warning');

        $this->assertDatabaseHas('ai_recommendations', [
            'user_id' => $user->id,
            'instrument_id' => $instrument->id,
            'status' => 'blocked',
        ]);
    }

    public function test_invalid_json_from_provider_is_recorded_as_failed(): void
    {
        config()->set('services.ollama.api_key', 'test-key');
        config()->set('services.ollama.base_url', 'https://ollama.com');

        $user = User::factory()->create();

        $instrument = Instrument::query()->create([
            'user_id' => $user->id,
            'symbol' => 'EUR/USD',
            'name' => 'Euro / US Dollar',
            'category' => 'forex',
            'is_active' => true,
        ]);

        Http::fake([
            'https://ollama.com/api/chat*' => Http::response([
                'model' => 'gpt-oss:120b',
                'message' => [
                    'content' => 'this is not json',
                ],
            ], 200),
        ]);

        $response = $this->actingAs($user)->post(route('ai.recommendations.store'), [
            'instrument_id' => $instrument->id,
            'timeframe' => 'H1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('ai_recommendations', [
            'user_id' => $user->id,
            'instrument_id' => $instrument->id,
            'status' => 'failed',
        ]);
    }
}
