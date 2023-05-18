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
             *      'lobParams': array,
             *      'positionalParams': array,
             *      'namedParams': array
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
            'useNamedParams' => false,
            'sql' => "SELECT * FROM `some_table` WHERE `someCoumn` = ? and `someOtherColumn` = ?",
            'lobParams' => ['first value has a ? inside it', 'second value'],
            'positionalParams' => [],
            'namedParams' => []
        ]);

        $logMessages = $this->db->getLogMessages();
        self::assertCount(1, $logMessages);
        self::assertEquals(
            "SELECT * FROM `some_table` WHERE `someCoumn` = 'first value has a ? inside it' and `someOtherColumn` = 'second value'",
            $logMessages[0],
            var_export($logMessages, true)
        );


        // now test with positional params
        $statementHandler->testBeforeExecute([
            'logQueries' => true,
            'logQueryValues' => true,
            'sql' => "SELECT * FROM `some_table` WHERE `someCoumn` = ? and `someOtherColumn` = ?",
            'lobParams' => [],
            'positionalParams' => ['first value has a ? inside it', 'second value'],
            'namedParams' => []
        ]);

        $logMessages = $this->db->getLogMessages();
        self::assertCount(2, $logMessages);
        self::assertEquals(
            "SELECT * FROM `some_table` WHERE `someCoumn` = 'first value has a ? inside it' and `someOtherColumn` = 'second value'",
            $logMessages[1],
            var_export($logMessages, true)
        );

        // now test with named params
        $statementHandler->testBeforeExecute([
            'logQueries' => true,
            'logQueryValues' => true,
            'useNamedParams' => true,
            'sql' => "SELECT * FROM `some_table` WHERE `someCoumn` = :first and `someOtherColumn` = :second",
            'lobParams' => [],
            'positionalParams' => [],
            'namedParams' => ['first' => 'first value has a ? inside it', 'second' => 'second value']
        ]);

        $logMessages = $this->db->getLogMessages();
        self::assertCount(3, $logMessages);
        self::assertEquals(
            "SELECT * FROM `some_table` WHERE `someCoumn` = 'first value has a ? inside it' and `someOtherColumn` = 'second value'",
            $logMessages[2],
            var_export($logMessages, true)
        );
    }
}
