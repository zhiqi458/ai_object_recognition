<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/AppConfig.php';
require_once __DIR__ . '/../includes/ImageValidator.php';
require_once __DIR__ . '/../includes/ObjectRecognizer.php';

try {
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
        echo json_encode(['ok' => false, 'error' => 'Invalid request payload.']);
        exit;
    }

    $image = (string)($payload['image'] ?? '');
    $validation = ImageValidator::validateBase64Image($image);
    if (($validation['ok'] ?? false) !== true) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => $validation['error'] ?? 'Invalid image.']);
        exit;
    }

    $config = AppConfig::load();
    $recognizer = new ObjectRecognizer($config);
    $result = $recognizer->recognize($validation['data']);

    if (($result['ok'] ?? false) !== true) {
        http_response_code(422);
        echo json_encode(['ok' => false, 'error' => $result['error'] ?? 'Unable to identify the object.']);
        exit;
    }

    $responseData = $result['data'];

    http_response_code(200);
    echo json_encode(['ok' => true, 'data' => $responseData]);
} catch (Throwable $exception) {
    error_log('Recognition API error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Unable to identify the object.']);
}
