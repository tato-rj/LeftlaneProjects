<?php

namespace Tests;

use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        // An optional Unix socket enables a second pass against an isolated
        // temporary MySQL server. Never read/use production database settings.
        $testSocket = getenv('QUICKREADS_TEST_MYSQL_SOCKET');
        if ($testSocket) {
            if (!str_starts_with($testSocket, '/private/tmp/quickreads-v2-mysql-')) {
                throw new \RuntimeException('Only an isolated /private/tmp/quickreads-v2-mysql- socket is permitted.');
            }
            $pdo = new \PDO('mysql:unix_socket='.$testSocket, 'root', '');
            $suffix = bin2hex(random_bytes(6));
            $databases = [];
            foreach (['quickreads', 'videouploader', 'leftlaneapps'] as $connection) {
                $database = 'qr_v2_test_'.$suffix.'_'.$connection;
                $pdo->exec('CREATE DATABASE `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
                $databases[] = $database;
                $app['config']->set('database.connections.'.$connection, [
                    'driver' => 'mysql', 'unix_socket' => $testSocket, 'database' => $database,
                    'username' => 'root', 'password' => '', 'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci', 'prefix' => '', 'strict' => false,
                ]);
            }
            $app['config']->set('database.default', 'leftlaneapps');
            $this->beforeApplicationDestroyed(function () use ($pdo, $databases) {
                foreach ($databases as $database) $pdo->exec('DROP DATABASE `'.$database.'`');
            });
        } else {
            // Isolate every named connection before DatabaseMigrations runs.
            foreach (['quickreads', 'videouploader', 'leftlaneapps'] as $connection) {
                $app['config']->set('database.connections.'.$connection, [
                    'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
                ]);
            }
        }
        foreach (['quickreads', 'videouploader'] as $project) {
            $app['migrator']->path(database_path('migrations/'.$project));
        }
        $app['config']->set('mail.driver', 'array');
        $app['config']->set('mail.default', 'array');
        $app['config']->set('mail.mailers.array', ['transport' => 'array']);

        // Emulate MySQL functions used by untouched legacy projections and v2
        // duration validation; production remains MySQL/utf8mb4_unicode_ci.
        if (!$testSocket) {
            $pdo = $app['db']->connection('quickreads')->getPdo();
            $pdo->sqliteCreateFunction('CONCAT', function (...$parts) {
                return in_array(null, $parts, true) ? null : implode('', $parts);
            });
            $pdo->sqliteCreateFunction('REGEXP', fn ($pattern, $value) => preg_match('/'.$pattern.'/uD', $value ?? ''));
        }

        Hash::setRounds(4);

        return $app;
    }
}
