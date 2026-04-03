<?php

namespace PicoDb\Driver;

use PDO;
use PDOException;

/**
 * Postgres Driver
 *
 * @package PicoDb\Driver
 * @author  Frederic Guillot
 */
class Postgres extends Base
{
    /**
     * List of required settings options
     *
     * @access protected
     */
    protected array $requiredAttributes = [
        'database',
    ];

    /**
     * Table to store the schema version
     *
     * @access private
     */
    private string $schemaTable = 'schema_version';

    /**
     * Create a new PDO connection
     *
     * @access public
     * @param  array   $settings
     */
    public function createConnection(array $settings)
    {
        $dsn = 'pgsql:dbname='.$settings['database'];
        $username = null;
        $password = null;
        $options = array();

        if (! empty($settings['username'])) {
            $username = $settings['username'];
        }

        if (! empty($settings['password'])) {
            $password = $settings['password'];
        }

        if (! empty($settings['hostname'])) {
            $dsn .= ';host='.$settings['hostname'];
        }

        if (! empty($settings['port'])) {
            $dsn .= ';port='.$settings['port'];
        }

        if (! empty($settings['timeout'])) {
            $options[PDO::ATTR_TIMEOUT] = $settings['timeout'];
        }

        $this->setConnection(new PDO($dsn, $username, $password, $options));

        if (isset($settings['schema_table'])) {
            $this->schemaTable = $settings['schema_table'];
        }
    }

    /**
     * Enable foreign keys
     *
     * @access public
     */
    public function enableForeignKeys()
    {
    }

    /**
     * Disable foreign keys
     *
     * @access public
     */
    public function disableForeignKeys()
    {
    }

    /**
     * Return true if the error code is a duplicate key
     *
     * @access public
     * @param  integer  $code
     * @return boolean
     */
    public function isDuplicateKeyError($code)
    {
        return $code == 23505 || $code == 23503;
    }

    /**
     * Escape identifier
     *
     * @access public
     * @param  string  $identifier
     * @return string
     */
    public function escape($identifier)
    {
        return '"'.$identifier.'"';
    }

    /**
     * Get non standard operator
     *
     * @access public
     * @param  string  $operator
     * @return string
     */
    public function getOperator($operator)
    {
        if ($operator === 'LIKE') {
            return 'LIKE';
        }
        else if ($operator === 'ILIKE') {
            return 'ILIKE';
        }

        return '';
    }

    /**
     * Convert a JSONPath expression ($.key or $.key1.key2) to a Postgres path value
     * and select the appropriate operator.
     *
     * Single-key paths use ->> / -> operators; nested paths use #>> / #> operators
     * with a path literal like {key1,key2}.
     *
     * @param  string $path  JSONPath expression
     * @return array{0: string, 1: string, 2: string}  [path value, text operator, jsonb operator]
     */
    private function convertJsonPath(string $path): array
    {
        $stripped = substr($path, 2); // strip leading '$.'
        $parts = explode('.', $stripped);

        if (count($parts) === 1) {
            return [$parts[0], '->>', '->'];
        }

        return ['{'.implode(',', $parts).'}', '#>>', '#>'];
    }

    public function buildJsonExtractCondition(string $column, string $path, string $operator = '='): array
    {
        [$pgPath, $textOp] = $this->convertJsonPath($path);
        return [$column.$textOp.'? '.$operator.' ?', [$pgPath]];
    }

    public function buildJsonContainsCondition(string $column, ?string $path, array $values): array
    {
        if ($path === null) {
            return [$column.' @> ?::jsonb', [json_encode($values)]];
        }

        [$pgPath, , $jsonbOp] = $this->convertJsonPath($path);
        return [$column.$jsonbOp.'? @> ?::jsonb', [$pgPath, json_encode($values)]];
    }

    /**
     * Get last inserted id
     *
     * @access public
     */
    public function getLastId(): string|false
    {
        try {
            $rq = $this->getConnection()->prepare('SELECT LASTVAL()');
            $rq->execute();

            return (string) $rq->fetchColumn();
        }
        catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get current schema version
     *
     * @access public
     * @return integer
     */
    public function getSchemaVersion()
    {
        $this->getConnection()->exec("CREATE TABLE IF NOT EXISTS ".$this->schemaTable." (version INTEGER DEFAULT 0)");

        $rq = $this->getConnection()->prepare('SELECT "version" FROM "'.$this->schemaTable.'"');
        $rq->execute();
        $result = $rq->fetchColumn();

        if ($result !== false) {
            return (int) $result;
        }
        else {
            $this->getConnection()->exec('INSERT INTO '.$this->schemaTable.' VALUES(0)');
        }

        return 0;
    }

    /**
     * Set current schema version
     *
     * @access public
     * @param  integer  $version
     */
    public function setSchemaVersion($version)
    {
        $rq = $this->getConnection()->prepare('UPDATE '.$this->schemaTable.' SET version=?');
        $rq->execute(array($version));
    }

    /**
     * Run EXPLAIN command
     *
     * @param  string $sql
     * @param  array  $values
     * @return array
     */
    public function explain($sql, array $values)
    {
        return $this->getConnection()->query('EXPLAIN (FORMAT YAML) '.$this->getSqlFromPreparedStatement($sql, $values))->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get database version
     *
     * @access public
     * @return array
     */
    public function getDatabaseVersion()
    {
        return $this->getConnection()->query('SHOW server_version')->fetchColumn();
    }
}
