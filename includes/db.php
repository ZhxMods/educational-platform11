<?php
/**
 * Database Layer — PDO singleton
 * Compatible: PHP 7.4 / 8.0  |  InfinityFree MySQL
 *
 * Usage:
 *   $db  = db();
 *   $row = db_row($sql, [...]);
 *   $all = db_all($sql, [...]);
 *   db_run($sql, [...]);
 *   $id  = db_last_id();
 */

declare(strict_types=1);

if (!defined('DB_HOST')) {
    require_once __DIR__ . '/../config.php';
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;dbname=%s;charset=%s',
            DB_HOST, DB_NAME, DB_CHARSET
        );
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('[DB] Connection failed: ' . $e->getMessage());
            http_response_code(500);
            exit('Database connection error. Please try again later.');
        }
    }
    return $pdo;
}

function db_row(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return ($row !== false) ? $row : null;
}

function db_all(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function db_run(string $sql, array $params = []): PDOStatement
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

function db_last_id(): string
{
    return db()->lastInsertId();
}
