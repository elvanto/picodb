<?php

namespace PicoDb\Builder;

/**
 * Class OrConditionBuilder
 *
 * @package PicoDb\Builder
 * @author  Frederic Guillot
 */
class LogicConditionBuilder
{
    /**
     * @var string
     */
    private $type;

    /**
     * List of SQL conditions
     *
     * @access protected
     * @var string[]
     */
    protected $conditions = array();

    /**
     * LogicConditionBuilder constructor.
     *
     * @param string $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * Add new condition
     *
     * @access public
     * @param  string $condition
     * @return $this
     */
    public function withCondition($condition) {
        $this->conditions[] = $condition;
        return $this;
    }

    /**
     * Build SQL
     *
     * @access public
     * @return string
     */
    public function build()
    {
        return '('.implode(' '. $this->type .' ', $this->conditions).')';
    }
}
