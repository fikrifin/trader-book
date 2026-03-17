<?php

namespace App\Services;

use App\Models\Instrument;
use App\Models\Trade;
use App\Models\TradingAccount;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class AiRecommendationService
{
    public function __construct(
        protected OllamaCloudService $ollamaCloudService,
        protected RiskRuleService $riskRuleService,
    ) {
    }

    public function generate(User $user, Instrument $instrument, string $timeframe, ?string $strategyPreference = null): array
    {
        $context = $this->buildContext($user, $instrument, $timeframe, $strategyPreference);

        if (($context['risk_status']['is_blocked'] ?? false) === true) {
            return [
                'status' => 'blocked',
                'provider' => 'ollama_cloud',
                'model' => (string) config('services.ollama.model', 'gpt-oss:120b'),
                'prompt_context' => $context,
                'recommendation' => [
                    'market_bias' => 'neutral',
                    'recommended_action' => 'no_trade',
                    'entry_zone' => '-',
                    'stop_loss' => '-',
                    'take_profit' => '-',
                    'risk_pct' => 0,
                    'confidence' => 0,
                    'invalidation' => 'Risk rule account sedang blocked.',
                    'rationale' => ['Trading diblokir oleh aturan risiko harian akun.'],
                    'checklist' => ['Tunggu reset sesi atau sesuaikan risk plan.'],
                    'warning' => 'Akun sedang dalam status blocked.',
                ],
                'risk_flags' => $context['risk_status']['warnings'] ?? [],
                'confidence' => 0,
                'latency_ms' => 0,
                'token_usage' => null,
                'error_message' => null,
            ];
        }

        $messages = [
            [
                'role' => 'system',
                'content' => $this->systemPrompt(),
            ],
            [
                'role' => 'user',
                'content' => "Gunakan context berikut dan balas JSON valid saja:\n".json_encode($context, JSON_PRETTY_PRINT),
            ],
        ];

        try {
            $chat = $this->ollamaCloudService->chat($messages);
            $recommendation = $this->parseRecommendationJson($chat['content']);

            $recommendation = $this->applySafetyGuardrails($recommendation, $context);

            Log::info('ai.recommendation.succeeded', [
                'user_id' => $user->id,
                'instrument_id' => $instrument->id,
                'model' => $chat['model'],
                'latency_ms' => $chat['latency_ms'],
            ]);

            return [
                'status' => 'success',
                'provider' => 'ollama_cloud',
                'model' => $chat['model'],
                'prompt_context' => $context,
                'recommendation' => $recommendation,
                'risk_flags' => $context['risk_status']['warnings'] ?? [],
                'confidence' => $recommendation['confidence'] ?? null,
                'latency_ms' => $chat['latency_ms'],
                'token_usage' => $chat['token_usage'] ?? null,
                'error_message' => null,
            ];
        } catch (RuntimeException $exception) {
            Log::warning('ai.recommendation.failed', [
                'user_id' => $user->id,
                'instrument_id' => $instrument->id,
                'message' => $exception->getMessage(),
            ]);

            return [
                'status' => 'failed',
                'provider' => 'ollama_cloud',
                'model' => (string) config('services.ollama.model', 'gpt-oss:120b'),
                'prompt_context' => $context,
                'recommendation' => null,
                'risk_flags' => $context['risk_status']['warnings'] ?? [],
                'confidence' => null,
                'latency_ms' => null,
                'token_usage' => null,
                'error_message' => $exception->getMessage(),
            ];
        }
    }

    protected function buildContext(User $user, Instrument $instrument, string $timeframe, ?string $strategyPreference): array
    {
        $activeAccount = $user->active_account_id
            ? TradingAccount::query()->where('user_id', $user->id)->find($user->active_account_id)
            : TradingAccount::query()->where('user_id', $user->id)->first();

        $riskStatus = [
            'today_trade_count' => 0,
            'today_loss' => 0,
            'warnings' => [],
            'is_blocked' => false,
        ];

        if ($activeAccount) {
            $riskStatus = $this->riskRuleService->checkDailyStatus($activeAccount, now()->toDateString());
        }

        $instrumentTrades = Trade::query()
            ->where('user_id', $user->id)
            ->where('instrument_id', $instrument->id)
            ->latest('date')
            ->limit(100)
            ->get();

        $stats = new StatisticService($instrumentTrades);

        return [
            'time' => now()->toDateTimeString(),
            'instrument' => [
                'id' => $instrument->id,
                'symbol' => $instrument->symbol,
                'name' => $instrument->name,
                'category' => $instrument->category,
                'timeframe' => $timeframe,
                'last_price' => $instrument->last_price,
                'price_change_pct' => $instrument->price_change_pct,
                'price_updated_at' => optional($instrument->price_updated_at)->toDateTimeString(),
            ],
            'user_stats' => [
                'instrument_trade_count' => $instrumentTrades->count(),
                'win_rate' => $stats->winRate(),
                'profit_factor' => $stats->profitFactor(),
                'avg_win' => $stats->averageWin(),
                'avg_loss' => $stats->averageLoss(),
                'max_drawdown' => $stats->maxDrawdown(),
            ],
            'risk_status' => $riskStatus,
            'account_limits' => [
                'max_daily_loss' => $activeAccount?->max_daily_loss,
                'max_trades_per_day' => $activeAccount?->max_trades_per_day,
                'currency' => $activeAccount?->currency,
            ],
            'strategy_preference' => $strategyPreference,
        ];
    }

    protected function systemPrompt(): string
    {
        return 'Anda adalah asisten trading konservatif. Jawab hanya JSON valid tanpa markdown.'
            .' Gunakan field wajib: market_bias, recommended_action, entry_zone, stop_loss, take_profit, risk_pct, confidence, invalidation, rationale, checklist, warning.'
            .' Pilihan recommended_action: buy|sell|wait|no_trade. confidence rentang 0-100.'
            .' Jangan berikan kepastian profit dan hormati risk guardrails.';
    }

    protected function parseRecommendationJson(string $content): array
    {
        $trimmed = trim($content);

        if (str_starts_with($trimmed, '```')) {
            $trimmed = preg_replace('/^```(?:json)?\s*/', '', $trimmed) ?: $trimmed;
            $trimmed = preg_replace('/\s*```$/', '', $trimmed) ?: $trimmed;
            $trimmed = trim($trimmed);
        }

        $decoded = json_decode($trimmed, true);

        if (! is_array($decoded)) {
            $jsonCandidate = $this->extractFirstJsonObject($trimmed);
            if ($jsonCandidate !== null) {
                $decoded = json_decode($jsonCandidate, true);
            }
        }

        if (! is_array($decoded)) {
            throw new RuntimeException('Output AI tidak berupa JSON valid.');
        }

        $required = [
            'market_bias', 'recommended_action', 'entry_zone', 'stop_loss', 'take_profit',
            'risk_pct', 'confidence', 'invalidation', 'rationale', 'checklist', 'warning',
        ];

        foreach ($required as $field) {
            if (! array_key_exists($field, $decoded)) {
                throw new RuntimeException("Field output AI tidak lengkap: {$field}");
            }
        }

        return $decoded;
    }

    protected function extractFirstJsonObject(string $text): ?string
    {
        $start = strpos($text, '{');
        if ($start === false) {
            return null;
        }

        $depth = 0;
        $inString = false;
        $escaped = false;
        $length = strlen($text);

        for ($i = $start; $i < $length; $i++) {
            $char = $text[$i];

            if ($inString) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === '"') {
                    $inString = false;
                }
                continue;
            }

            if ($char === '"') {
                $inString = true;
                continue;
            }

            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
                if ($depth === 0) {
                    return substr($text, $start, $i - $start + 1);
                }
            }
        }

        return null;
    }

    protected function applySafetyGuardrails(array $recommendation, array $context): array
    {
        $action = strtolower((string) ($recommendation['recommended_action'] ?? 'wait'));
        $confidence = (float) ($recommendation['confidence'] ?? 0);

        if (! in_array($action, ['buy', 'sell', 'wait', 'no_trade'], true)) {
            $recommendation['recommended_action'] = 'wait';
        }

        $maxRiskPct = 2.0;
        $riskPct = (float) ($recommendation['risk_pct'] ?? 0);
        if ($riskPct > $maxRiskPct) {
            $recommendation['risk_pct'] = $maxRiskPct;
            $recommendation['warning'] = 'Risk disesuaikan otomatis ke batas aman 2%.';
        }

        if ($confidence < 45 && in_array($recommendation['recommended_action'], ['buy', 'sell'], true)) {
            $recommendation['recommended_action'] = 'wait';
            $recommendation['warning'] = 'Confidence rendah, disarankan wait.';
        }

        if (($context['risk_status']['is_blocked'] ?? false) === true) {
            $recommendation['recommended_action'] = 'no_trade';
            $recommendation['warning'] = 'Akun sedang blocked oleh risk rule.';
            $recommendation['confidence'] = 0;
        }

        return $recommendation;
    }
}
