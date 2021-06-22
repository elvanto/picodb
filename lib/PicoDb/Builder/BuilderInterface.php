<?php

namespace PicoDb\Builder;

interface BuilderInterface
{
    /**
     * Build the SQL
     */
    public function build();
}
