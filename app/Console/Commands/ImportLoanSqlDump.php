<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class ImportLoanSqlDump extends Command
{
    protected $signature = 'loan-management:import-sql
        {path? : Full path to s_loanmanagement.sql}
        {--mysql= : Full path to mysql/mysql.exe}
        {--force : Import without confirmation}';

    protected $description = 'Import an existing Loan Management SQL dump into the configured DB_LOAN_DATABASE.';

    public function handle(): int
    {
        $path = $this->argument('path') ?: 'C:\\Users\\CHHOEUNREACH\\Desktop\\s_loanmanagement.sql';
        $path = $this->normalizePath((string) $path);

        if (! is_file($path)) {
            $this->error('SQL dump not found: '.$path);

            return self::FAILURE;
        }

        $cfg = Config::get('database.connections.mysql_loan');
        $database = (string) ($cfg['database'] ?? '');
        if ($database === '') {
            $this->error('DB_LOAN_DATABASE is not configured.');

            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Import {$path} into database [{$database}]? Existing rows may be replaced.", false)) {
            return self::SUCCESS;
        }

        $this->ensureDatabaseExists($cfg, $database);

        $mysql = (string) ($this->option('mysql') ?: 'mysql');
        $cmd = [
            $mysql,
            '-h'.($cfg['host'] ?? '127.0.0.1'),
            '-P'.($cfg['port'] ?? '3306'),
            '-u'.($cfg['username'] ?? 'root'),
            $database,
        ];

        $password = (string) ($cfg['password'] ?? '');
        if ($password !== '') {
            $cmd[] = '-p'.$password;
        }

        $this->info('Importing SQL dump. Large files can take several minutes...');

        $descriptors = [
            0 => ['file', $path, 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($cmd, $descriptors, $pipes);
        if (! is_resource($process)) {
            $this->error('Unable to start mysql client.');

            return self::FAILURE;
        }

        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $status = proc_close($process);
        if ($status !== 0) {
            $this->error(trim($error) ?: 'mysql import failed.');

            return self::FAILURE;
        }

        if (trim((string) $output) !== '') {
            $this->line(trim((string) $output));
        }

        $this->info('SQL dump imported successfully.');

        return self::SUCCESS;
    }

    private function ensureDatabaseExists(array $cfg, string $database): void
    {
        $host = (string) ($cfg['host'] ?? '127.0.0.1');
        $port = (int) ($cfg['port'] ?? 3306);
        $username = (string) ($cfg['username'] ?? 'root');
        $password = (string) ($cfg['password'] ?? '');
        $charset = (string) ($cfg['charset'] ?? 'utf8mb4');
        $collation = (string) ($cfg['collation'] ?? 'utf8mb4_unicode_ci');
        $safeDb = str_replace('`', '``', $database);

        $pdo = new \PDO("mysql:host={$host};port={$port}", $username, $password, [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$safeDb}` CHARACTER SET {$charset} COLLATE {$collation}");
    }

    private function normalizePath(string $path): string
    {
        if (preg_match('/^[A-Za-z]:\\\\/', $path) === 1 && DIRECTORY_SEPARATOR === '/') {
            $drive = strtolower($path[0]);
            $rest = str_replace('\\', '/', substr($path, 2));

            return '/mnt/'.$drive.$rest;
        }

        return $path;
    }
}
