<?php

namespace App\Http\Controllers;

use App\Models\AiRecommendation;
use App\Models\Instrument;
use App\Services\AiRecommendationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class AiRecommendationController extends Controller
{
    public function __construct(protected AiRecommendationService $aiRecommendationService)
    {
    }

    public function index(): Response
    {
        $instruments = Instrument::query()
            ->forUser()
            ->where('is_active', true)
            ->orderBy('symbol')
            ->get(['id', 'symbol', 'name', 'category', 'last_price', 'price_change_pct', 'price_updated_at']);

        $history = AiRecommendation::query()
            ->where('user_id', Auth::id())
            ->latest('id')
            ->limit(15)
            ->get();

        return Inertia::render('AI/Index', [
            'instruments' => $instruments,
            'history' => $history,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'instrument_id' => ['required', 'integer'],
            'timeframe' => ['required', 'string', 'max:20'],
            'strategy_preference' => ['nullable', 'string', 'max:500'],
        ]);

        $instrument = Instrument::query()
            ->forUser()
            ->findOrFail($validated['instrument_id']);

        Log::info('ai.recommendation.requested', [
            'user_id' => Auth::id(),
            'instrument_id' => $instrument->id,
            'timeframe' => $validated['timeframe'],
        ]);

        $result = $this->aiRecommendationService->generate(
            Auth::user(),
            $instrument,
            $validated['timeframe'],
            $validated['strategy_preference'] ?? null,
        );

        AiRecommendation::query()->create([
            'user_id' => Auth::id(),
            'instrument_id' => $instrument->id,
            'timeframe' => $validated['timeframe'],
            'provider' => $result['provider'],
            'model' => $result['model'],
            'prompt_context' => $result['prompt_context'],
            'recommendation' => $result['recommendation'],
            'confidence' => $result['confidence'],
            'risk_flags' => $result['risk_flags'],
            'latency_ms' => $result['latency_ms'],
            'token_usage' => $result['token_usage'],
            'status' => $result['status'],
            'error_message' => $result['error_message'],
        ]);

        if ($result['status'] === 'success') {
            return back()->with('success', 'AI recommendation berhasil dibuat.');
        }

        if ($result['status'] === 'blocked') {
            Log::info('ai.recommendation.blocked', [
                'user_id' => Auth::id(),
                'instrument_id' => $instrument->id,
            ]);

            return back()->with('warning', 'Akun sedang blocked oleh risk rule. AI mengembalikan no_trade.');
        }

        return back()->with('error', 'Gagal menghasilkan rekomendasi AI: '.$result['error_message']);
    }
}
