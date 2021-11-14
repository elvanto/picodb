<?php

use PicoDb\Database;

class PostgresDatabaseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PicoDb\Database
     */
    private $db;

    public function setUp()
    {
        $this->db = new Database(array('driver' => 'postgres', 'hostname' => '127.0.0.1', 'username' => 'root', 'password' => 'rootpassword', 'database' => 'picodb'));
        $this->db->getConnection()->exec('DROP TABLE IF EXISTS foobar');
        $this->db->getConnection()->exec('DROP TABLE IF EXISTS schema_version');
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
        $this->db->getConnection()->exec('CREATE TABLE foobar (id serial PRIMARY KEY, something TEXT)');
        $this->db->execute('INSERT INTO foobar (something) VALUES (?)', array('a'));
        $this->assertEquals(1, $this->db->getLastId());
        $this->assertEquals('a', $this->db->execute('SELECT something FROM foobar WHERE something=?', array('a'))->fetchColumn());
    }

    /**
     * @expectedException PicoDb\SQLException
     */
    public function testBadSQLQuery()
    {
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

    /**
     * @expectedException PicoDb\SQLException
     */
    public function testThatTransactionThrowExceptionWhenRollbacked()
    {
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
}
