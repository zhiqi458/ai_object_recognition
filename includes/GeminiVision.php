<?php

declare(strict_types=1);

require_once __DIR__ . '/AIProvider.php';
require_once __DIR__ . '/ProductResult.php';

final class GeminiVision extends AIProvider
{
    public function recognize(string $base64Image): array
    {
        $apiKey = (string)($this->config['gemini_api_key'] ?? '');
        $lastError = 'Gemini connection failed.';

        if ($apiKey === '') {
            return ['ok' => false, 'error' => 'AI provider is not configured.'];
        }

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $this->buildPrompt()],
                        [
                            'inline_data' => [
                                'mime_type' => 'image/jpeg',
                                'data' => $base64Image,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $timeout = $this->requestTimeoutSeconds();
        foreach ($this->candidateModels('gemini_model', 'gemini_fallback_models', 'gemini-3.8-flash') as $model) {
            $response = $this->postJson($this->buildUrl($model, $apiKey), $payload, $timeout);
            if (!$response['ok']) {
                $lastError = (string)($response['error'] ?? $lastError);
                if ($this->shouldTryNextModel($response)) {
                    continue;
                }

                return ['ok' => false, 'error' => $response['error']];
            }

            $text = $this->extractText($response['data']);
            if ($text === '') {
                return ['ok' => false, 'error' => 'Gemini returned an empty result.'];
            }

            $decoded = $this->parseJsonFromText($text);
            if ($decoded === null) {
                return ['ok' => false, 'error' => 'Gemini returned invalid JSON.'];
            }

            $normalized = $this->normalizeResponse($decoded);
            return [
                'ok' => true,
                'result' => ProductResult::fromArray($normalized, 'gemini')->toArray(),
                'provider' => 'gemini',
            ];
        }

        return ['ok' => false, 'error' => $lastError];
    }

    public function testConnection(): array
    {
        $apiKey = (string)($this->config['gemini_api_key'] ?? '');
        $lastError = 'Gemini connection failed.';
        if ($apiKey === '') {
            return ['ok' => false, 'error' => 'Gemini API key is not configured.'];
        }

        $payload = [
            'contents' => [[ 'parts' => [['text' => 'Return JSON {"status":"ok"}']] ]],
        ];

        $timeout = $this->requestTimeoutSeconds();
        foreach ($this->candidateModels('gemini_model', 'gemini_fallback_models', 'gemini-3.8-flash') as $model) {
            $response = $this->postJson($this->buildUrl($model, $apiKey), $payload, $timeout);
            if (!$response['ok']) {
                $lastError = (string)($response['error'] ?? $lastError);
                if ($this->shouldTryNextModel($response)) {
                    continue;
                }

                return ['ok' => false, 'error' => $response['error']];
            }

            return ['ok' => true, 'message' => 'Gemini connected successfully.'];
        }

        return ['ok' => false, 'error' => $lastError];
    }

    private function requestTimeoutSeconds(): int
    {
        $timeout = (int)($this->config['request_timeout'] ?? 15);
        return max(10, min(15, $timeout));
    }

    private function postJson(string $url, array $payload, int $timeout): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $error !== '') {
            return ['ok' => false, 'error' => 'Connection failed: ' . $error, 'status' => $status];
        }

        $decoded = json_decode($body, true);
        if ($status >= 400) {
            $message = is_array($decoded) && isset($decoded['error']['message']) ? $decoded['error']['message'] : 'Provider request failed.';
            return ['ok' => false, 'error' => $message, 'status' => $status];
        }

        if (!is_array($decoded)) {
            return ['ok' => false, 'error' => 'Gemini returned an invalid response.', 'status' => $status];
        }

        return ['ok' => true, 'data' => $decoded];
    }

    private function buildUrl(string $model, string $apiKey): string
    {
        return 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($apiKey);
    }

    /**
     * @return array<int, string>
     */
    private function candidateModels(string $primaryKey, string $fallbackKey, string $defaultModel): array
    {
        $models = [];
        $primary = $this->normalizeModelName((string)($this->config[$primaryKey] ?? $defaultModel));
        if ($primary !== '') {
            $models[] = $primary;
        }

        $fallbacks = preg_split('/[\r\n,]+/', (string)($this->config[$fallbackKey] ?? ''), -1, PREG_SPLIT_NO_EMPTY);
        if (is_array($fallbacks)) {
            foreach ($fallbacks as $fallback) {
                $normalized = $this->normalizeModelName($fallback);
                if ($normalized !== '') {
                    $models[] = $normalized;
                }
            }
        }

        return array_values(array_unique($models));
    }

    private function shouldTryNextModel(array $response): bool
    {
        $status = (int)($response['status'] ?? 0);
        $error = strtolower((string)($response['error'] ?? ''));

        if ($status === 404 || $status === 429) {
            return true;
        }

        if ($status === 400 || $status === 403) {
            return str_contains($error, 'no longer available')
                || str_contains($error, 'not found')
                || str_contains($error, 'not available')
                || str_contains($error, 'quota')
                || str_contains($error, 'rate limit')
                || str_contains($error, 'too many requests')
                || str_contains($error, 'limit');
        }

        return false;
    }

    private function extractText(array $data): string
    {
        if (!isset($data['candidates']) || !is_array($data['candidates'])) {
            return '';
        }

        $parts = [];
        foreach ($data['candidates'] as $candidate) {
            if (!isset($candidate['content']['parts']) || !is_array($candidate['content']['parts'])) {
                continue;
            }

            foreach ($candidate['content']['parts'] as $part) {
                if (isset($part['text'])) {
                    $parts[] = (string)$part['text'];
                }
            }
        }

        return implode("\n", $parts);
    }

    private function normalizeModelName(string $model): string
    {
        $model = strtolower(trim($model));
        $model = preg_replace('/^models\//', '', $model) ?? $model;
        $model = preg_replace('/\s+/', ' ', $model) ?? $model;

        return match ($model) {
            'gemini 3.8 flash', 'gemini-3.8-flash' => 'gemini-3.8-flash',
            'gemini 3.8 flash lite', 'gemini-3.8-flash-lite' => 'gemini-3.8-flash-lite',
            'gemini 2.5 flash', 'gemini-2.5-flash' => 'gemini-3.8-flash',
            'gemini 2.5 flash lite', 'gemini-2.5-flash-lite' => 'gemini-3.8-flash-lite',
            'gemini 2.0 flash', 'gemini-2.0-flash' => 'gemini-2.0-flash',
            'gemini 1.5 flash', 'gemini-1.5-flash' => 'gemini-1.5-flash',
            'gemini 1.5 flash latest', 'gemini-1.5-flash-latest' => 'gemini-1.5-flash-latest',
            default => $model,
        };
    }
}
