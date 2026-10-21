<?php

declare(strict_types=1);

abstract class AIProvider
{
    protected string $name;

    public function __construct(protected array $config)
    {
        $this->name = static::providerName();
    }

    public static function providerName(): string
    {
        return strtolower((new ReflectionClass(static::class))->getShortName());
    }

    abstract public function recognize(string $base64Image): array;

    abstract public function testConnection(): array;

    protected function buildPrompt(): string
    {
        return "Identify the main object in the image and return valid JSON only. " .
            "Use the exact schema: {\"objectLabel\":\"string\",\"productName\":\"string\",\"manufacturer\":\"string\",\"specification\":\"string\",\"description\":\"string\",\"boundingBox\":{\"ymin\":0,\"xmin\":0,\"ymax\":1000,\"xmax\":1000},\"confidence\":0.0}. " .
            "Do not invent exact product models when the image does not clearly show enough evidence. " .
            "Use a generic product category if needed, such as 'Apple iPhone' instead of a specific model name. " .
            "Only include numbers between 0 and 1000 for bounding box coordinates. " .
            "Keep confidence between 0 and 1. " .
            "If the product is unclear, return a generic but honest result.";
    }

    protected function normalizeResponse(array $payload): array
    {
        $objectLabel = trim((string)($payload['objectLabel'] ?? 'object'));
        $productName = trim((string)($payload['productName'] ?? 'Unknown product'));
        $manufacturer = trim((string)($payload['manufacturer'] ?? 'Unknown'));
        $specification = trim((string)($payload['specification'] ?? 'Unavailable'));
        $description = trim((string)($payload['description'] ?? 'No description available.'));

        $box = $payload['boundingBox'] ?? [];
        $normalizedBox = [
            'ymin' => (float)($box['ymin'] ?? 0),
            'xmin' => (float)($box['xmin'] ?? 0),
            'ymax' => (float)($box['ymax'] ?? 1000),
            'xmax' => (float)($box['xmax'] ?? 1000),
        ];

        $confidence = (float)($payload['confidence'] ?? 0.0);

        return [
            'objectLabel' => $objectLabel === '' ? 'object' : $objectLabel,
            'productName' => $productName === '' ? 'Unknown product' : $productName,
            'manufacturer' => $manufacturer === '' ? 'Unknown' : $manufacturer,
            'specification' => $specification === '' ? 'Unavailable' : $specification,
            'description' => $description === '' ? 'No description available.' : $description,
            'boundingBox' => ProductResult::normalizeBox($normalizedBox),
            'confidence' => ProductResult::normalizeConfidence($confidence),
        ];
    }

    protected function parseJsonFromText(string $text): ?array
    {
        $text = trim($text);
        if ($text === '') {
            return null;
        }

        $jsonStart = strpos($text, '{');
        $jsonEnd = strrpos($text, '}');
        if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
            $candidate = substr($text, $jsonStart, $jsonEnd - $jsonStart + 1);
            $decoded = json_decode($candidate, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        $decoded = json_decode($text, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        return null;
    }
}
