<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Query;

/**
 * A list of QueryFields
 * 
 * @package query_builder
 */
class QueryFieldList {
    
    private $fields;
    
    function __construct () {
        $this->fields = array ();
    }
    
    /**
     * Adds a field to the list
     *
     * @param QueryField $field The field to add
     */
    function add (QueryField $field) {
        $this->fields[] = $field;
    }
    
    /**
     * Removes a field from the list
     *
     * @param QueryField $rem_field The field to remove
     * @return bool true on success, false on failure
     */
    function remove (QueryField $rem_field) {
        foreach ($this->fields as $key => $field) {
            if ($field === $rem_field) {
                unset ($this->fields[$key]);
                return true;
                break;
            }
        }
        return false;
    }
    
    
    /**
     * Removes a field from the specified index
     * @param int $index The index of the field to remove
     */
    function removeAt ($index) {
        unset ($this->fields[$index]);
    }
    
    
    /**
     * Finds a field in the list
     *
     * @param string $field_full_name The full name (table.column, or alias) of
     *        the field to find
     * @param bool $ignore_pk_cols If true, aliased PK cols (_PKxx) will be
     *        ignored
     * @return mixed The matching {@link QueryField} if found, or null otherwise
     */
    function find ($field_full_name, $ignore_pk_cols = false) {
        
        if (strpos ($field_full_name, '.') !== false ) {
            list ($table_name, $field_name) = explode ('.', $field_full_name);
        } else {
            $field_name = $field_full_name;
            $table_name = '';
        }
        
        foreach ($this->fields as $field) {
            // echo "Checking field ", $field->getName (), "<br>\n";
            if ($field_name == $field->getName() or
                    ($field instanceof AliasedColumn
                    and $field_name == $field->getAlias())) {
                
                $table_name_match = false;
                if ($table_name == '') {
                    $table_name_match = true;
                } else if ($field instanceof Column and $table_name == $field->getTable()->getName()) {
                    $table_name_match = true;
                } else if ($field instanceof AliasedColumn) {
                    $table = $field->getTable();
                    if ($table_name == $table->getName()) {
                        $table_name_match = true;
                    } else if ($table instanceof AliasedTable
                            and $table_name == $table->getAlias()) {
                        $table_name_match = true;
                    }
                }
                
                if (!$table_name_match) continue;
                $alias_start = '';
                if ($field instanceof AliasedColumn) {
                    $alias_start = substr($field->getAlias(), 0, 3);
                }
                if (!$ignore_pk_cols or $alias_start != '_PK') {
                    return $field;
                }
            }
            
            // Support LinkColumns
            if ($field instanceof QueryFunction) {
                $source = $field->getSource();
                if ($source == null) continue;
                if ($source->getName() != $field_name) continue;
                if ($source->getTable()->getName() != $table_name) continue;
                return $source;
            }
        }
        
        return null;
        
    }

    public function __toString () {
        $out = __CLASS__. ' { ';

        $i = count ($this->fields);
        foreach ($this->fields as $f) {
            $out .= $f->getName ();
            if (--$i > 0) $out .= ', ';
        }

        return $out. ' }';
    }
    
}

?>
