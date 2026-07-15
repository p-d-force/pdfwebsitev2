<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;
use PDOStatement;
use RuntimeException;

/**
 * Database — thin PDO wrapper with connection pooling, query logging, and TTL cache.
 *
 * Usage:
 *   $row  = Database::fetch('SELECT ...', [$id]);
 *   $rows = Database::fetchAll('SELECT ...');
 *   $id   = Database::insert('INSERT ...', [...]);
 *   $n    = Database::execute('UPDATE ...', [...]);
 *   Database::beginTransaction(); ... Database::commit();
 */
class Database
{
    private static ?PDO $instance = null;
    private static array $queryLog = [];
    private static int $queryCount = 0;
    private static array $cache = [];

    /** Get or create the PDO connection. */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                DB_HOST,
                DB_PORT,
                DB_NAME,
                DB_CHARSET
            );

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASSWORD, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                ]);
            } catch (PDOException $e) {
                throw new RuntimeException('Database connection failed: ' . $e->getMessage(), 500, $e);
            }
        }
        return self::$instance;
    }

    /** Execute a prepared statement and return the PDOStatement. */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $start = microtime(true);
        $stmt = self::getInstance()->prepare($sql);
        $stmt->execute($params);
        $elapsed = round((microtime(true) - $start) * 1000, 2);
        self::$queryCount++;
        self::$queryLog[] = ['sql' => $sql, 'params' => $params, 'ms' => $elapsed];
        return $stmt;
    }

    /** Fetch a single row or null. */
    public static function fetch(string $sql, array $params = []): ?array
    {
        $result = self::query($sql, $params)->fetch();
        return $result !== false ? $result : null;
    }

    /** Fetch all rows as an array of associative arrays. */
    public static function fetchAll(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /** Fetch with simple TTL cache. Use for read-heavy, slow-changing queries. */
    public static function fetchAllCached(string $sql, array $params = [], int $ttl = 30): array
    {
        $key = md5($sql . serialize($params));
        if (isset(self::$cache[$key]) && (time() - self::$cache[$key]['at']) < $ttl) {
            return self::$cache[$key]['data'];
        }
        $data = self::fetchAll($sql, $params);
        self::$cache[$key] = ['data' => $data, 'at' => time()];
        return $data;
    }

    /** Fetch a single column value. */
    public static function fetchColumn(string $sql, array $params = [], int $column = 0): mixed
    {
        $stmt = self::query($sql, $params);
        $val = $stmt->fetchColumn($column);
        return $val !== false ? $val : null;
    }

    /** Cached fetchColumn. */
    public static function fetchColumnCached(string $sql, array $params = [], int $column = 0, int $ttl = 30): mixed
    {
        $key = md5($sql . serialize($params) . $column);
        if (isset(self::$cache[$key]) && (time() - self::$cache[$key]['at']) < $ttl) {
            return self::$cache[$key]['data'];
        }
        $val = self::fetchColumn($sql, $params, $column);
        self::$cache[$key] = ['data' => $val, 'at' => time()];
        return $val;
    }

    /** Insert and return the new auto-increment ID. */
    public static function insert(string $sql, array $params = []): string
    {
        self::query($sql, $params);
        return self::getInstance()->lastInsertId();
    }

    /** Execute a statement and return affected row count. */
    public static function execute(string $sql, array $params = []): int
    {
        return self::query($sql, $params)->rowCount();
    }

    public static function beginTransaction(): void
    {
        self::getInstance()->beginTransaction();
    }

    public static function commit(): void
    {
        self::getInstance()->commit();
    }

    public static function rollback(): void
    {
        if (self::getInstance()->inTransaction()) {
            self::getInstance()->rollBack();
        }
    }

    /** Invalidate all cached queries. */
    public static function clearCache(): void
    {
        self::$cache = [];
    }

    /** Total queries executed this request. */
    public static function getQueryCount(): int
    {
        return self::$queryCount;
    }

    /** Query log for debugging: [sql, params, ms]. */
    public static function getQueryLog(): array
    {
        return self::$queryLog;
    }
}
