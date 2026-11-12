<?php

namespace Tests\Feature\Infrastructure;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Infrastructure check: tests reach the isolated MySQL 8.4 test database
 * configured with utf8mb4 and InnoDB.
 */
class DatabaseConnectionTest extends TestCase
{
    public function test_tests_use_the_isolated_mysql_test_database(): void
    {
        $connection = DB::connection();

        $this->assertSame('mysql', $connection->getDriverName());
        $this->assertSame('krishibondhu_test', $connection->getDatabaseName());
        $this->assertSame('krishibondhu_test', $connection->selectOne('select database() as name')->name);
    }

    public function test_mysql_server_and_schema_settings(): void
    {
        $server = DB::selectOne('select version() as version, @@default_storage_engine as engine');
        $schema = DB::selectOne(
            'select default_character_set_name as charset from information_schema.schemata where schema_name = database()'
        );

        $this->assertStringStartsWith('8.4.', $server->version);
        $this->assertSame('InnoDB', $server->engine);
        $this->assertSame('utf8mb4', $schema->charset);
    }
}
