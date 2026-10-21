<?php

declare(strict_types=1);

require_once __DIR__ . '/AIProvider.php';
require_once __DIR__ . '/ProductResult.php';

final class AgnesVision extends AIProvider
{
    public function recognize(string $base64Image): array
    {
        $apiKey = (string)($this->config['agnes_api_key'] ?? '');
        $apiUrl = trim((string)($this->config['agnes_api_url'] ?? ''));
        
        $model = (string)($this->config['agnes_model'] ?? 'agnes-vision');

        if ($apiKey === '' || $apiUrl === '') {
            return ['ok' => false, 'error' => 'AI provider is not configured.'];
        }

        $payload = [
            'model' => $model,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $this->buildPrompt(),
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => [
                                'url' => 'data:image/jpeg;base64,' . $base64Image,
                            ],
                        ],
                    ],
                ],
            ],
            'max_tokens' => 1024,
        ];

        $timeout = $this->requestTimeoutSeconds();
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $error !== '') {
            return ['ok' => false, 'error' => 'Agnes connection failed: ' . $error];
        }

        $decoded = json_decode($body, true);
        if ($status >= 400 || !is_array($decoded)) {
            return ['ok' => false, 'error' => 'Agnes returned an invalid response.'];
        }

        $text = $this->extractResponseText($decoded);
        $json = $this->parseJsonFromText($text);
        if ($json === null) {
            return ['ok' => false,'error' => 'Agnes produced invalid output.'];
        }

        $normalized = $this->normalizeResponse($json);
        return [
            'ok' => true,
            'result' => ProductResult::fromArray($normalized, 'agnes')->toArray(),
            'provider' => 'agnes',
        ];
    }

    public function testConnection(): array
    {
        $apiKey = (string)($this->config['agnes_api_key'] ?? '');
        $apiUrl = trim((string)($this->config['agnes_api_url'] ?? ''));

        if ($apiKey === '' || $apiUrl === '') {
            return ['ok' => false, 'error' => 'Agnes API key or endpoint is not configured.'];
        }

        $payload = [
            'model' => (string)($this->config['agnes_model'] ?? 'agnes-vision'),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Please respond with a simple confirmation message.',
                ],
            ],
            'max_tokens' => 1024,
        ];

        $timeout = $this->requestTimeoutSeconds();
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($body === false || $error !== '') {
            return ['ok' => false, 'error' => 'Connection failed: ' . $error];
        }

        if ($status >= 400) {
            return ['ok' => false, 'error' => 'Connection failed with HTTP ' . $status];
        }

        return ['ok' => true, 'message' => 'Agnes connected successfully.'];
    }

    private function extractResponseText(array $response): string
    {
        if (is_string($response['result'] ?? null)) {
            return $response['result'];
        }

        $content = $response['choices'][0]['message']['content'] ?? null;
        if (is_string($content)) {
            return $content;
        }

        return json_encode($response) ?: '';
    }

    private function requestTimeoutSeconds(): int
    {
        $timeout = (int)($this->config['request_timeout'] ?? 15);
        return max(10, min(15, $timeout));
    }
}
