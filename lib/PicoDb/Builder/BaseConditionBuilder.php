<?php

namespace PicoDb\Builder;

use PicoDb\Database;
use PicoDb\Table;

/**
 * Class BaseConditionBuilder
 *
 * @package PicoDb\Builder
 * @author  Frederic Guillot
 */
class BaseConditionBuilder
{
    /**
     * Database instance
     *
     * @access private
     * @var    Database
     */
    protected $db;

    /**
     * Condition values
     *
     * @access private
     * @var    array
     */
    protected $values = array();

    /**
     * SQL AND conditions
     *
     * @access private
     * @var    string[]
     */
    protected $conditions = array();

    /**
     * SQL embedded NOT/AND/OR/XOR conditions
     *
     * @access private
     * @var    LogicConditionBuilder[]
     */
    protected $embeddedConditions = array();

    /**
     * SQL condition offset
     *
     * @access private
     * @var int
     */
    protected $embeddedConditionOffset = 0;

    /**
     * Constructor
     *
     * @access public
     * @param  Database  $db
     */
    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get condition values
     *
     * @access public
     * @return array
     */
    public function getValues()
    {
        return $this->values;
    }

    /**
     * Returns true if there is some conditions
     *
     * @access public
     * @return boolean
     */
    public function hasCondition()
    {
        return ! empty($this->conditions);
    }

    /**
     * Add custom condition
     *
     * @access public
     * @param  string  $sql
     */
    public function addCondition($sql)
    {
        if ($this->embeddedConditionOffset > 0) {
            $this->embeddedConditions[$this->embeddedConditionOffset]->withCondition($sql);
        }
        else {
            $this->conditions[] = $sql;
        }
    }

    public function beginNot()
    {
        $this->embeddedConditionOffset++;
        $this->embeddedConditions[$this->embeddedConditionOffset] = new LogicConditionBuilder('NOT');
    }

    public function closeNot()
    {
        $condition = $this->embeddedConditions[$this->embeddedConditionOffset]->build();
        $this->embeddedConditionOffset--;

        if ($this->embeddedConditionOffset > 0) {
            $this->embeddedConditions[$this->embeddedConditionOffset]->withCondition($condition);
        } else {
            $this->conditions[] = $condition;
        }
    }

    /**
     * Start AND condition
     *
     * @access public
     */
    public function beginAnd()
    {
        $this->embeddedConditionOffset++;
        $this->embeddedConditions[$this->embeddedConditionOffset] = new LogicConditionBuilder('AND');
    }

    /**
     * Close AND condition
     *
     * @access public
     */
    public function closeAnd()
    {
        $condition = $this->embeddedConditions[$this->embeddedConditionOffset]->build();
        $this->embeddedConditionOffset--;

        if ($this->embeddedConditionOffset > 0) {
            $this->embeddedConditions[$this->embeddedConditionOffset]->withCondition($condition);
        } else {
            $this->conditions[] = $condition;
        }
    }

    /**
     * Start OR condition
     *
     * @access public
     */
    public function beginOr()
    {
        $this->embeddedConditionOffset++;
        $this->embeddedConditions[$this->embeddedConditionOffset] = new LogicConditionBuilder('OR');
    }

    /**
     * Close OR condition
     *
     * @access public
     */
    public function closeOr()
    {
        $condition = $this->embeddedConditions[$this->embeddedConditionOffset]->build();
        $this->embeddedConditionOffset--;

        if ($this->embeddedConditionOffset > 0) {
            $this->embeddedConditions[$this->embeddedConditionOffset]->withCondition($condition);
        } else {
            $this->conditions[] = $condition;
        }
    }

    /**
     * Start OR condition
     *
     * @access public
     */
    public function beginXor()
    {
        $this->embeddedConditionOffset++;
        $this->embeddedConditions[$this->embeddedConditionOffset] = new LogicConditionBuilder('XOR');
    }

    /**
     * Close OR condition
     *
     * @access public
     */
    public function closeXor()
    {
        $condition = $this->embeddedConditions[$this->embeddedConditionOffset]->build();
        $this->embeddedConditionOffset--;

        if ($this->embeddedConditionOffset > 0) {
            $this->embeddedConditions[$this->embeddedConditionOffset]->withCondition($condition);
        } else {
            $this->conditions[] = $condition;
        }
    }

    /**
     * Equal condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $value
     */
    public function eq($column, $value)
    {
        $this->addCondition($this->db->escapeIdentifier($column).' = ?');
        $this->values[] = $value;
    }

    /**
     * Not equal condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $value
     */
    public function neq($column, $value)
    {
        $this->addCondition($this->db->escapeIdentifier($column).' != ?');
        $this->values[] = $value;
    }

    /**
     * IN condition
     *
     * @access public
     * @param  string   $column
     * @param  array    $values
     */
    public function in($column, array $values)
    {
        if (!empty($values)) {
            $this->addCondition($this->db->escapeIdentifier($column).' IN ('.implode(', ', array_fill(0, count($values), '?')).')');
            $this->values = array_merge($this->values, $values);
        } else {
            throw new \InvalidArgumentException('$values must not be an empty array');
        }
    }

    /**
     * IN condition with a subquery
     *
     * @access public
     * @param  string   $column
     * @param  Table    $subquery
     */
    public function inSubquery($column, Table $subquery)
    {
        $this->addCondition($this->db->escapeIdentifier($column).' IN ('.$subquery->buildSelectQuery().')');
        $this->values = array_merge($this->values, $subquery->getValues());
    }

    /**
     * NOT IN condition
     *
     * @access public
     * @param  string   $column
     * @param  array    $values
     */
    public function notIn($column, array $values)
    {
        if (! empty($values)) {
            $this->addCondition($this->db->escapeIdentifier($column).' NOT IN ('.implode(', ', array_fill(0, count($values), '?')).')');
            $this->values = array_merge($this->values, $values);
        }
    }

    /**
     * NOT IN condition with a subquery
     *
     * @access public
     * @param  string   $column
     * @param  Table    $subquery
     */
    public function notInSubquery($column, Table $subquery)
    {
        $this->addCondition($this->db->escapeIdentifier($column).' NOT IN ('.$subquery->buildSelectQuery().')');
        $this->values = array_merge($this->values, $subquery->getValues());
    }

    /**
     * LIKE condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $value
     */
    public function like($column, $value)
    {
        $this->addCondition($this->db->escapeIdentifier($column).' '.$this->db->getDriver()->getOperator('LIKE').' ?');
        $this->values[] = $value;
    }

    /**
     * ILIKE condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $value
     */
    public function ilike($column, $value)
    {
        $this->addCondition($this->db->escapeIdentifier($column).' '.$this->db->getDriver()->getOperator('ILIKE').' ?');
        $this->values[] = $value;
    }

    /**
     * NOT LIKE condition
     *
     * @param $column
     * @param $value
     */
    public function notLike($column, $value)
    {
        $this->addCondition($this->db->escapeIdentifier($column).' NOT LIKE ?');
        $this->values[] = $value;
    }

    /**
     * Greater than condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $value
     */
    public function gt($column, $value)
    {
        $this->addCondition($this->db->escapeIdentifier($column).' > ?');
        $this->values[] = $value;
    }

    /**
     * Greater than condition with subquery
     *
     * @access public
     * @param  string   $column
     * @param  Table    $subquery
     */
    public function gtSubquery($column, Table $subquery)
    {
        $this->addCondition($this->db->escapeIdentifier($column).' > ('.$subquery->buildSelectQuery().')');
        $this->values = array_merge($this->values, $subquery->getValues());
    }

    /**
     * Lower than condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $value
     */
    public function lt($column, $value)
    {
        $this->addCondition($this->db->escapeIdentifier($column).' < ?');
        $this->values[] = $value;
    }

    /**
     * Lower than condition with subquery
     *
     * @access public
     * @param  string   $column
     * @param  Table    $subquery
     */
    public function ltSubquery($column, Table $subquery)
    {
        $this->addCondition($this->db->escapeIdentifier($column).' < ('.$subquery->buildSelectQuery().')');
        $this->values = array_merge($this->values, $subquery->getValues());
    }

    /**
     * Greater than or equals condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $value
     */
    public function gte($column, $value)
    {
        $this->addCondition($this->db->escapeIdentifier($column).' >= ?');
        $this->values[] = $value;
    }

    /**
     * Greater than or equal condition with subquery
     *
     * @access public
     * @param  string   $column
     * @param  Table    $subquery
     */
    public function gteSubquery($column, Table $subquery)
    {
        $this->addCondition($this->db->escapeIdentifier($column).' >= ('.$subquery->buildSelectQuery().')');
        $this->values = array_merge($this->values, $subquery->getValues());
    }

    /**
     * Lower than or equals condition
     *
     * @access public
     * @param  string   $column
     * @param  mixed    $value
     */
    public function lte($column, $value)
    {
        $this->addCondition($this->db->escapeIdentifier($column).' <= ?');
        $this->values[] = $value;
    }

    /**
     * Lower than or equal condition with subquery
     *
     * @access public
     * @param  string   $column
     * @param  Table    $subquery
     */
    public function lteSubquery($column, Table $subquery)
    {
        $this->addCondition($this->db->escapeIdentifier($column).' <= ('.$subquery->buildSelectQuery().')');
        $this->values = array_merge($this->values, $subquery->getValues());
    }

    /**
     * BETWEEN operator
     *
     * @param $column
     * @param $lowValue
     * @param $highValue
     */
    public function between($column, $lowValue, $highValue)
    {
        $this->addCondition($this->db->escapeIdentifier($column).' BETWEEN ? AND ?');
        $this->values[] = $lowValue;
        $this->values[] = $highValue;
    }

    /**
     * NOT BETWEEN operator
     *
     * @param $column
     * @param $lowValue
     * @param $highValue
     */
    public function notBetween($column, $lowValue, $highValue)
    {
        $this->addCondition($this->db->escapeIdentifier($column).' NOT BETWEEN ? AND ?');
        $this->values[] = $lowValue;
        $this->values[] = $highValue;
    }

    /**
     * IS NULL condition
     *
     * @access public
     * @param  string   $column
     */
    public function isNull($column)
    {
        $this->addCondition($this->db->escapeIdentifier($column).' IS NULL');
    }

    /**
     * IS NOT NULL condition
     *
     * @access public
     * @param  string   $column
     */
    public function notNull($column)
    {
        $this->addCondition($this->db->escapeIdentifier($column).' IS NOT NULL');
    }
}
