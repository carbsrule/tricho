<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Query;

/**
 * represents an aliased SQL database table
 * 
 * @package query_builder
 */
class AliasedTable implements QueryTable {
    
    protected $table;
    protected $alias;
    protected $columns;
    
    /**
     * @param string $name The table name
     */
    function __construct (QueryTable $table, $alias) {
        $this->table = $table;
        $this->alias = (string) $alias;
    }
    
    /**
     * @return string
     */
    function __toString () {
        return $this->table->getName(). ($this->alias != ''? ': '. $this->alias: '');
    }
    
    /**
     * Gets the name of this table
     *
     * @return string The table that this aliases
     */
    function getTable () {
        return $this->table;
    }
    
    /**
     * Sets the alias to use for this table
     *
     * @param string $name The alias for this table
     */
    function setAlias ($alias) {
        $this->alias = (string) $alias;
    }
    
    /**
     * Gets the alias used for this table
     *
     * @return string The alias of this table
     */
    function getAlias () {
        return $this->alias;
    }
    
    
    /**
     * @return AliasedColumn
     */
    function get($name) {
        $col = $this->table->get($name);
        
        // TODO: use a Column (not a name, alias, and type)
        $aliased = new AliasedColumn();
        
    }
    
    
    function getRowIdentifier() {
        return $this->table->getRowIdentifier();
    }
    
    
    /**
     * Identify this table in a specific context
     *
     * @param string $context The context to identify this table in:
     *        'select' (FROM x or JOIN y)
     *        'normal' (everywhere else)
     */
    function identify ($context) {
        $name = $this->getTable()->getName();
        switch (strtolower($context)) {
            case 'select':
                return '`'. $name. '`'. ($this->alias? ' AS `'. $this->alias. '`': '');
                break;
            case 'normal':
                return '`'. ($this->alias? $this->alias: $name). '`';
                break;
            default:
                throw new Exception ("Invalid context {$context}, must be 'select' ".
                    "or 'normal'");
        }
    }
    
}

?>
