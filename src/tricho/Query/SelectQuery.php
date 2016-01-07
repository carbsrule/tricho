<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Query;

use Exception;
use InvalidArgumentException;

use Tricho\Meta\Column;
use Tricho\Meta\LinkColumn;

/**
 * used to build queries more easily than hand-coding
 * 
 * @package query_builder
 */
class SelectQuery extends Query {
    
    private $select_fields;
    private $table;
    private $joins;
    private $where;
    private $order_by;
    private $group_by;
    private $limit;
    private $distinct;
    
    function __construct (QueryTable $table) {
        
        $this->select_fields = array ();
        $this->table = $table;
        $this->joins = array ();
        $this->where = new LogicTree ();
        $this->order_by = array ();
        $this->group_by = array ();
        $this->limit = null;
        $this->distinct = false;
    }
    
    /**
     * @return string
     */
    function __toString () {
        
        $result = 'SELECT ';
        if ($this->distinct) $result .= 'DISTINCT ';
        $line = '';
        $col_count = 0;
        if (count($this->joins) == 0) {
            $basic_query = true;
        } else {
            $basic_query = false;
        }
        
        if ($this->table instanceof AliasedTable) {
            $basic_query = false;
        }
        
        foreach ($this->select_fields as $field) {
            if ($col_count++ > 0) {
                $result .= ', ';
            }
            
            // we use get_class instead of instanceof, because this condition should only succeed
            // in the case of QueryColumn. Its sub-classes (e.g. DateTimeQueryColumn) should go through the else
            if (get_class ($field) == 'QueryColumn' and $basic_query) {
                // Don't bother using full table.column identification if all columns are from the same table
                $select_name = '`' . $field->getName () . '`';
                if ($field->getAlias () != '') {
                    $select_name .= ' AS `'. $field->getAlias (). '`';
                }
                $result .= $field->identify ('select');
                
            } else {
                $result .= $field->identify ('select');
            }
        }
        
        if ($this->table != null) {
            $result .= "\nFROM ". $this->table->identify ('select');
            
            // joins
            foreach ($this->joins as $join) {
                $table = $join->getTable ();
                $result .= "\n";
                switch ($join->getType ()) {
                    case SQL_JOIN_TYPE_INNER:
                        $result .= 'INNER JOIN ';
                        break;
                    case SQL_JOIN_TYPE_LEFT:
                        $result .= 'LEFT JOIN ';
                        break;
                    default:
                        throw new Exception ('Invalid join type: '. $join->getType ());
                }
                $result .= $table->identify ('select');
                $clauses = cast_to_string ($join->getClauses ());
                if ($clauses != '') $result .= ' ON '. $clauses;
            }
            
            // where
            $where = cast_to_string ($this->where);
            if ($where != '') $result .= "\nWHERE ". $where;
            
            // group by clause
            if (count($this->group_by) > 0) {
                $result .= "\nGROUP BY ";
                $group_by_num = 0;
                foreach ($this->group_by as $group_col) {
                    if ($group_by_num++ > 0) $result .= ', ';
                    $result .= $group_col->identify ('order_by');
                }
            }
            
            // order by clause
            if (count($this->order_by) > 0) {
                $result .= "\nORDER BY ";
                $order_by_num = 0;
                foreach ($this->order_by as $order_col) {
                    if (is_array ($order_col)) {
                        foreach ($order_col as $col) {
                            if ($order_by_num++ > 0) $result .= ', ';
                            $result .= cast_to_string ($col);
                        }
                    } else {
                        if ($order_by_num++ > 0) $result .= ', ';
                        $result .= cast_to_string ($order_col);
                    }
                }
            }
            
            // limit
            if ($this->limit !== null) {
                $result .= "\nLIMIT ". $this->limit;
            }
            
        }
        
        return $result;
    }
    
    // Build an appropriate SQL query handler with the sections:
    // * select columns
    // * base table and joins
    // * where clauses
    // * group by clauses
    // * having clauses
    // * order by clauses
    // * a limit clause
    // logic-based clauses (join, where, having) are to use the logic tree
    // can just store column lists (for group by) as array
    // use a class for the order by list (contains column name and direction)
    // need a method that drops off the select list and instead gives a count
    
    /**
     * adds a select field (i.e. SELECT select_field1, select_field2, ...
     * FROM ...)
     * 
     * @param QueryField $column the new field to add to the SELECT list
     */
    function addSelectField (QueryField $column) {
        $this->select_fields[] = $column;
    }
    
    /**
     * Finds select fields
     * 
     * @param mixed $table the table to which the column belongs. This
     *        parameter can be null, a table name, or a {@link QueryTable}. If
     *        a null value is provided, {@link QueryFunction}s and
     *        {@link QueryFieldLiteral}s may be returned in addition to
     *        {@link QueryColumn}s
     * @param string $column the name or alias of the desired field
     * @param int $type the type of fields that are allowed. This is a logical
     *        combination of the following: FIND_SELECT_TYPE_ANY (default),
     *        FIND_SELECT_TYPE_COLUMN, FIND_SELECT_TYPE_LITERAL, or
     *        FIND_SELECT_TYPE_FUNCTION. If functions and literals are allowed,
     *        the $table parameter will be ignored when matching
     *        {@link QueryFunction}s and {@link QueryFieldLiteral}s.
     * @param bool $allow_alias whether or not to allow matching on aliases -
     *        defaults to true. Note that if you supply a {@link QueryTable} as
     *        the $table parameter, only column aliases will be checked,
     *        otherwise both table and column aliases will be checked.
     * @return array the matching {@link QueryField}s
     */
    function findSelectFields ($table, $column, $type = FIND_SELECT_TYPE_ANY, $allow_alias = true) {
        
        // check inputs
        $use_table_object = false;
        if ($table instanceOf QueryTable) {
            $use_table_object = true;
        } else if ($table != null and !is_string ($table)) {
            throw new Exception ('$table parameter must be a QueryTable, a string, or null');
        }
        
        if (!is_string ($column) or strlen ($column) == 0) {
            throw new Exception ('$column parameter must be a non-empty string');
        }
        
        // find columns
        $matches = array ();
        foreach ($this->select_fields as $field) {
            
            if ($field instanceOf QueryColumn and ($type & FIND_SELECT_TYPE_COLUMN)) {
                
                $table_match = false;
                if ($column == $field->getName () or ($allow_alias and $column == $field->getAlias ())) {
                    if ($use_table_object) {
                        if ($table === $field->getTable ()) {
                            $matches[] = $field;
                        }
                    } else if ($table == '') {
                        $matches[] = $field;
                    } else if ($table == $field->getTable ()->getName () or
                            ($allow_alias and $table == $field->getTable ()->getAlias ())) {
                        $matches[] = $field;
                    }
                }
                
            } else if ($field instanceof QueryFunction and ($type & FIND_SELECT_TYPE_FUNCTION)) {
                
                if ($column == cast_to_string ($field) or ($allow_alias and $column == $field->getAlias ())) {
                    $matches[] = $field;
                }
                
            } else if ($field instanceof QueryFieldLiteral and ($type & FIND_SELECT_TYPE_LITERAL)) {
                
                if ("'". $column. "'" == $field->getName ()) {
                    $matches[] = $field;
                }
                
            }
            
        }
        
        return $matches;
        
    }
    
    /**
     * Removes a single select field
     * 
     * @param QueryField $field_to_remove the field to remove
     * 
     * @return bool true if the specified field was found and removed
     */
    function removeSelectField ($field_to_remove) {
        foreach ($this->select_fields as $id => $field) {
            if ($field === $field_to_remove) {
                unset ($this->select_fields[$id]);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Clears the list of SELECT fields, so you can start again from scratch
     */
    function removeAllSelectFields () {
        $this->select_fields = array ();
    }
    
    /**
     * sets the base table for the query (i.e. SELECT ... FROM base_table)
     * 
     * @param QueryTable $table the table to use as the base table
     */
    function setBaseTable (QueryTable $table) {
        $this->table = $table;
    }
    
    /**
     * gets the base table of the query (see {@link setBaseTable})
     * 
     * @return QueryTable
     */
    function getBaseTable () {
        return $this->table;
    }
    
    /**
     * adds a join to the query
     * 
     * @param QueryJoin $join the join to add
     */
    function addJoin (QueryJoin $join) {
        $this->joins[] = $join;
    }
    
    /**
     * gets the first join to a table
     * 
     * @param QueryTable $table the table to find a join to
     * @return mixed the QueryJoin if there is one, or null
     */
    function getJoin (QueryTable $table) {
        foreach ($this->joins as $join) {
            if ($join->getTable () === $table) {
                return $join;
            }
        }
        return null;
    }
    
    /**
     * Gets all the joins that are used in this SelectQuery
     *
     * @return array of QueryJoin
     */
    function getAllJoins () {
        return $this->joins;
    }
    
    /**
     * Remove joins from the query.
     * 
     * @param mixed $join_type SQL_JOIN_TYPE_INNER or SQL_JOIN_TYPE_LEFT.
     *        Anything else will remove all joins.
     */
    function dropJoins ($join_type = null) {
        switch ($join_type) {
            
            case SQL_JOIN_TYPE_INNER:
            case SQL_JOIN_TYPE_LEFT:
                foreach ($this->joins as &$join) {
                    if ($join->getType () === $join_type) {
                        unset ($join);
                    }
                }
                break;
            
            default:
                $this->joins = array ();
        }
    }
    
    /**
     * gets the LogicTree used to build the WHERE clause
     * 
     * @return LogicTree the logic for the WHERE clause
     */
    function getWhere () {
        return $this->where;
    }
    
    /**
     * adds order column(s) to the ORDER BY clause
     * 
     * @param mixed $col an OrderColumn or an array of OrderColumn objects to
     *        add
     * @param mixed $key the position in the list of order by columns that the
     *        first parameter should take. If null (which is the default), the
     *        first parameter is added to the end of the list. Note that an
     *        array will only occupy a single position until the query is
     *        converted to a string
     */
    function addOrderBy ($col, $key = null) {
        if (is_array ($col)) {
            foreach ($col as $column) {
                if (!$column instanceof OrderColumn) {
                    throw new Exception ("Each array element must be an OrderColumn object.\n");
                }
            }
        } else {
            if (!$col instanceof OrderColumn) {
                throw new Exception ("The first parameter must be an OrderColumn or an array of OrderColumn objects.\n");
            }
        }
        
        if ($key === null) {
            $this->order_by[] = $col;
        } else {
            $this->order_by[$key] = $col;
            // May need to re-sort if numeric keys don't order themselves nicely
            ksort ($this->order_by);
        }
    }
    
    /**
     * Clears the list of ORDER BY columns
     */
    function clearOrderBy () {
        $this->order_by = array ();
    }
    
    
    /**
     * Gets the ORDER BY columns
     * 
     * @return array
     */
    function getOrderBy () {
        return $this->order_by;
    }
    
    
    /**
     * sets a LIMIT clause for the query
     * 
     * @param mixed $limit null if no limit is to be applied, otherwise one of
     *        three formats:
     *        (1) X
     *        (2) X, Y
     *        (3) X OFFSET Y
     */
    function setLimit ($limit) {
        if ($limit == null) {
            $this->limit = null;
        } else if (preg_match ('/^([0-9]+,\s*)?[0-9]+$/', $limit)) {
            $this->limit = (string) $limit;
        } else if (preg_match ('/^[0-9]+\s+OFFSET\s+[0-9]+$/i', $limit)) {
            $this->limit = (string) $limit;
        } else {
            throw new Exception ('Invalid parameter: '. $limit);
        }
    }
    
    /**
     * gets the LIMIT clause for the query
     * 
     * @return string
     */
    function getLimit () {
        return $this->limit;
    }
    
    /**
     * Gets the specified select field, by the alias.
     *
     * @param string $alias The alias to search for
     * @return QueryField The select field, or null if the field was not found
     */
    function getSelectFieldByAlias ($alias) {
        foreach ($this->select_fields as $field) {
            if ($field->getAlias () == $alias) {
                return $field;
            }
        }
        return null;
    }
    
    /**
     * Gets the list of select fields.
     *
     * @return array an array of {@link QueryField}s
     */
    function getSelectFields () {
        return $this->select_fields;
    }
    
    /**
     * Sets the 'distinct' parameter. Set to True for a SELECT DISTINCT and
     * false for a SELECT. Default: SELECT
     * @param bool $value The new value
     */
    function setDistinct ($value) {
        $this->distinct = (bool) $value;
    }
        
    /**
     * finds a join from one column to another
     * 
     * @param string $from a full column name (table.column) from the base of
     *        the join
     * @param string $to a full column name (table.column) from the joined
     *        table (i.e. this column will be specified in the ON clause for
     *        the join)
     * @return array An array containing two elements:
     *         [0] QueryColumn belonging to the base of the join
     *         [1] QueryColumn belonging to the joined table
     *         Note that both elements will be null if no match was found
     */
    function findJoin ($from, $to) {
        
        if (!is_string ($from) or !is_string ($to)) {
            throw new Exception ("Both parameters must be strings of the form Table.Column");
        }
        
        list ($to_table, $to_column) = explode ('.', $to);
        
        foreach ($this->joins as $join) {
            $joined_table = $join->getTable ();
            if ($joined_table->getName () == $to_table or $joined_table->getAlias () == $to_table) {
                $join_conditions = $join->getClauses ()->getConditions ();
                
                foreach ($join_conditions as $condition) {
                    
                    $lhs = $condition->getLHS ();
                    $rhs = $condition->getRHS ();
                    
                    if ($lhs instanceof QueryColumn and $rhs instanceof QueryColumn) {
                        if ($lhs->matchesFullColumnName ($from) and $rhs->matchesFullColumnName ($to)) {
                            return array ($lhs, $rhs);
                        } else if ($rhs->matchesFullColumnName ($from) and $lhs->matchesFullColumnName ($to)) {
                            return array ($rhs, $lhs);
                        }
                    }
                }
            }
        }
        return array (null, null);
    }
    
    
    /**
     * Automatically adds a join, determining the right ON clause, and creating
     * any necessary table aliases, if the target table has already been
     * referenced in the query
     */
    function autoJoin(LinkColumn $col) {
        if (!($col instanceof Column or $col instanceof AliasedColumn)) {
            $err = 'Must be a Column or AliasedColumn';
            throw new InvalidArgumentException($err);
        }
        
        // Check for extant references to the target table
        // The new join must have a unique alias
        $reference_count = 0;
        $target_col = $col->getTarget();
        $target_table = $target_col->getTable();
        $base = $this->table;
        if ($target_table->matches($base)) ++$reference_count;
        foreach ($this->joins as $join) {
            if ($target_table->matches($join->getTable())) {
                ++$reference_count;
            }
        }
        $clauses = new LogicTree();
        if ($reference_count == 0) {
            $clause = new LogicConditionNode($col, LOGIC_CONDITION_EQ, $target_col);
        } else {
            $alias = $target_table->getName() . ($reference_count + 1);
            $target_table = new AliasedTable($target_table, $alias);
            $target = new AliasedColumn($target_table, $target_col);
            $clause = new LogicConditionNode($col, LOGIC_CONDITION_EQ, $target);
        }
        $clauses->setRoot($clause);
        $join = new QueryJoin($target_table, $clauses);
        $this->joins[] = $join;
        return $join;
    }
    
    
    /**
     * Adds a QueryField to the GROUP BY clause of the query
     *
     * @param QueryField $query_field The query field to add to the GROUP BY
     *        clause
     */
    function addGroupBy ($query_field) {
        $this->group_by[] = $query_field;
    }
    
    /**
     * Returns an array of QueryField objects which make up the GROUP BY clause
     * of the query
     *
     * @return array The QueryField objects
     */
    function getGroupBy () {
        return $this->group_by;
    }
    
    /**
     * Removes all the QueryFields from the GROUP BY clause.
     */
    function clearGroupBy () {
        $this->group_by = array ();
    }
    
    /**
     * Traverses linked columns to implement the 'using linked table' ordering
     * mechanism.
     * 
     * @param Column $column The column that links to the linked table
     * @param array $linked_ordering The array that will store all the
     *        OrderColumn objects
     */
    function implementLinkedOrdering (Column $column, &$linked_ordering) {
        $link_data = $column->getLink ();
        $to_column = $link_data->getToColumn ();
        $to_table = $to_column->getTable ();
        $from_table = $link_data->getFromColumn ();
        
        $from_name = $column->getTable ()->getName (). '.'. $column->getName ();
        $to_name = $to_table->getName (). '.'. $to_column->getName ();
        list ($from, $to) = $this->autoJoin ($from_name, $to_name);
        $q_table = $to->getTable ();
        
        $order_cols = $to_table->getOrder ('view');
        foreach ($order_cols as $order_col_details) {
            list ($col, $direction) = $order_col_details;
            $link = $col->getLink ();
            if ($link != null and $link->isParent ()) {
                $this->implementLinkedOrdering ($col, $linked_ordering);
            } else {
                $q_col = new QueryColumn ($q_table, $col->getName ());
                if ($direction == "DESC") {
                    $direction = ORDER_DIR_DESC;
                } else {
                    $direction = ORDER_DIR_ASC;
                }
                $order_column = new OrderColumn ($q_col, $direction);
                $linked_ordering[] = $order_column;
            }
        }
    }
}

?>
