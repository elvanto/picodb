<?php

namespace PicoDb\Driver;

use PDO;
use LogicException;
use PDOException;

/**
 * Base Driver class
 *
 * @package PicoDb\Driver
 * @author  Frederic Guillot
 */
abstract class Base
{
    /**
     * List of required settings options
     *
     * @access protected
     */
    protected array $requiredAttributes = array();

    /**
     * PDO connection
     *
     * @access private
     */
    private ?PDO $pdo = null;

    /**
     * Create a new PDO connection
     *
     * @abstract
     * @access public
     * @param  array   $settings
     */
    abstract public function createConnection(array $settings);

    /**
     * Enable foreign keys
     *
     * @abstract
     * @access public
     */
    abstract public function enableForeignKeys();

    /**
     * Disable foreign keys
     *
     * @abstract
     * @access public
     */
    abstract public function disableForeignKeys();

    /**
     * Return true if the error code is a duplicate key
     *
     * @abstract
     * @access public
     * @param  integer  $code
     * @return boolean
     */
    abstract public function isDuplicateKeyError($code);

    /**
     * Escape identifier
     *
     * @abstract
     * @access public
     * @param  string  $identifier
     * @return string
     */
    abstract public function escape($identifier);

    /**
     * Get non standard operator
     *
     * @abstract
     * @access public
     * @param  string  $operator
     * @return string
     */
    abstract public function getOperator($operator);

    /**
     * Build a JSON field equality condition
     *
     * Returns [string $sql, array $preValueBindings] where $sql contains a trailing ?
     * for the comparison value, and $preValueBindings contains any path-related bindings
     * that must be merged before the value.
     *
     * @abstract
     * @access public
     * @param  string  $column    Escaped column identifier
     * @param  string  $path      JSONPath expression (e.g. '$.key' or '$.key1.key2')
     * @param  string  $operator  Comparison operator, defaults to '='
     * @return array{0: string, 1: array}
     */
    abstract public function buildJsonExtractCondition(string $column, string $path, string $operator = '='): array;

    /**
     * Build a JSON array containment condition
     *
     * Checks that all elements of $values exist in the JSON array stored in $column
     * (optionally at $path within the column).
     *
     * Returns [string $sql, array $bindings] — a complete condition with all bindings included.
     *
     * @abstract
     * @access public
     * @param  string      $column  Escaped column identifier
     * @param  string|null $path    JSONPath expression, or null to target the column directly
     * @param  array       $values  The values that must all be present in the JSON array
     * @return array{0: string, 1: array}
     */
    abstract public function buildJsonContainsCondition(string $column, ?string $path, array $values): array;

    /**
     * Get last inserted id
     *
     * @abstract
     * @access public
     */
    abstract public function getLastId(): string|false;

    /**
     * Get current schema version
     *
     * @abstract
     * @access public
     * @return integer
     */
    abstract public function getSchemaVersion();

    /**
     * Set current schema version
     *
     * @abstract
     * @access public
     * @param  integer  $version
     */
    abstract public function setSchemaVersion($version);

    /**
     * Constructor
     *
     * @access public
     * @param  array   $settings
     */
    public function __construct(array $settings)
    {
        foreach ($this->requiredAttributes as $attribute) {
            if (! isset($settings[$attribute])) {
                throw new LogicException('This configuration parameter is missing: "'.$attribute.'"');
            }
        }

        $this->createConnection($settings);
        $this->getConnection()->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Get the PDO connection
     *
     * @access public
     * @throws LogicException
     */
    public function getConnection(): PDO
    {
        if ($this->pdo === null) {
            throw new LogicException('The database connection is not established.');
        }

        return $this->pdo;
    }

    /**
     * Set the PDO connection
     *
     * @access protected
     */
    protected function setConnection(PDO $pdo): void
    {
        $this->pdo = $pdo;
    }

    /**
     * Release the PDO connection
     *
     * @access public
     */
    public function closeConnection()
    {
        $this->pdo = null;
    }

    /**
     * Get offset limit clause
     *
     * @param int $limit
     * @param int $offset
     * @param string|null $order
     * @return string
     */
    public function getLimitClause($limit, $offset, $order)
    {
        $clause = '';

        if (! is_null($limit)) {
            $clause .= ' LIMIT ' . $limit;
        }

        if (! is_null($offset)) {
            $clause .= '  OFFSET ' . $offset;
        }

        return $clause;
    }

    /**
     * Upsert for a key/value variable
     *
     * @access public
     * @param  string  $table
     * @param  string  $keyColumn
     * @param  string  $valueColumn
     * @param  array   $dictionary
     * @return bool    False on failure
     */
    public function upsert($table, $keyColumn, $valueColumn, array $dictionary)
    {
        try {
            $this->getConnection()->beginTransaction();

            foreach ($dictionary as $key => $value) {

                $rq = $this->getConnection()->prepare('SELECT 1 FROM '.$this->escape($table).' WHERE '.$this->escape($keyColumn).'=?');
                $rq->execute(array($key));

                if ($rq->fetchColumn()) {
                    $rq = $this->getConnection()->prepare('UPDATE '.$this->escape($table).' SET '.$this->escape($valueColumn).'=? WHERE '.$this->escape($keyColumn).'=?');
                    $rq->execute(array($value, $key));
                }
                else {
                    $rq = $this->getConnection()->prepare('INSERT INTO '.$this->escape($table).' ('.$this->escape($keyColumn).', '.$this->escape($valueColumn).') VALUES (?, ?)');
                    $rq->execute(array($key, $value));
                }
            }

            $this->getConnection()->commit();

            return true;
        }
        catch (PDOException $e) {
            $this->getConnection()->rollBack();
            return false;
        }
    }

    /**
     * Run EXPLAIN command
     *
     * @access public
     * @param  string $sql
     * @param  array  $values
     * @return array
     */
    public function explain($sql, array $values)
    {
        return $this->getConnection()->query('EXPLAIN '.$this->getSqlFromPreparedStatement($sql, $values))->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Replace placeholder with values in prepared statement
     *
     * @access protected
     * @param  string $sql
     * @param  array  $values
     * @return string
     */
    protected function getSqlFromPreparedStatement($sql, array $values)
    {
        foreach ($values as $value) {
            $pos = strpos($sql, '?');
            if ($pos === false) {
                break;
            }
            $sql = substr_replace($sql, "'$value'", $pos, 1);
        }

        return $sql;
    }

    /**
     * Get database version
     *
     * @access public
     * @return array
     */
    public function getDatabaseVersion()
    {
        return $this->getConnection()->query('SELECT VERSION()')->fetchColumn();
    }
}
