<?php

use PicoDb\Database;

class MysqlDatabaseTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PicoDb\Database
     */
    private $db;

    public function setUp()
    {
        $this->db = new Database(array('driver' => 'mysql', 'hostname' => 'localhost', 'username' => 'root', 'password' => '', 'database' => 'picodb'));
        $this->db->getConnection()->exec('CREATE DATABASE IF NOT EXISTS `picodb`');
        $this->db->getConnection()->exec('DROP TABLE IF EXISTS foobar');
        $this->db->getConnection()->exec('DROP TABLE IF EXISTS schema_version');
    }

    public function testEscapeIdentifer()
    {
        $this->assertEquals('`a`', $this->db->escapeIdentifier('a'));
        $this->assertEquals('a.b', $this->db->escapeIdentifier('a.b'));
        $this->assertEquals('`c`.`a`', $this->db->escapeIdentifier('a', 'c'));
        $this->assertEquals('a.b', $this->db->escapeIdentifier('a.b', 'c'));
        $this->assertEquals('SELECT COUNT(*) FROM test', $this->db->escapeIdentifier('SELECT COUNT(*) FROM test'));
        $this->assertEquals('SELECT COUNT(*) FROM test', $this->db->escapeIdentifier('SELECT COUNT(*) FROM test', 'b'));
    }

    public function testEscapeIdentiferList()
    {
        $this->assertEquals(array('`c`.`a`', '`c`.`b`'), $this->db->escapeIdentifierList(array('a', 'b'), 'c'));
        $this->assertEquals(array('`a`', 'd.b'), $this->db->escapeIdentifierList(array('a', 'd.b')));
    }

    public function testThatPreparedStatementWorks()
    {
        $this->db->getConnection()->exec('CREATE TABLE foobar (id INT AUTO_INCREMENT NOT NULL, something TEXT, PRIMARY KEY (id)) ENGINE=InnoDB');
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
        $this->db->getConnection()->exec('CREATE TABLE foobar (something CHAR(1) UNIQUE) ENGINE=InnoDB');

        $this->assertNotFalse($this->db->execute('INSERT INTO foobar (something) VALUES (?)', array('a')));
        $this->assertFalse($this->db->execute('INSERT INTO foobar (something) VALUES (?)', array('a')));

        $this->assertEquals(1, $this->db->execute('SELECT COUNT(*) FROM foobar WHERE something=?', array('a'))->fetchColumn());
    }

    public function testThatTransactionReturnsAValue()
    {
        $this->assertEquals('a', $this->db->transaction(function (Database $db) {
            $db->getConnection()->exec('CREATE TABLE foobar (something CHAR(1) UNIQUE) ENGINE=InnoDB');
            $db->execute('INSERT INTO foobar (something) VALUES (?)', array('a'));

            return $db->execute('SELECT something FROM foobar WHERE something=?', array('a'))->fetchColumn();
        }));
    }

    public function testThatTransactionReturnsTrue()
    {
        $this->assertTrue($this->db->transaction(function (Database $db) {
            $db->getConnection()->exec('CREATE TABLE foobar (something CHAR(1) UNIQUE) ENGINE=InnoDB');
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
        $this->assertFalse($this->db->transaction(function (Database $db) {
            $db->getConnection()->exec('CREATE TABLE foobar (something CHAR(1) UNIQUE) ENGINE=InnoDB');
            $r1 = $db->execute('INSERT INTO foobar (something) VALUES (?)', array('a'));
            $r2 = $db->execute('INSERT INTO foobar (something) VALUES (?)', array('a'));
            return $r1 && $r2;
        }));
    }
}
