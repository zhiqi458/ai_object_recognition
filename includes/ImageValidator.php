<?php

declare(strict_types=1);

final class ImageValidator
{
    public static function validateBase64Image(string $imageData): array
    {
        $trimmedValue = trim($imageData);
        if ($trimmedValue === '') {
            return ['ok' => false, 'error' => 'Empty image data.'];
        }

        $dataUrlPattern = '/^data:(image\/(png|jpeg|jpg|webp));base64,/i';
        if (preg_match($dataUrlPattern, $trimmedValue, $matches) === 1) {
            $trimmedValue = preg_replace($dataUrlPattern, '', $trimmedValue);
        }

        if (!preg_match('/^[A-Za-z0-9+\/]+={0,2}$/', $trimmedValue)) {
            return ['ok' => false, 'error' => 'Invalid image payload.'];
        }

        $decoded = base64_decode($trimmedValue, true);
        if ($decoded === false) {
            return ['ok' => false, 'error' => 'Invalid Base64 image data.'];
        }

        if (strlen($decoded) > 10 * 1024 * 1024) {
            return ['ok' => false, 'error' => 'Image is too large.'];
        }

        $image = @imagecreatefromstring($decoded);
        if ($image === false) {
            return ['ok' => false, 'error' => 'The uploaded image could not be decoded.'];
        }

        imagedestroy($image);

        return ['ok' => true, 'data' => $trimmedValue];
    }
}
