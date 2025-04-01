<?php

class MysqlLobTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PicoDb\Database
     */
    private $db;

    public function setUp(): void
    {
        $this->db = new PicoDb\Database(array('driver' => 'mysql', 'hostname' => getenv('MYSQL_HOST'), 'username' => 'root', 'password' => 'rootpassword', 'database' => 'picodb'));
        $this->db->getConnection()->exec('DROP TABLE IF EXISTS large_objects');
        $this->db->getConnection()->exec('CREATE TABLE large_objects (id VARCHAR(20), file_content BLOB)');
        $this->db->getStatementHandler()->withLogging();
    }

    public function testInsert()
    {
        $result = $this->db->largeObject('large_objects')->insertFromFile('file_content', __FILE__, array('id' => 'test'));
        $this->assertTrue($result);
    }

    public function testInsertFromString()
    {
        $data = 'test';
        $result = $this->db->largeObject('large_objects')->insertFromString('file_content', $data, array('id' => 'test'));
        $this->assertTrue($result);
    }

    public function testInsertWithOptionalParams()
    {
        $result = $this->db->largeObject('large_objects')->insertFromFile('file_content', __FILE__);
        $this->assertTrue($result);
    }

    public function testFindOneColumnAsStream()
    {
        $result = $this->db->largeObject('large_objects')->insertFromFile('file_content', __FILE__, array('id' => 'test'));
        $this->assertTrue($result);

        $contents = $this->db->largeObject('large_objects')->eq('id', 'test')->findOneColumnAsStream('file_content');
        $this->assertSame(md5(file_get_contents(__FILE__)), md5(stream_get_contents($contents)));
    }

    public function testFindOneColumnAsString()
    {
        $result = $this->db->largeObject('large_objects')->insertFromFile('file_content', __FILE__, array('id' => 'test'));
        $this->assertTrue($result);

        $contents = $this->db->largeObject('large_objects')->eq('id', 'test')->findOneColumnAsString('file_content');
        $this->assertSame(md5(file_get_contents(__FILE__)), md5($contents));
    }

    public function testUpdate()
    {
        $result = $this->db->largeObject('large_objects')->insertFromFile('file_content', __FILE__, array('id' => 'test1'));
        $this->assertTrue($result);

        $result = $this->db->largeObject('large_objects')->insertFromFile('file_content', __FILE__, array('id' => 'test2'));
        $this->assertTrue($result);

        $result = $this->db->largeObject('large_objects')->eq('id', 'test1')->updateFromFile('file_content', __DIR__.'/../LICENSE');
        $this->assertTrue($result);

        $contents = $this->db->largeObject('large_objects')->eq('id', 'test1')->findOneColumnAsString('file_content');
        $this->assertSame(md5(file_get_contents(__DIR__.'/../LICENSE')), md5($contents));

        $contents = $this->db->largeObject('large_objects')->eq('id', 'test2')->findOneColumnAsString('file_content');
        $this->assertSame(md5(file_get_contents(__FILE__)), md5($contents));

        $result = $this->db->largeObject('large_objects')->updateFromFile('file_content', __DIR__.'/../composer.json');
        $this->assertTrue($result);

        $contents = $this->db->largeObject('large_objects')->eq('id', 'test1')->findOneColumnAsString('file_content');
        $this->assertSame(md5(file_get_contents(__DIR__.'/../composer.json')), md5($contents));

        $contents = $this->db->largeObject('large_objects')->eq('id', 'test2')->findOneColumnAsString('file_content');
        $this->assertSame(md5(file_get_contents(__DIR__.'/../composer.json')), md5($contents));
    }
}
