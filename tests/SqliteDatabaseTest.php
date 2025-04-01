<?php

use PicoDb\Database;

class SqliteDatabaseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PicoDb\Database
     */
    private $db;

    public function setUp(): void
    {
        $this->db = new Database(array('driver' => 'sqlite', 'filename' => ':memory:'));
    }

    public function testEscapeIdentifer()
    {
        $this->assertEquals('"a"', $this->db->escapeIdentifier('a'));
        $this->assertEquals('a.b', $this->db->escapeIdentifier('a.b'));
        $this->assertEquals('"c"."a"', $this->db->escapeIdentifier('a', 'c'));
        $this->assertEquals('a.b', $this->db->escapeIdentifier('a.b', 'c'));
        $this->assertEquals('SELECT COUNT(*) FROM test', $this->db->escapeIdentifier('SELECT COUNT(*) FROM test'));
        $this->assertEquals('SELECT COUNT(*) FROM test', $this->db->escapeIdentifier('SELECT COUNT(*) FROM test', 'b'));
    }

    public function testEscapeIdentiferList()
    {
        $this->assertEquals(array('"c"."a"', '"c"."b"'), $this->db->escapeIdentifierList(array('a', 'b'), 'c'));
        $this->assertEquals(array('"a"', 'd.b'), $this->db->escapeIdentifierList(array('a', 'd.b')));
    }

    public function testThatPreparedStatementWorks()
    {
        $this->db->getConnection()->exec('CREATE TABLE foobar (id INTEGER PRIMARY KEY, something TEXT)');
        $this->db->execute('INSERT INTO foobar (something) VALUES (?)', array('a'));
        $this->assertEquals(1, $this->db->getLastId());
        $this->assertEquals('a', $this->db->execute('SELECT something FROM foobar WHERE something=?', array('a'))->fetchColumn());
    }

    public function testBadSQLQuery()
    {
        $this->expectException(\PicoDb\SQLException::class);

        $this->db->execute('INSERT INTO foobar');
    }

    public function testDuplicateKey()
    {
        $this->expectException(\PicoDb\SQLException::class);

        $this->db->getConnection()->exec('CREATE TABLE foobar (something TEXT UNIQUE)');

        $this->assertNotFalse($this->db->execute('INSERT INTO foobar (something) VALUES (?)', array('a')));
        $this->db->execute('INSERT INTO foobar (something) VALUES (?)', array('a'));
    }

    public function testThatTransactionReturnsAValue()
    {
        $this->assertEquals('a', $this->db->transaction(function (Database $db) {
            $db->getConnection()->exec('CREATE TABLE foobar (something TEXT UNIQUE)');
            $db->execute('INSERT INTO foobar (something) VALUES (?)', array('a'));

            return $db->execute('SELECT something FROM foobar WHERE something=?', array('a'))->fetchColumn();
        }));
    }

    public function testThatTransactionReturnsTrue()
    {
        $this->assertTrue($this->db->transaction(function (Database $db) {
            $db->getConnection()->exec('CREATE TABLE foobar (something TEXT UNIQUE)');
            $db->execute('INSERT INTO foobar (something) VALUES (?)', array('a'));
        }));
    }

    public function testThatTransactionThrowExceptionWhenRollbacked()
    {
        $this->expectException(\PicoDb\SQLException::class);

        $this->assertFalse($this->db->transaction(function (Database $db) {
            $db->getConnection()->exec('CREATE TABL');
        }));
    }

    public function testThatTransactionReturnsFalseWhithDuplicateKey()
    {
        $this->expectException(\PicoDb\SQLException::class);

        $this->db->transaction(function (Database $db) {
            $db->getConnection()->exec('CREATE TABLE foobar (something TEXT UNIQUE)');
            $r1 = $db->execute('INSERT INTO foobar (something) VALUES (?)', array('a'));
            $r2 = $db->execute('INSERT INTO foobar (something) VALUES (?)', array('a'));
            return $r1 && $r2;
        });
    }

    public function testGetInstance()
    {
        Database::setInstance('main', function () {
            return new Database(array('driver' => 'sqlite', 'filename' => ':memory:'));
        });

        $instance1 = Database::getInstance('main');
        $instance2 = Database::getInstance('main');

        $this->assertInstanceOf('PicoDb\Database', $instance1);
        $this->assertInstanceOf('PicoDb\Database', $instance2);
        $this->assertTrue($instance1 === $instance2);
    }

    public function testGetMissingInstance()
    {
        $this->expectException(\LogicException::class);

        Database::getInstance('notfound');
    }
}
