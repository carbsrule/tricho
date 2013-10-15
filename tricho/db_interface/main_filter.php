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
 * Used to search/filter the results on the main page;
 * usually stored in $_SESSION[ADMIN_KEY]['search_params'][$table->getName ()]
 * 
 * @package main_system
 */
class MainFilter {
    
    private $name;
    private $type;
    private $value;
    
    /**
     * @param string $name the name of the column which is to be searched
     * @param int $type a logic condition type (from constant definition), e.g. LOGIC_CONDITION_LT (<)
     * @param mixed $value a string, number, or array of same, depending on the type
     */
    function __construct ($name, $type, $value) {
        
        $this->name = $name;
        switch ($type) {
            case LOGIC_CONDITION_LIKE:
            case LOGIC_CONDITION_EQ:
            case LOGIC_CONDITION_BETWEEN:
            case LOGIC_CONDITION_NOT_BETWEEN:
            case LOGIC_CONDITION_LT:
            case LOGIC_CONDITION_GT:
            case LOGIC_CONDITION_LT_OR_EQ:
            case LOGIC_CONDITION_GT_OR_EQ:
            case LOGIC_CONDITION_NOT_LIKE:
            case LOGIC_CONDITION_NOT_EQ:
            case LOGIC_CONDITION_STARTS_WITH:
            case LOGIC_CONDITION_ENDS_WITH:
            case LOGIC_CONDITION_IS:
                $this->type = $type;
                break;
            
            default:
                throw new Exception ("Invalid type: {$type}");
        }
        $this->value = $value;
        
    }
    
    /**
     * Gets the name of the column that this filter applies on
     * 
     * @return string the column name
     */
    function getName () {
        return $this->name;
    }
    
    /**
     * Gets the type of filter
     * 
     * @return int the logic condition type (from constant definition),
     *     e.g. LOGIC_CONDITION_GT_OR_EQ (>=)
     */
    function getType () {
        return $this->type;
    }
    
    /**
     * Gets the value of this filter, e.g. for X = 7, the value is 7.
     * 
     * @return mixed the value (a string, number, or array of same, depending on the condition type)
     */
    function getValue () {
        return $this->value;
    }
    
    /**
     * Sets the name of the column that a filter applies to.
     * @param string Name of column
     */
    function setName ($name) {
        $this->name = $name;
    }
    
    /**
     * Sets the type of a filter.
     * @param int Logic condition type, e.g. LOGIC_CONDITION_EQ
     */
    function setType ($type) {
        $this->type = $type;
    }
    
    /**
     * Sets the value of a filter.
     * @param mixed Value to filter records by
     */
    function setValue ($value) {
        $this->value = $value;
    }
    
    /**
     * Apply this filter to a MainTable
     *
     * @param MainTable $mainTable The table to apply this filter to
     * @return LogicConditionNode A condition for this filter, or null on error
     */
    function applyFilter ($mainTable) {
        $where_conditions = array();
        
        // It is assumed that MainFilter will only ever apply on the base table
        // otherwise a MainJoinFilter should surely be used.
        // Hence, specify the base table name for the find method, otherwise a
        // matching column from another table could be returned.
        
        // Support fields in self-joining tables
        $base_table = $mainTable->getSelectQuery ()->getBaseTable ();
        if ($base_table instanceof AliasedTable) {
            $base_table_name = $base_table->getAlias();
        } else {
            $base_table_name = $base_table->getName();
        }
        $name = $base_table_name . '.' . $this->getName();
        
        // TODO: $mainTable->fields is a QueryFieldList -- should be private!
        // ignores columns with _PKxx aliases
        $field = $mainTable->fields->find ($name, true);
        
        if ($field != null) {
            // apply where clause for non-linked columns
            
            // echo "Base column {$name}";
            
            switch ($this->getType ()) {
                case LOGIC_CONDITION_STARTS_WITH:
                case LOGIC_CONDITION_ENDS_WITH:
                case LOGIC_CONDITION_LIKE:
                case LOGIC_CONDITION_NOT_LIKE:
                    // N.B. values are always quoted.
                    // If it's ever a requirement that users should be able to
                    // enter their own LIKE search terms, e.g. 'A%B', there'd
                    // need to be a separate rule, e.g. "Contains (advanced)"
                    // or simply "LIKE", but it would need to be restricted to
                    // setup users only, as queries could become broken really
                    // easily.
                    $value = $this->getValue();
                    $conn = ConnManager::get_active();
                    $value = $conn->quote($value);
                    
                    // % and _ have special meaning in LIKE terms, and thus
                    // need to be prefixed. Hopefully this isn't a security
                    // problem - it has to be done after escaping; otherwise
                    // the backslashes get escaped too.
                    $value = str_replace(array('%', '_'), array('\%', '\_'), $value);
                    $first = substr($value, 0, 1);
                    $last = substr($value, -1);
                    $inner = substr($value, 1, -1);
                    
                    if ($this->getType() == LOGIC_CONDITION_STARTS_WITH) {
                        $value = "{$first}{$inner}%{$last}";
                        $type = LOGIC_CONDITION_LIKE;
                    } else if ($this->getType() == LOGIC_CONDITION_ENDS_WITH) {
                        $value = "{$first}%{$inner}{$last}";
                        $type = LOGIC_CONDITION_LIKE;
                    } else {
                        $value = "{$first}%{$inner}%{$last}";
                        $type = $this->getType();
                    }
                    
                    // create condition
                    $cond = new LogicConditionNode (
                        $field,
                        $type,
                        new QueryFieldLiteral ($value, false)
                    );
                    break;
                
                case LOGIC_CONDITION_IS:
                    switch ($this->getValue ()) {
                        case 'null':
                        case 'not null':
                            $cond = new LogicConditionNode (
                                $field,
                                LOGIC_CONDITION_IS,
                                new QueryFieldLiteral ($this->getValue (), false)
                            );
                            break;
                    }
                    break;
                    
                case LOGIC_CONDITION_BETWEEN:
                case LOGIC_CONDITION_NOT_BETWEEN:
                    $values = $this->getValue ();
                    $cond = new LogicConditionNode (
                        $field,
                        $this->getType (),
                        array (new QueryFieldLiteral ($values[0]), new QueryFieldLiteral ($values[1]))
                    );
                    break;
                
                default:
                    $cond = new LogicConditionNode (
                        $field,
                        $this->getType (),
                        new QueryFieldLiteral ($this->getValue ())
                    );
            }
            return $cond;
            
        } else {
            return null;
        }
    }
    
}
?>