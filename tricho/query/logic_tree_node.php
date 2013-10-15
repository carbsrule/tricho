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
 * This is the base logic tree node class.
 * Both LogicConditionNode and LogicOperatorNode extend this class.
 * It provides basic tree functionality.
 * 
 * @package query_builder
 */
abstract class LogicTreeNode {
    
    protected $parent;
    protected $children;
    protected $type = null;
    
    function __construct () {
        $this->parent = null;
        $this->children = array ();
    }
    
    /**
     * Set the parent for this node
     *
     * @param LogicTreeNode $new_parent The new parent of this node
     */
    function setParent (LogicTreeNode $new_parent) {
        $this->parent = $new_parent;
    }
    
    /**
     * Get the parent of this node
     *
     * @return LogicTreeNode The parent node
     */
    function getParent () {
        return $this->parent;
    }
    
    /**
     * Add a child node to this node
     * Note: this method allows duplicates
     *
     * @param LogicTreeNode $new_child The child to add
     */
    function addChild (LogicTreeNode $new_child) {
        $new_child->setParent ($this);
        $this->children[] = $new_child;
    }
    
    /**
     * Return all the children of this node
     *
     * @return array The children of this node
     */
    function getChildren () {
        return $this->children;
    }
    
    /**
     * Gets a child based on the node number in the children list
     *
     * @param $id mixed A node identifier
     * @return LogicTreeNode The node
     */
    function getChild ($id) {
        if (isset ($this->children[$id])) {
            return $this->children[$id];
        } else {
            return null;
        }
    }
    
    /**
     * Remove a child node from under this node
     *
     * @param LogicTreeNode $rem_child The node to remove
     */
    function removeChild (LogicTreeNode $rem_child) {
        foreach ($this->children as $id => $child) {
            if ($rem_child === $child) {
                unset ($this->children[$id]);
                break;
            }
        }
    }
    
    /**
     * Get this node type
     *
     * @return int This node type (LOGIC_TREE_OR, LOGIC_TREE_AND, or
     *         LOGIC_TREE_COND)
     */
    function getType () {
        return $this->type;
    }
    
    
    /**
     * Get the conditions of this node
     *
     * @return array The various conditions related to this node
     */
    function getConditions () {
        $conditions = array ();
        if ($this instanceof LogicConditionNode) {
            $conditions[] = $this;
        }
        foreach ($this->children as $child) {
            $child_conditions = $child->getConditions ();
            $conditions = array_merge ($conditions, $child_conditions);
        }
        return $conditions;
    }
    
    
    public function __toString () {
        return __CLASS__;
    }
    
}

?>
