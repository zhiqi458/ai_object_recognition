<?php

declare(strict_types=1);

require_once __DIR__ . '/AppConfig.php';
require_once __DIR__ . '/GeminiVision.php';
require_once __DIR__ . '/AgnesVision.php';

final class ObjectRecognizer
{
    public function __construct(private array $config)
    {
    }

    public function recognize(string $base64Image): array
    {
        $providerOrder = $this->providerOrder();
        $lastError = 'Unable to identify the object.';

        foreach ($providerOrder as $providerName) {
            $provider = $this->createProvider($providerName);
            if ($provider === null) {
                continue;
            }

            $response = $provider->recognize($base64Image);
            if (($response['ok'] ?? false) === true && isset($response['result'])) {
                $result = $response['result'];
                $result['provider'] = $providerName;
                return ['ok' => true, 'data' => $result];
            }

            if (isset($response['error'])) {
                $lastError = $response['error'];
            }
        }

        return ['ok' => false, 'error' => $lastError];
    }

    public function testConnection(string $provider): array
    {
        $providerInstance = $this->createProvider($provider);
        if ($providerInstance === null) {
            return ['ok' => false, 'error' => 'Unsupported AI provider.'];
        }

        return $providerInstance->testConnection();
    }

    private function providerOrder(): array
    {
        $selection = strtolower((string)($this->config['ai_provider'] ?? 'auto'));

        if ($selection === 'gemini') {
            return ['gemini'];
        }
        if ($selection === 'agnes') {
            return ['agnes'];
        }

        return ['gemini', 'agnes'];
    }

    private function createProvider(string $providerName): ?AIProvider
    {
        $providerName = strtolower(trim($providerName));

        if ($providerName === 'gemini') {
            return new GeminiVision($this->config);
        }

        if ($providerName === 'agnes') {
            return new AgnesVision($this->config);
        }

        return null;
    }
}
