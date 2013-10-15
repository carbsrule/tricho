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
 * stores a list of tables
 * 
 * @package query_builder
 */
class QueryTableList {
    
    private $tables;
    
    function __construct () {
        $this->tables = array ();
    }
    
    function __toString () {
        
        $return_string = '';
        $i = 0;
        foreach ($this->tables as $table) {
            if ($i++ > 0) $return_string .= ', ';
            $return_string .= cast_to_string ($table);
        }
        
        return $return_string;
        
    }
    
    /**
     * finds a table given a name, and possibly an alias
     * 
     * @param string $table_name the name of the table to be found
     * @param mixed $alias if provided, only the table which has the specified
     *        name and alias will be returned
     * @return mixed a QueryTable if found, or null
     */
    function find ($table_name, $alias = null) {
        
        foreach ($this->tables as $table) {
            if ($table->getName () == $table_name) {
                
                if ($alias == null) {
                    return $table;
                } else if ($alias == $table->getAlias ()) {
                    return $table;
                }
                
            }
        }
        return false;
        
    }
    
    /**
     * creates a new table and adds it to the list, automatically generating an
     * alias if the table name already exists in the list.
     * 
     * @param string $table_name the name of the table to be added
     * @return QueryTable the new table that has been created and added to the
     *         list
     */
    function add ($table_name) {
        
        $new_table = new QueryTable ($table_name);
        
        // set up alias if the table name is already in the table list
        $existing = $this->find ($table_name);
        $alias = null;
        $alias_counter = 0;
        
        // check if an existing table with the same name in the list has an alias and add one if not
        // otherwise there will be two references to the same table and only one will be aliased
        // such a query will confuse programmers and probably the SQL engine too
        if ($existing != null and $existing->getAlias () == null) {
            $this->generateAlias ($existing);
        }
        
        // if there is at least one existing table, the new table will need an alias.
        // make the alias number for the new table = 1 + # of existing aliases
        while ($existing != null) {
            
            $alias = $table_name. '_A'. ++$alias_counter;
            $existing = $this->find ($table_name, $alias);
            
        }
        
        if ($alias != null) {
            $new_table->setAlias ($alias);
        }
        
        $this->tables[] = $new_table;
        
        return $new_table;
    }
    
    /**
     * Adds a unique alias for a table in the list, that is determined
     * automatically.
     * 
     * Since this is called every time a table is added to the list, it will
     * ensure that each duplicate reference to a table will have a unique alias
     */
    function generateAlias (QueryTable $table) {
        
        if ($table->getAlias () != '') return;
        
        $aliases = array ();
        
        foreach ($this->tables as $existing_table) {
            if ($existing_table !== $table) {
                $alias = $existing_table->getAlias ();
                if ($alias != '') {
                    $aliases[] = $alias;
                }
            }
        }
        
        $alias_counter = 1;
        $table_name = $table->getName ();
        
        do {
            $alias = $table_name. '_A'. $alias_counter++;
        } while (in_array ($alias, $aliases));
        
        $table->setAlias ($alias);
    }
    
}

?>
