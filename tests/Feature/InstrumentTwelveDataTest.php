<?php

namespace Tests\Feature;

use App\Models\Instrument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class InstrumentTwelveDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_sync_instruments_from_twelvedata(): void
    {
        config()->set('services.twelvedata.key', 'test-key');
        config()->set('services.twelvedata.base_url', 'https://api.twelvedata.com');

        Http::fake([
            'https://api.twelvedata.com/symbol_search*' => Http::response([
                'data' => [
                    [
                        'symbol' => 'BTC/USD',
                        'instrument_name' => 'Bitcoin / US Dollar',
                        'instrument_type' => 'Digital Currency',
                    ],
                ],
            ], 200),
        ]);

        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('settings.instruments.sync'), [
                'query' => 'BTC',
                'limit' => 10,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('instruments', [
            'user_id' => $user->id,
            'symbol' => 'BTC/USD',
            'category' => 'crypto',
        ]);
    }

    public function test_prices_endpoint_updates_cached_price_and_change_percentage(): void
    {
        Cache::flush();

        config()->set('services.twelvedata.key', 'test-key');
        config()->set('services.twelvedata.base_url', 'https://api.twelvedata.com');

        Http::fake([
            'https://api.twelvedata.com/price*' => Http::response([
                'price' => '110.0',
            ], 200),
        ]);

        $user = User::factory()->create();
        $instrument = Instrument::query()->create([
            'user_id' => $user->id,
            'symbol' => 'EUR/USD',
            'name' => 'Euro / US Dollar',
            'category' => 'forex',
            'is_active' => true,
            'last_price' => 100,
        ]);

        $response = $this->actingAs($user)
            ->get(route('settings.instruments.prices', ['symbols' => 'EUR/USD']));

        $response->assertOk();
        $this->assertEquals(110.0, $response->json('prices.EUR/USD.price'));

        $instrument->refresh();

        $this->assertSame('110.000000', (string) $instrument->last_price);
        $this->assertSame('10.0000', (string) $instrument->price_change_pct);
        $this->assertSame('twelvedata', $instrument->price_source);
        $this->assertNotNull($instrument->price_updated_at);
    }
}
