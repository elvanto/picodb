<?php

namespace PicoDb\Builder;

use PicoDb\Database;
use PicoDb\Table;

/**
 * Handle SQL conditions
 *
 * @package PicoDb\Builder
 * @author  Frederic Guillot
 */
class ConditionBuilder extends BaseConditionBuilder implements BuilderInterface
{
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
     * Build the SQL condition
     *
     * @access public
     * @return string
     */
    public function build()
    {
        return empty($this->conditions) ? '' : ' WHERE '.implode(' AND ', $this->conditions);
    }
}
