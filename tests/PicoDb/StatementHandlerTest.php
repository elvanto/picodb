<?php
declare(strict_types=1);

namespace PicoDb;

use PHPUnit\Framework\TestCase;

class StatementHandlerTest extends TestCase
{
    /**
     * @var Database
     */
    private $db;

    protected function setUp()
    {
        $this->db = new Database(array('driver' => 'sqlite', 'filename' => ':memory:'));
        $this->statementHandler = new StatementHandler($this->db);
        parent::setUp();
    }


    public function testBeforeExecuteLogs()
    {
        // create an anon class that extends statement handler
        $statementHandler = new class($this->db) extends StatementHandler {

            /**
             * A wrapper to set the state of the class before running the protected var.
             *
             * @param array{
             *      'logQueries': boolean,
             *      'logQueryValues': boolean,
             *      'sql': string,
             *      'lobParams': array
             *     } $props
             * @return void
             */
            public function testBeforeExecute(array $props)
            {
                foreach ($props as $key => $value) {
                    $this->{$key} = $value;
                }
                $this->beforeExecute();
            }
        };

        $statementHandler->testBeforeExecute([
            'logQueries' => true,
            'logQueryValues' => true,
            'sql' => "SELECT * FROM `some_table` WHERE `someCoumn`='?' and `someOtherColumn`='?'",
            'lobParams' => ['first value has a ? inside it', 'second value']
        ]);

        $expectedLogs = [
            "SELECT * FROM `some_table` WHERE `someCoumn`='first value has a ? inside it' and `someOtherColumn`='second value'"
        ];

        $logMessages = $this->db->getLogMessages();
        self::assertEquals($expectedLogs, $logMessages, var_export($logMessages, true));
    }
}
