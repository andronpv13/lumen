<?php
/**
 * Подключение к базе данных через PDO
 */

if (!function_exists('db')) {
    function db(): ?PDO {
        static $instance = null;
        
        if ($instance === null) {
            try {
                $config = require __DIR__ . '/../config.php';
                $cfg = $config['db'];
                $dsn = "mysql:host={$cfg['host']};dbname={$cfg['dbname']};charset={$cfg['charset']}";
                
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false,
                ];
                
                $instance = new PDO($dsn, $cfg['user'], $cfg['pass'], $options);
                
            } catch (PDOException $e) {
                // Логирование ошибки
                error_log('DB Connection Error: ' . $e->getMessage());
                
                // В режиме отладки показываем детальную ошибку
                $appConfig = require __DIR__ . '/../config.php';
                if (($appConfig['debug'] ?? false) || ($appConfig['app_env'] ?? 'production') !== 'production') {
                    die('Ошибка подключения к БД: ' . htmlspecialchars($e->getMessage()));
                }
                
                // В production — общее сообщение
                http_response_code(500);
                die('Ошибка подключения к базе данных');
            }
        }
        
        return $instance;
    }
}