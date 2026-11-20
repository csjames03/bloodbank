<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;
use Throwable;

/**
 * Thin PDO wrapper: one lazy connection, prepared statements everywhere,
 * exceptions on error and a transaction helper.
 */
final class Database
{
    private static ?self $instance = null;

    private function __construct(private readonly PDO $pdo)
    {
    }

    public static function instance(): self
    {
        if (self::$instance === null) {
            $c = Config::get('db');
            // PDO::connect() returns the driver subclass (Pdo\Pgsql), which
            // adds getNotify() for LISTEN/NOTIFY-based live updates.
            $pdo = PDO::connect(
                sprintf('pgsql:host=%s;port=%d;dbname=%s', $c['host'], $c['port'], $c['database']),
                $c['username'],
                $c['password'],
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ],
            );
            $pdo->exec("SET TIME ZONE '" . str_replace("'", '', (string) Config::get('app.timezone')) . "'");
            self::$instance = new self($pdo);
        }

        return self::$instance;
    }

    /** Subscribe this connection to a PostgreSQL NOTIFY channel. */
    public function listen(string $channel): void
    {
        $this->pdo->exec('LISTEN ' . preg_replace('/[^a-z_]/', '', $channel));
    }

    /**
     * Wait up to `$timeoutMs` for a notification on a LISTENed channel.
     * Returns ['message' => channel, 'payload' => ...] or null on timeout
     * (or when the driver can't wait, in which case it simply sleeps).
     *
     * @return array<string, mixed>|null
     */
    public function waitForNotify(int $timeoutMs): ?array
    {
        if ($this->pdo instanceof \Pdo\Pgsql) {
            $n = $this->pdo->getNotify(PDO::FETCH_ASSOC, $timeoutMs);

            return is_array($n) ? $n : null;
        }

        usleep($timeoutMs * 1000);

        return null;
    }

    /** @param array<int|string, mixed> $params */
    public function run(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);

        return $statement;
    }

    /**
     * @param array<int|string, mixed> $params
     * @return list<array<string, mixed>>
     */
    public function all(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    /**
     * @param array<int|string, mixed> $params
     * @return array<string, mixed>|null
     */
    public function one(string $sql, array $params = []): ?array
    {
        $row = $this->run($sql, $params)->fetch();

        return $row === false ? null : $row;
    }

    /** @param array<int|string, mixed> $params */
    public function value(string $sql, array $params = []): mixed
    {
        $value = $this->run($sql, $params)->fetchColumn();

        return $value === false ? null : $value;
    }

    /**
     * Runs `$callback` inside a transaction; rolls back on any exception.
     *
     * @template T
     * @param callable(self): T $callback
     * @return T
     */
    public function transaction(callable $callback): mixed
    {
        $this->pdo->beginTransaction();
        try {
            $result = $callback($this);
            $this->pdo->commit();

            return $result;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }
}
