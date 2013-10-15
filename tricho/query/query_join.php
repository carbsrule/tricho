<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * @package query_builder
 */

/**
 * used to create JOINs for a query
 * 
 * @package query_builder
 */
class QueryJoin {
    
    private $table;
    private $clauses;
    private $type;
    
    /**
     * @param QueryTable $table the table you wish to join to
     * @param LogicTree $clauses the clauses (i.e. ON) to use for the join
     * @param int $type the type of JOIN required, i.e. SQL_JOIN_TYPE_LEFT or
     *        SQL_JOIN_TYPE_INNER
     */
    function __construct (QueryTable $table, LogicTree $clauses, $type = SQL_JOIN_TYPE_LEFT) {
        
        if ($type === SQL_JOIN_TYPE_LEFT or $type === SQL_JOIN_TYPE_INNER) {
            $this->table = $table;
            $this->clauses = $clauses;
            $this->type = $type;
        } else {
            throw new Exception ('Invalid join type');
        }
        
    }
    
    /**
     * Gets the table which the join will join to, e.g. INNER JOIN
     * <b>JoinedTable</b> ON ...
     * 
     * @return QueryTable
     */
    function getTable () {
        return $this->table;
    }
    
    /**
     * Gets the type of join used, e.g. <b>INNER JOIN</b> JoinedTable ON ...
     * 
     * @return int SQL_JOIN_TYPE_LEFT or SQL_JOIN_TYPE_INNER
     */
    function getType () {
        return $this->type;
    }
    
    /**
     * Gets the clauses used for the join, e.g. INNER JOIN JoinedTable ON
     * <b>a = b</b>
     * 
     * @return LogicTree
     */
    function getClauses () {
        return $this->clauses;
    }

    public function __toString () {
        return __CLASS__. ' { table: '. $this->table->getName (). '; }';
    }
    
}

?>
