<?php

namespace App\Domain\Agent;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/**
 * HTTP client for OpenRouter chat completions.
 * Accepts model, messages, optional response_format; returns raw content + usage.
 * API key is passed in (from ProjectSecrets), never logged.
 *
 * Inputs: API key, model, messages array, options (temperature, max_tokens, json_mode).
 * Outputs: ['content' => string, 'usage' => array|null, 'finish_reason' => string|null].
 * Side effects: Network request.
 */
class OpenRouterClient
{
    private string $baseUrl;

    public function __construct(?string $baseUrl = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? config('services.openrouter.base_url', 'https://openrouter.ai/api/v1'), '/');
    }

    /**
     * @param array<int, array{role: string, content: string}> $messages
     * @param array{temperature?: float, max_tokens?: int, top_p?: float, response_format?: array} $options
     * @return array{content: string, usage: array|null, finish_reason: string|null}
     */
    public function chat(string $apiKey, string $model, array $messages, array $options = []): array
    {
        $payload = [
            'model' => $model,
            'messages' => $messages,
        ];
        if (isset($options['temperature'])) {
            $payload['temperature'] = (float) $options['temperature'];
        }
        if (isset($options['max_tokens'])) {
            $payload['max_tokens'] = (int) $options['max_tokens'];
        }
        if (isset($options['top_p'])) {
            $payload['top_p'] = (float) $options['top_p'];
        }
        if (!empty($options['response_format'])) {
            $payload['response_format'] = $options['response_format'];
        } elseif (!empty($options['json_mode'])) {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        $response = $this->request($apiKey)->post($this->baseUrl . '/chat/completions', $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('OpenRouter API error: ' . $response->body());
        }

        $data = $response->json();
        $choices = $data['choices'] ?? [];
        $first = $choices[0] ?? [];
        $message = $first['message'] ?? [];
        $content = $message['content'] ?? '';
        $usage = $data['usage'] ?? null;
        $finishReason = $first['finish_reason'] ?? null;

        return [
            'content' => is_string($content) ? $content : json_encode($content),
            'usage' => $usage,
            'finish_reason' => $finishReason,
        ];
    }

    private function request(string $apiKey): PendingRequest
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
            'HTTP-Referer' => config('app.url', ''),
        ])->timeout(120);
    }
}
