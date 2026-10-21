<?php

declare(strict_types=1);

final class AppConfig
{
    public static function load(): array
    {
        $projectRoot = dirname(__DIR__);
        $configFile = $projectRoot . '/config/config.php';
        $exampleFile = $projectRoot . '/config/config.example.php';

        if (is_file($configFile)) {
            $config = require $configFile;
            return is_array($config) ? $config : [];
        }

        if (is_file($exampleFile)) {
            $config = require $exampleFile;
            return is_array($config) ? $config : [];
        }

        return [];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $config = self::load();
        return $config[$key] ?? $default;
    }

    public static function save(array $config): void
    {
        $projectRoot = dirname(__DIR__);
        $path = $projectRoot . '/config/config.php';
        $content = "<?php\n\ndeclare(strict_types=1);\n\nreturn " . var_export($config, true) . ";\n";
        file_put_contents($path, $content);
    }

    public static function sanitizeForClient(array $config): array
    {
        $safe = $config;
        $safe['gemini_api_key'] = $safe['gemini_api_key'] !== '' ? 'Key saved' : 'Not configured';
        $safe['agnes_api_key'] = $safe['agnes_api_key'] !== '' ? 'Key saved' : 'Not configured';
        return $safe;
    }
}
