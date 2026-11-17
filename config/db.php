<?php
/**
 * Database Configuration & Connection
 * The ALT HQ
 */

// Allow local environment override (e.g. on live dev/prod hosting servers)
if (file_exists(__DIR__ . '/db.local.php')) {
    require_once __DIR__ . '/db.local.php';
}

if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: 'root');
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: 'the_alt_hq');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

function getDB() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $host = trim(DB_HOST);
    $user = trim(DB_USER);
    $pass = trim((string)DB_PASS);
    $name = trim(DB_NAME);

    $dsn = "mysql:host=" . $host . ";dbname=" . $name . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
    } catch (PDOException $e) {
        // If database does not exist, attempt to initialize it automatically
        if ($e->getCode() == 1049) {
            initDatabase();
            $pdo = new PDO($dsn, $user, $pass, $options);
        } else {
            throw $e;
        }
    }

    ensureTablesExist($pdo);

    return $pdo;
}

function ensureTablesExist($pdo) {
    try {
        $pdo->query("SELECT 1 FROM `admin_users` LIMIT 1");
    } catch (Exception $e) {
        $schemaFile = __DIR__ . '/../database/schema.sql';
        if (file_exists($schemaFile)) {
            $sql = file_get_contents($schemaFile);
            $pdo->exec($sql);
        }
    }
}

function initDatabase() {
    $host = trim(DB_HOST);
    $user = trim(DB_USER);
    $pass = trim((string)DB_PASS);
    $name = trim(DB_NAME);

    $dsn = "mysql:host=" . $host . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `" . $name . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `" . $name . "`;");

    $schemaFile = __DIR__ . '/../database/schema.sql';
    if (file_exists($schemaFile)) {
        $sql = file_get_contents($schemaFile);
        $pdo->exec($sql);
    }
}

