<?php

use PicoDb\Driver\Postgres;

class PostgresDriverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PicoDb\Driver\Postgres
     */
    private $driver;

    public function setUp(): void
    {
        $this->driver = new Postgres(array('hostname' => getenv('POSTGRES_HOST'), 'username' => 'root', 'password' => 'rootpassword', 'database' => 'picodb'));
        $this->driver->getConnection()->exec('DROP TABLE IF EXISTS foo');
        $this->driver->getConnection()->exec('DROP TABLE IF EXISTS foobar');
        $this->driver->getConnection()->exec('DROP TABLE IF EXISTS schema_version');
    }

    public function tearDown(): void
    {
        $this->driver->closeConnection();
    }

    public function testMissingRequiredParameter()
    {
        $this->expectException(LogicException::class);

        new Postgres(array());
    }

    public function testDuplicateKeyError()
    {
        $this->assertFalse($this->driver->isDuplicateKeyError(1234));
        $this->assertTrue($this->driver->isDuplicateKeyError(23505));
        $this->assertTrue($this->driver->isDuplicateKeyError(23503));
    }

    public function testOperator()
    {
        $this->assertEquals('LIKE', $this->driver->getOperator('LIKE'));
        $this->assertEquals('ILIKE', $this->driver->getOperator('ILIKE'));
        $this->assertEquals('', $this->driver->getOperator('FOO'));
    }

    public function testSchemaVersion()
    {
        $this->assertEquals(0, $this->driver->getSchemaVersion());

        $this->driver->setSchemaVersion(1);
        $this->assertEquals(1, $this->driver->getSchemaVersion());

        $this->driver->setSchemaVersion(42);
        $this->assertEquals(42, $this->driver->getSchemaVersion());
    }

    public function testLastInsertId()
    {
        $this->assertEquals(0, $this->driver->getLastId());

        $this->driver->getConnection()->exec('CREATE TABLE foobar (id serial PRIMARY KEY, something TEXT)');
        $this->driver->getConnection()->exec('INSERT INTO foobar (something) VALUES (1)');

        $this->assertEquals(1, $this->driver->getLastId());
    }

    public function testEscape()
    {
        $this->assertEquals('"foobar"', $this->driver->escape('foobar'));
    }

//    public function testDatabaseVersion()
//    {
//        $this->assertStringStartsWith('11.', $this->driver->getDatabaseVersion());
//    }
}
