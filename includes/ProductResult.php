<?php

declare(strict_types=1);

final class ProductResult
{
    public function __construct(
        public string $objectLabel,
        public string $productName,
        public string $manufacturer,
        public string $specification,
        public string $description,
        public array $boundingBox,
        public float $confidence,
        public string $provider = 'unknown'
    ) {
    }

    public static function fromArray(array $data, string $provider = 'unknown'): self
    {
        $box = [
            'ymin' => (float)($data['boundingBox']['ymin'] ?? 0),
            'xmin' => (float)($data['boundingBox']['xmin'] ?? 0),
            'ymax' => (float)($data['boundingBox']['ymax'] ?? 1000),
            'xmax' => (float)($data['boundingBox']['xmax'] ?? 1000),
        ];

        return new self(
            (string)($data['objectLabel'] ?? 'object'),
            (string)($data['productName'] ?? 'Unknown product'),
            (string)($data['manufacturer'] ?? 'Unknown'),
            (string)($data['specification'] ?? 'Unavailable'),
            (string)($data['description'] ?? 'No description available.'),
            self::normalizeBox($box),
            self::normalizeConfidence((float)($data['confidence'] ?? 0.0)),
            $provider
        );
    }

    public static function normalizeBox(array $box): array
    {
        $normalized = [
            'ymin' => self::clampNumber((float)($box['ymin'] ?? 0), 0, 1000),
            'xmin' => self::clampNumber((float)($box['xmin'] ?? 0), 0, 1000),
            'ymax' => self::clampNumber((float)($box['ymax'] ?? 1000), 0, 1000),
            'xmax' => self::clampNumber((float)($box['xmax'] ?? 1000), 0, 1000),
        ];

        if ($normalized['ymax'] < $normalized['ymin']) {
            [$normalized['ymin'], $normalized['ymax']] = [$normalized['ymax'], $normalized['ymin']];
        }
        if ($normalized['xmax'] < $normalized['xmin']) {
            [$normalized['xmin'], $normalized['xmax']] = [$normalized['xmax'], $normalized['xmin']];
        }

        return $normalized;
    }

    public static function normalizeConfidence(float $confidence): float
    {
        if ($confidence < 0) {
            return 0.0;
        }
        if ($confidence > 1) {
            return 1.0;
        }

        return (float) round($confidence, 4);
    }

    public function toArray(): array
    {
        return [
            'objectLabel' => $this->objectLabel,
            'productName' => $this->productName,
            'manufacturer' => $this->manufacturer,
            'specification' => $this->specification,
            'description' => $this->description,
            'boundingBox' => $this->boundingBox,
            'confidence' => $this->confidence,
            'provider' => $this->provider,
        ];
    }

    private static function clampNumber(float $value, float $min, float $max): float
    {
        if ($value < $min) {
            return $min;
        }
        if ($value > $max) {
            return $max;
        }

        return $value;
    }
}
