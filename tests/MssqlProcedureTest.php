<?php

use PicoDb\Database;
use PicoDb\Table;

class MssqlProcedureTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PicoDb\Database
     */
    private $db;

    public function setUp(): void
    {
        $this->db = new Database(array('driver' => 'mssql', 'hostname' => getenv('MSSQL_HOST'), 'username' => 'root', 'password' => 'rootpassword', 'database' => 'picodb', 'trust_server_cert' => true));
        $this->db->getConnection()->exec("IF (SELECT db_id('picodb')) IS NULL CREATE DATABASE [picodb]");
        $this->db->getConnection()->exec('DROP PROCEDURE IF EXISTS [usp_test1]');
        $this->db->getConnection()->exec('DROP TABLE IF EXISTS [test1]');
    }

    public function testExecute()
    {
        $this->assertNotFalse($this->db->execute('CREATE TABLE [test1] (a INTEGER, b INTEGER )'));
        $this->assertTrue($this->db->table('test1')->insert(array('a' => 2, 'b' => 3)));

        $this->assertNotFalse($this->db->execute('CREATE OR ALTER PROCEDURE [dbo].[usp_test1] @ParamA INT = NULL AS BEGIN IF @ParamA IS NOT NULL SELECT @ParamA AS [input]; ELSE SELECT * FROM [test1]; END'));
        $this->assertEquals(['a' => 2, 'b' => 3], $this->db->execute('EXEC [usp_test1]')->fetch(PDO::FETCH_ASSOC));
        $this->assertEquals(['input' => 5], $this->db->executeNamed('EXEC [usp_test1] :ParamA', ['ParamA' => 5])->fetch(PDO::FETCH_ASSOC));
    }

    public function testOutputParams()
    {
        $this->assertNotFalse($this->db->execute('CREATE OR ALTER PROCEDURE [dbo].[usp_testMultiply] @ParamA INT, @ParamB INT, @ParamC INT OUTPUT AS BEGIN SET @ParamC = @ParamA * @ParamB; END'));

        $output = 0;
        $this->assertNotFalse($this->db->executeNamed('EXEC [usp_testMultiply] :ParamA, :ParamB, :ParamC', ['ParamA' => 5, 'ParamB' => 10], ['ParamC' => &$output]));
        $this->assertEquals(50, $output);
    }
}
