<?php

use PicoDb\Driver\Sqlite;

class SqliteDriverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PicoDb\Driver\Sqlite
     */
    private $driver;

    public function setUp(): void
    {
        $this->driver = new Sqlite(array('filename' => ':memory:'));
    }

    public function testMissingRequiredParameter()
    {
        $this->expectException(LogicException::class);

        new Sqlite(array());
    }

    public function testDuplicateKeyError()
    {
        $this->assertFalse($this->driver->isDuplicateKeyError(1234));
        $this->assertTrue($this->driver->isDuplicateKeyError(23000));
    }

    public function testOperator()
    {
        $this->assertEquals('LIKE', $this->driver->getOperator('LIKE'));
        $this->assertEquals('LIKE', $this->driver->getOperator('ILIKE'));
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

        $this->driver->getConnection()->exec('CREATE TABLE foobar (id INTEGER PRIMARY KEY, something TEXT)');
        $this->driver->getConnection()->exec('INSERT INTO foobar (something) VALUES (1)');

        $this->assertEquals(1, $this->driver->getLastId());
    }

    public function testEscape()
    {
        $this->assertEquals('"foobar"', $this->driver->escape('foobar'));
    }

    public function testDatabaseVersion()
    {
        $this->assertStringStartsWith('3.', $this->driver->getDatabaseVersion());
    }
}
