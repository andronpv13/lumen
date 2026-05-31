<?php
/**
 * Конфигурация приложения Lumen
 * Чувствительные данные загружаются из переменных окружения
 */

// Загрузка переменных окружения из .env файла (если существует)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos(trim($line), '#') !== 0) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            // Убираем кавычки если есть
            $value = trim($value, '\'"');
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

return [
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'dbname' => getenv('DB_NAME') ?: 'lumen_shop',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'upload_dir' => __DIR__ . '/uploads/',
    'upload_url' => '/uploads/',
    'items_per_page' => 12,
    'app_env' => getenv('APP_ENV') ?: 'production',
    'debug' => (getenv('APP_DEBUG') === 'true'),
];