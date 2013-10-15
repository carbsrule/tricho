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
 * used to represent an actual column in a query (e.g. MyTable.MyColumn)
 * 
 * @package query_builder
 */
class AliasedColumn extends AliasedField implements QueryField {
    protected $table;
    protected $column;
    
    /**
     * @param QueryTable $table the table to which this column will belong.
     *        This is particularly relevant when the AliasedColumn will belong
     *        to an AliasedTable.
     * @param Column $col the column to alias
     * @param string $alias the alias you want to use to describe the column.
     *        An empty string signifies that no alias will be used.
     */
    function __construct (QueryTable $table, Column $col, $alias = '') {
        $this->table = $table;
        $this->column = $col;
        $this->alias = $alias;
    }
    
    function __toString () {
        if ($this->alias == '') {
            return $this->table->identify('normal') . '.`' .
                $this->column->getName() . '`';
        } else {
            return $this->table->identify('normal') . '`' . $this->alias . '`';
        }
    }
    
    
    /**
     * Identifies the column in a particular context
     * 
     * For example, MyTable.MyColumn with alias MyAlias would be returned as
     * "MyTable.MyColumn MyAlias" in 'select', which is used for listing the
     * SELECT columns. It would be returned as "MyTable.MyColumn" in 'normal',
     * which is used for performing JOINs. It would be returned as "MyAlias" in
     * 'row', which is used when looping through the result set.
     * 
     * @param string $context: the context used to identify the column:
     *        'select', 'normal', 'order_by', or 'row'.
     * @return string
     */
    function identify ($context) {
        
        switch (strtolower($context)) {
            
            case 'select':
                return $this->table->identify('normal') .
                    '.`' . $this->column->getName() . '`' .
                    ($this->alias != '' ? ' AS `'. $this->alias. '`': '');
                break;
                
            case 'normal':
                return $this->table->identify ('normal'). '.`'.
                    ($this->alias != ''? $this->alias: $this->column->getName()) . '`';
                break;
            
            case 'param':
                return $this->table->identify ('normal') . '.`' .
                    $this->column->getName() . '`';
                break;
                
            case 'order_by':
                if ($this->alias != '') {
                    return '`'. $this->alias. '`';
                } else {
                    return $this->table->identify ('normal') . '.`' .
                        $this->column->getName() . '`';
                }
                break;
                
            case 'row':
                return $this->alias == ''? $this->name: $this->alias;
                break;
                
            default:
                throw new Exception ("Invalid context {$context}, must be ".
                    "'select', 'normal', 'order_by', 'param' or 'row'");
        }
    }
    
    /**
     * Gets the table which holds this column
     * 
     * @return QueryTable
     */
    function getTable () {
        return $this->table;
    }
    
    
    /**
     * Returns the name of the column this references
     */
    function getName () {
        return $this->column->getName();
    }
    
    /**
     * Returns true if this column is the specified column
     * 
     * @param string $name a full column name, i.e. MyTable.MyColumn
     * @return bool true if this is the desired column
     */
    function matchesFullColumnName ($name) {
        list ($table, $column) = explode ('.', $name);
        if ($this->table->getName () != $table and $this->table->getAlias () != $table) {
            return false;
        }
        if ($column != $this->name and $column != $this->alias) {
            return false;
        }
        return true;
    }
}

?>
