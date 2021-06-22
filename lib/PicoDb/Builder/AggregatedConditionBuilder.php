<?php

namespace PicoDb\Builder;

use PicoDb\Database;
use PicoDb\Table;

/**
 * Class AggregatedConditionBuilder
 *
 * @package PicoDb\Builder
 * @author  Frederic Guillot
 */
class AggregatedConditionBuilder extends BaseConditionBuilder implements BuilderInterface
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
     * Build the SQL aggregated condition
     *
     * @access public
     * @return string
     */
    public function build()
    {
        return empty($this->conditions) ? '' : ' HAVING '.implode(' AND ', $this->conditions);
    }
}
