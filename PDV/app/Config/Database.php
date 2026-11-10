<?php

declare(strict_types=1);

namespace PDV\Config;

use PDO;
use RuntimeException;
use Throwable;

final class Database
{
    private static ?PDO $connection = null;

    public static function getConnection(): PDO
    {
        if (self::$connection instanceof PDO) {
            return self::$connection;
        }

        $connectionString = self::getConnectionString(dirname(__DIR__, 2) . '/.env');
        $url = parse_url($connectionString);

        if ($url === false || empty($url['host'])) {
            throw new RuntimeException('A configuração da conexão com o banco de dados é inválida.');
        }

        $host = $url['host'];
        $port = $url['port'] ?? 5432;
        $user = $url['user'] ?? '';
        $password = $url['pass'] ?? '';
        $database = ltrim($url['path'] ?? '', '/');
        $endpointId = explode('.', $host)[0];
        $options = "endpoint=$endpointId --timezone=America/Cuiaba";
        $dsn = "pgsql:host=$host;port=$port;dbname=$database;sslmode=require;options='{$options}'";

        try {
            self::$connection = new PDO($dsn, $user, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch (Throwable $exception) {
            throw new RuntimeException('Não foi possível conectar ao banco de dados.', 0, $exception);
        }

        return self::$connection;
    }

    private static function getConnectionString(string $envPath): string
    {
        if (!is_readable($envPath)) {
            throw new RuntimeException('O arquivo de configuração do banco de dados não está disponível.');
        }

        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (preg_match('/^SRVDB\s*=\s*(.*)$/', trim($line), $matches)) {
                $connectionString = trim($matches[1], " \t\n\r\0\x0B\"'");
                if ($connectionString !== '') {
                    return $connectionString;
                }
            }
        }

        throw new RuntimeException('A configuração SRVDB não foi encontrada.');
    }
}