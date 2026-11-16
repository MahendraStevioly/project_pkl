<?php

namespace Tests\Feature\Models;

use App\Models\User;
use Dotenv\Dotenv;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

abstract class PostgresFeatureTestCase extends TestCase
{
    private string $originalDefaultConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalDefaultConnection = DB::getDefaultConnection();

        $environmentPath = base_path('.env');

        if (! is_file($environmentPath)) {
            throw new LogicException('PostgreSQL model tests require the configured .env file.');
        }

        // PHPUnit's SQLite defaults override the PostgreSQL database name.
        $environment = Dotenv::parse((string) file_get_contents($environmentPath));
        $pgsqlConfig = config('database.connections.pgsql');

        if (! isset($environment['DB_DATABASE']) && ! isset($environment['DB_URL'])) {
            throw new LogicException('PostgreSQL model tests require DB_DATABASE or DB_URL.');
        }

        $pgsqlConfig['database'] = $environment['DB_DATABASE'] ?? $pgsqlConfig['database'];
        $pgsqlConfig['url'] = $environment['DB_URL'] ?? null;

        Config::set('database.connections.pgsql', $pgsqlConfig);
        DB::setDefaultConnection('pgsql');
        DB::purge('pgsql');
        DB::connection('pgsql')->beginTransaction();
    }

    protected function tearDown(): void
    {
        $connection = DB::connection('pgsql');

        if ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }

        DB::purge('pgsql');
        DB::setDefaultConnection($this->originalDefaultConnection);

        parent::tearDown();
    }

    protected function userWithRole(string $role): User
    {
        $user = User::factory()->create();
        $user->forceFill(['role' => $role])->save();

        return $user;
    }
}
