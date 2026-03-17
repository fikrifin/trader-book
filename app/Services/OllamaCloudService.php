<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OllamaCloudService
{
    public function isConfigured(): bool
    {
        return filled(config('services.ollama.api_key'));
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(array $messages): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('OLLAMA_API_KEY belum dikonfigurasi.');
        }

        $startedAt = microtime(true);
        $response = $this->requestWithRetry('/api/chat', [
            'model' => config('services.ollama.model', 'gpt-oss:120b'),
            'stream' => false,
            'think' => false,
            'messages' => $messages,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Gagal menghubungi Ollama Cloud. HTTP '.$response->status().' - '.$this->summarizeBody($response));
        }

        $payload = $response->json();
        if (! is_array($payload)) {
            throw new RuntimeException('Respons Ollama Cloud bukan JSON object.');
        }

        if (filled(data_get($payload, 'error'))) {
            throw new RuntimeException('Ollama Cloud error: '.(string) data_get($payload, 'error'));
        }

        $content = $this->extractAssistantContent($payload);

        if (blank($content)) {
            $fallback = $this->generateFallback($messages);
            if (filled($fallback['content'] ?? null)) {
                return $fallback;
            }

            Log::warning('ai.ollama.invalid-payload', [
                'keys' => array_keys($payload),
                'model' => data_get($payload, 'model'),
                'message_keys' => array_keys((array) data_get($payload, 'message', [])),
            ]);

            throw new RuntimeException('Respons Ollama Cloud tidak valid (content kosong/tidak ditemukan).');
        }

        return [
            'content' => $content,
            'model' => (string) (data_get($payload, 'model') ?: config('services.ollama.model', 'gpt-oss:120b')),
            'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'token_usage' => [
                'prompt_eval_count' => data_get($payload, 'prompt_eval_count'),
                'eval_count' => data_get($payload, 'eval_count'),
                'total_duration' => data_get($payload, 'total_duration'),
            ],
        ];
    }

    protected function requestWithRetry(string $path, array $body): Response
    {
        $attempts = max(1, (int) config('services.ollama.retry_times', 2));
        $sleepMs = max(0, (int) config('services.ollama.retry_sleep_ms', 400));

        $response = $this->client()->post($path, $body);

        for ($i = 1; $i < $attempts; $i++) {
            if (! in_array($response->status(), [429, 500, 502, 503, 504], true)) {
                return $response;
            }

            if ($sleepMs > 0) {
                usleep($sleepMs * 1000);
            }

            $response = $this->client()->post($path, $body);
        }

        return $response;
    }

    protected function client()
    {
        return Http::baseUrl((string) config('services.ollama.base_url', 'https://ollama.com'))
            ->acceptJson()
            ->asJson()
            ->timeout((int) config('services.ollama.timeout', 30))
            ->withToken((string) config('services.ollama.api_key'));
    }

    protected function extractAssistantContent(array $payload): ?string
    {
        $candidates = [
            data_get($payload, 'message.content'),
            data_get($payload, 'message.thinking'),
            data_get($payload, 'message.reasoning'),
            data_get($payload, 'message.reasoning_content'),
            data_get($payload, 'response'),
            data_get($payload, 'content'),
            data_get($payload, 'thinking'),
            data_get($payload, 'choices.0.message.content'),
            data_get($payload, 'choices.0.message.reasoning_content'),
            data_get($payload, 'choices.0.text'),
            data_get($payload, 'output_text'),
            data_get($payload, 'data.0.content'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && filled(trim($candidate))) {
                return trim($candidate);
            }

            // Some providers return segmented content objects.
            if (is_array($candidate)) {
                $text = collect(Arr::wrap($candidate))
                    ->map(function ($part) {
                        if (is_string($part)) {
                            return $part;
                        }

                        if (is_array($part)) {
                            return (string) ($part['text'] ?? $part['content'] ?? '');
                        }

                        return '';
                    })
                    ->implode('');

                if (filled(trim($text))) {
                    return trim($text);
                }
            }
        }

        return null;
    }

    protected function summarizeBody(Response $response): string
    {
        $body = trim((string) $response->body());

        if ($body === '') {
            return 'empty body';
        }

        return mb_substr($body, 0, 200);
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    protected function generateFallback(array $messages): array
    {
        $prompt = collect($messages)
            ->map(fn (array $m) => strtoupper((string) ($m['role'] ?? 'USER')).":\n".((string) ($m['content'] ?? '')))
            ->implode("\n\n");

        $startedAt = microtime(true);
        $response = $this->requestWithRetry('/api/generate', [
            'model' => config('services.ollama.model', 'gpt-oss:120b'),
            'stream' => false,
            'think' => false,
            'prompt' => $prompt,
        ]);

        if (! $response->successful()) {
            return [
                'content' => null,
                'model' => (string) config('services.ollama.model', 'gpt-oss:120b'),
                'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'token_usage' => null,
            ];
        }

        $payload = $response->json();
        $content = is_array($payload)
            ? (data_get($payload, 'response')
                ?: data_get($payload, 'content')
                ?: data_get($payload, 'thinking')
                ?: data_get($payload, 'message.content')
                ?: data_get($payload, 'message.thinking'))
            : null;

        return [
            'content' => is_string($content) ? trim($content) : null,
            'model' => is_array($payload) ? (string) (data_get($payload, 'model') ?: config('services.ollama.model', 'gpt-oss:120b')) : (string) config('services.ollama.model', 'gpt-oss:120b'),
            'latency_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            'token_usage' => is_array($payload)
                ? [
                    'prompt_eval_count' => data_get($payload, 'prompt_eval_count'),
                    'eval_count' => data_get($payload, 'eval_count'),
                    'total_duration' => data_get($payload, 'total_duration'),
                ]
                : null,
        ];
    }
}
