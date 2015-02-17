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
 * The LogicTree encapsulates all of the objects required for the advanced
 * query building system.
 * Its nodes are {@link LogicTreeNode}s: {@link LogicOperatorNode}s are used
 * for AND and OR operators, and {@link LogicConditionNode}s are used for
 * conditions like A = B.
 * 
 * @package query_builder
 */
class LogicTree {
    
    private $root;
    
    function __construct () {
        $this->root = null;
    }
    
    /**
     * Gets a fragment of an SQL query that contains the clauses in the tree
     *
     * @return string
     */
    function __toString () {
        if ($this->root == null) {
            return '';
        } else {
            return cast_to_string ($this->root);
        }
    }
    
    /**
     * Get the root node for this tree
     *
     * @return mixed The root of this tree (a LogicTreeNode) if it exists, or
     *         null.
     */
    function getRoot () {
        return $this->root;
    }
    
    /**
     * Set the root node for this tree
     *
     * @param mixed $node The new root node for this tree - either a
     *        LogicTreeNode or null
     */
    function setRoot ($node) {
        if ($node === null or $node instanceof LogicTreeNode) {
            $this->root = $node;
        } else {
            throw new Exception ('Invalid root, must be LogicTreeNode or null');
        }
    }
    
    /**
     * Add a condition to the root node of this tree. If there is no root node,
     * it will be created
     *
     * @param LogicConditionNode $node the new condition
     * @param int $condition_type either LOGIC_TREE_OR or LOGIC_TREE_AND
     */
    function addCondition (LogicConditionNode $node, $condition_type = LOGIC_TREE_OR) {
        if ($this->root == null) {
            $this->root = new LogicOperatorNode ($condition_type);
            $this->root->addChild ($node);
        } else {
            $this->root->addCondition ($node, $condition_type);
            while ($this->root->getParent () !== null) {
                $this->root = $this->root->getParent ();
            }
        }
    }
    
    
    /**
     * Create a new condition and add it to the root node of this tree. If
     * there is no root node, it will be created.
     *
     * @param QueryField $col the field on which the condition applies
     * @param int $operator the constant definition of the type of condition,
     *        e.g. LOGIC_CONDITION_GT
     * @param mixed $values a {@link QueryField} for most condition types, or
     *        an array of {@link QueryField}s for LOGIC_CONDITION_[NOT_]BETWEEN
     *        (2 elements) or LOGIC_CONDITION_IN (1+ elements)
     * @param int $condition_type either LOGIC_TREE_OR or LOGIC_TREE_AND
     * @return LogicConditionNode The created node
     */
    function addNewCondition(QueryField $col, $operator, $values, $condition_type = LOGIC_TREE_OR) {
        $node = new LogicConditionNode($col, $operator, $values);
        
        if ($this->root == null) {
            $this->root = new LogicOperatorNode($condition_type);
            $this->root->addChild($node);
        } else {
            $this->root->addCondition($node, $condition_type);
            while ($this->root->getParent() !== null) {
                $this->root = $this->root->getParent();
            }
        }
        return $node;
    }
    
    
    /**
     * Get the conditions in this logic tree
     *
     * @return array The conditions of this tree
     */
    function getConditions () {
        if ($this->root == null) {
            return array ();
        } else {
            return $this->root->getConditions ();
        }
    }
    
}

?>
