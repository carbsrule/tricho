<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * @package main_system
 */

/**
 * Represents a join used by MainJoinFilter.
 * All values should be strings
 * 
 * @package main_system
 */
class MainJoin {
    private $fromTable;
    private $fromColumn;
    private $toTable;
    private $toColumn;
    
    /**
     * @param string $fromTable The from table name
     * @param string $fromColumn The from column name
     * @param string $toTable The to table name
     * @param string $toColumn The to column name
     */
    function __construct ($fromTable, $fromColumn, $toTable, $toColumn) {
        $this->fromTable = $fromTable;
        $this->fromColumn = $fromColumn;
        $this->toTable = $toTable;
        $this->toColumn = $toColumn;
    }
    
    /* From Table */
    /**
     * Set the from table
     * @param string $value The name of the table
     */
    function setFromTable($value) {
        $this->fromTable = $value;
    }
    /**
     * Get the from table
     * @return string The from table name
     */
    function getFromTable() {
        return $this->fromTable;
    }

    /* From Column */
    /**
     * Set the from column
     * @param string $value The name of the column
     */
    function setFromColumn($value) {
        $this->fromColumn = $value;
    }
    /**
     * Get the from column
     * @return string The from column name
     */
    function getFromColumn() {
        return $this->fromColumn;
    }

    /* To Table */
    /**
     * Set the to table
     * @param string $value The name of the table
     */
    function setToTable($value) {
        $this->toTable = $value;
    }
    /**
     * Get the to table
     * @return string The to table name
     */
    function getToTable() {
        return $this->toTable;
    }
    
    /* To Column */
    /**
     * Set the to column
     * @param string $value The name of the column
     */
    function setToColumn($value) {
        $this->toColumn = $value;
    }
    /**
     * Get the to column
     * @return string The to column name
     */
    function getToColumn() {
        return $this->toColumn;
    }
    
    
    function __toString () {
        return __CLASS__. ': '. $this->fromTable . '.' . $this->fromColumn . ' -&gt; ' . $this->toTable . '.' . $this->toColumn;
    }
}

?>
