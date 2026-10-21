<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/AppConfig.php';
require_once __DIR__ . '/../includes/ObjectRecognizer.php';

try {
    $config = AppConfig::load();
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

    if ($method === 'GET') {
        echo json_encode(['ok' => true, 'data' => AppConfig::sanitizeForClient($config)]);
        exit;
    }

    $rawBody = file_get_contents('php://input');
    if ($rawBody === '') {
        $payload = [];
    } else {
        try {
            $payload = json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'error' => 'Invalid request payload.']);
            exit;
        }
    }

    if (!is_array($payload)) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Invalid request.']);
        exit;
    }

    $action = $payload['action'] ?? 'save';

    if ($action === 'test') {
        $recognizer = new ObjectRecognizer($config);
        $results = [];
        foreach (['gemini', 'agnes'] as $provider) {
            try {
                $results[$provider] = $recognizer->testConnection($provider);
            } catch (Throwable $exception) {
                $results[$provider] = ['ok' => false, 'error' => 'Connection test failed.'];
            }
        }
        echo json_encode(['ok' => true, 'data' => $results]);
        exit;
    }

    $merged = $config;
    foreach (['ai_provider', 'gemini_api_key', 'gemini_model', 'gemini_fallback_models', 'agnes_api_key', 'agnes_api_url', 'agnes_model', 'agnes_fallback_models', 'request_timeout'] as $field) {
        if (array_key_exists($field, $payload)) {
            $merged[$field] = $payload[$field];
        }
    }

    $merged['request_timeout'] = max(10, min(20, (int)$merged['request_timeout']));
    AppConfig::save($merged);

    echo json_encode(['ok' => true, 'data' => AppConfig::sanitizeForClient($merged)]);
} catch (Throwable $exception) {
    error_log('Settings API error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Unable to save settings.']);
}
