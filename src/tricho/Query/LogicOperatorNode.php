<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Query;

use Exception;

/**
 * A LogicOperatorNode represents an AND or an OR used in a {@link LogicTree}.
 * It can have any number of child nodes, of which each is a
 * {@link LogicConditionNode} or another LogicOperatorNode.
 * 
 * @package query_builder
 */
class LogicOperatorNode extends LogicTreeNode {
    
    /**
     * @param int $node_type The type of this node (e.g. LOGIC_TREE_AND)
     */
    function __construct ($node_type = LOGIC_TREE_OR) {
        switch ($node_type) {
            case LOGIC_TREE_OR:
            case LOGIC_TREE_AND:
                break;
            default:
                throw new Exception ("Attempted to create LogicOperatorNode with unknown type {$node_type}");
        }
        
        $this->type = $node_type;
        parent::__construct ();
    }
    
    /**
     * @see getString
     * @return string
     */
    function __toString () {
        return $this->getString (0);
    }
    
    /**
     * Gets a fragment of an SQL query that contains this operator and all its
     * children
     * 
     * @param int $level the number of indents to prepend to the resultant
     *        string
     * @return string
     */
    function getString ($level = 0) {
        // echo 'Processing ', ($this->type === LOGIC_TREE_OR ? 'OR' : 'AND'), " node at level {$level}\n";
        $string = '';
        if ($level > 0) $string .= '(';
        if (@count($this->children) > 0) {
            foreach ($this->children as $child_count => $child) {
                if ($child_count > 0) {
                    if ($this->type === LOGIC_TREE_OR) {
                        $string .= ' OR ';
                    } else {
                        $string .= ' AND ';
                    }
                }
                $string .= $child->getString ($level + 1);
            }
        }
        if ($level > 0) $string .= ')';
        return $string;
    }
    
    /**
     * Adds another condition, modifiying the existing tree as required.
     * 
     * For example, if the new operator type is AND and this condition is under
     * an OR operator, the compaction before adding the new condition will be
     * "this OR that" (where that is the other condition under the existing OR),
     * and the compaction after will be "(this AND new_cond) OR that"
     * 
     * @param LogicConditionNode $node the new condition
     * @param int $condition_type either LOGIC_TREE_OR or LOGIC_TREE_AND
     */
    function addCondition (LogicConditionNode $node, $condition_type = LOGIC_TREE_OR) {
        
        switch ($condition_type) {
            case LOGIC_TREE_OR:
            case LOGIC_TREE_AND:
                break;
            default:
                throw new Exception ("Attempted to use addCondition with unknown type {$condition_type}");
        }
        
        if ($this->type == $condition_type) {
            
            // if condition types match, append condition
            $this->addChild ($node);
            
        } else {
            
            // otherwise, add a parent operator with the condition and this node as its children
            $old_parent = $this->parent;
            
            $new_parent = new LogicOperatorNode ($condition_type);
            
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
    
}

?>
