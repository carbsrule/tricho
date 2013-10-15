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
 * Used to search/filter the results on the main page for linked columns
 * usually stored in $_SESSION[ADMIN_KEY]['search_params'][$table->getName ()]
 * 
 * @package main_system
 */
class MainJoinFilter {
    
    private $joinlist;
    private $value;
    private $type;
    
    /**
     * @param mixed $value a string or number depending on the type
     * @param array $joinlist an array of {@link MainJoin}s representing each
     *        join in the join chain
     */
    function __construct ($value, $joinlist) {
        $this->value = $value;
        $this->type = LOGIC_CONDITION_EQ;
        
        if (is_array($joinlist)) {
            $this->joinlist = $joinlist;
            
            // integrity checking: remove all objects that are not a MainJoin
            foreach ($this->joinlist as $index => $object) {
                if (!($object instanceof MainJoin)) {
                    $this->joinlist[$index] = null;
                }
            }
            
        } else {
            // not even an array!
            $this->joinlist = array();
            throw new Exception('$joinlist must be an array');
        }
    }
    
    /**
     * Gets the value of this filter, e.g. for X = 7, the value is 7.
     * 
     * @return mixed the value (a string, number, or array of same, depending
     * on the condition type)
     */
    function getValue () {
        return $this->value;
    }
    
    /**
     * Gets the type of this filter (e.g. LOGIC_CONDITION_EQ)
     * @return int The type
     */
    function getType () {
        return $this->type;
    }
    
    /**
     * Sets the type of this filter
     * @param int $type The type of this filter (e.g. LOGIC_CONDITION_EQ)
     */
    function setType($type) {
        $this->type = $type;
    }
    
    /**
     * Set the value of this filter
     * @param mixed $value The value
     */
    function setValue ($value) {
        $this->value = $value;
    }
    
    function getName () {
        $item = $this->joinlist[0];
        if ($item != null) {
            return $item->getFromColumn();
        } else {
            return 'invalid';
        }
    }
    
    /**
     * Gets the joinlist for this filter
     *
     * @return array the joinlist
     */
    function getJoinList () {
        return $this->joinlist;
    }
    
    /**
     * Adds an item to the JoinList
     * 
     * @param MainJoin $join The item to add to the join list
     */
    function addToJoinList (MainJoin $join) {
        $this->joinlist[] = $join;
    }
    
    
    
    /**
     * Apply this filter to a MainTable
     *
     * @param MainTable $main_table The main table view to apply this filter to
     * @return LogicConditionNode A condition for this filter, or null on error
     */
    function applyFilter ($main_table) {
        
        $join = $this->joinlist[0];
        
        $base_table = $main_table->getSelectQuery ()->getBaseTable ();
        $cond = new LogicConditionNode (
            new QueryColumn ($base_table, $join->getFromColumn()),
            $this->type,
            new QueryFieldLiteral ($this->value)
        );
        
        return $cond;
    }
}
?>