<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Query;

/**
 * Used in a {@link LogicTree} to add a condition for an SQL query,
 * e.g. `A` LIKE '%B%'
 * 
 * @package query_builder
 */
class LogicConditionNode extends LogicTreeNode {
    
    private $column = null;
    private $operator = null;
    private $value = null;
    
    /**
     * @param QueryField $col the field on which the condition applies
     * @param int $operator the constant definition of the type of condition,
     *        e.g. LOGIC_CONDITION_GT
     * @param mixed $values a {@link QueryField} for most condition types, or
     *        an array of {@link QueryField}s for LOGIC_CONDITION_[NOT_]BETWEEN
     *        (2 elements) or LOGIC_CONDITION_IN (1+ elements)
     */
    function __construct (QueryField $col, $operator, $values) {
        $this->type = LOGIC_TREE_COND;
        $this->column = $col;
        
        // TODO: remove constants and just use the symbols (e.g. '=', '<', 'IN')
        if ($operator === '=') $operator = LOGIC_CONDITION_EQ;
        
        // check operator is valid
        $operator_int = (int) $operator;
        switch ($operator_int) {
            
            case LOGIC_CONDITION_LIKE:
            case LOGIC_CONDITION_EQ:
            case LOGIC_CONDITION_LT:
            case LOGIC_CONDITION_GT:
            case LOGIC_CONDITION_LT_OR_EQ:
            case LOGIC_CONDITION_GT_OR_EQ:
            case LOGIC_CONDITION_NOT_LIKE:
            case LOGIC_CONDITION_NOT_EQ:
            case LOGIC_CONDITION_IS:
                if ($values instanceof QueryField) {
                    $this->value = $values;
                } else if (is_bool($values)) {
                    $this->value = new QueryFieldLiteral((int) $values);
                } else if (is_int($values) or is_string($values) or is_float($values)) {
                    $this->value = new QueryFieldLiteral($values);
                } else {
                    throw new Exception ("Cannot use operator {$operator} for parameter that isn't a QueryField");
                }
                $this->operator = $operator_int;
                break;
            
            case LOGIC_CONDITION_BETWEEN:
            case LOGIC_CONDITION_NOT_BETWEEN:
                if (!is_array ($values)) {
                    throw new Exception ("[NOT] BETWEEN values must be an array of QueryField objects");
                }
                $values_arr = array ();
                foreach ($values as $value) {
                    if ($value instanceof QueryField) {
                        $values_arr[] = $value;
                    } else {
                        throw new Exception ("All [NOT] BETWEEN elements must be of type QueryField");
                    }
                }
                $this->value = $values_arr;
                $this->operator = $operator_int;
                break;
                
            case LOGIC_CONDITION_IN:
                if (!is_array ($values)) {
                    if ($values instanceof QueryField) {
                        $values = array ($values);
                    } else {
                        throw new Exception ("IN values must be of type QueryField, or an array of QueryField objects");
                    }
                }
                $values_arr = array ();
                foreach ($values as $value) {
                    if ($value instanceof QueryField) {
                        $values_arr[] = $value;
                    } else {
                        throw new Exception ("All IN elements must be of type QueryField");
                    }
                }
                $this->value = $values_arr;
                $this->operator = $operator_int;
                break;
            
            default:
                throw new Exception ("Unknown operator {$operator}");
        }
        
        parent::__construct ();
    }
    
    /**
     * @see getString()
     * @return string
     */
    function __toString () {
        return $this->getString ();
    }
    
    /**
     * Returns this logic condition node as a fragment of a SQL query
     * 
     * @param int $level the number of indents to prepend to the resultant
     *        string
     * @return string
     */
    function getString ($level = 0) {
        $string = $this->column->identify ('normal'). ' ';
        
        switch ($this->operator) {
            
            case LOGIC_CONDITION_LIKE:
                $string .= 'LIKE '. cast_to_string ($this->value);
                break;
                
            case LOGIC_CONDITION_EQ:
                $clause = '= ' . $this->value->identify('normal');
                if ($this->value instanceof QueryFieldLiteral) {
                    if ($this->value->getName () === null) {
                        $clause = 'IS NULL';
                    }
                }
                $string .= $clause;
                break;
                
            case LOGIC_CONDITION_BETWEEN:
                $string .= 'BETWEEN '. cast_to_string ($this->value[0]). ' AND '. cast_to_string ($this->value[1]);
                break;
                
            case LOGIC_CONDITION_LT:
                $string .= '< '. cast_to_string ($this->value);
                break;
                
            case LOGIC_CONDITION_GT:
                $string .= '> '. cast_to_string ($this->value);
                break;
                
            case LOGIC_CONDITION_LT_OR_EQ:
                $string .= '<= '. cast_to_string ($this->value);
                break;
                
            case LOGIC_CONDITION_GT_OR_EQ:
                $string .= '>= '. cast_to_string ($this->value);
                break;
                
            case LOGIC_CONDITION_NOT_LIKE:
                $string .= 'NOT LIKE '. cast_to_string ($this->value);
                break;
                
            case LOGIC_CONDITION_NOT_EQ:
                if ($this->value->getName () === null) {
                    $string .= 'IS NOT NULL';
                } else {
                    $string .= '!= '. cast_to_string ($this->value);
                }
                break;
            
            case LOGIC_CONDITION_NOT_BETWEEN:
                $string .= 'NOT BETWEEN '. cast_to_string ($this->value[0]). ' AND '. cast_to_string ($this->value[1]);
                break;
            
            case LOGIC_CONDITION_IS:
                $string .= 'IS '. cast_to_string ($this->value);
                break;
                
            case LOGIC_CONDITION_IN:
                $string .= 'IN (';
                $value_count = 0;
                foreach ($this->value as $value) {
                    if ($value_count++ > 0) $string .= ', ';
                    $string .= cast_to_string ($value);
                }
                $string .= ')';
                break;
                
            default:
                $string .= "UNKNOWN_OPERATOR({$this->operator})";
        }
        return $string;
    }
    
    /**
     * Adds another condition, modifiying the existing tree as required.
     * 
     * For example, if the new operator type is AND and this condition is under
     * an OR operator, the compaction before adding the new condition will be
     * "this OR that" (where that is the other condition under the existing OR),
     * and the compaction after will be "(this and new_cond) OR that"
     * 
     * @param LogicConditionNode $node the new condition
     * @param int $condition_type either LOGIC_TREE_OR or LOGIC_TREE_AND
     */
    function addCondition (LogicConditionNode $node, $operator_type = LOGIC_TREE_OR) {
        
        switch ($operator_type) {
            case LOGIC_TREE_OR:
            case LOGIC_TREE_AND:
                break;
            default:
                throw new Exception ("Attempted to use addCondition with unknown type {$operator_type}");
        }
        
        $parent_type = null;
        $parent = $this->parent;
        
        if ($this->parent !== null and $parent->getType () == $operator_type) {
            $parent->addChild ($node);
        } else {
            $old_parent = $this->parent;
            $new_parent = new LogicOperatorNode ($operator_type);
            
            $this->setParent ($new_parent);
            $new_parent->addChild ($this);
            $node->setParent ($new_parent);
            $new_parent->addChild ($node);
            
            if ($old_parent !== null) {
                $old_parent->removeChild ($this);
                $old_parent->addChild ($new_parent);
            }
            
        }
        
    }
    
    /*
    function getConditions () {
        return array ($this);
    }
    */
    
    /**
     * Gets the left hand side of this condition
     */
    function getLHS () {
        return $this->column;
    }
    
    /**
     * Gets the right hand side of this condition
     */
    function getRHS () {
        return $this->value;
    }
    
}

?>
