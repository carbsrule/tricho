<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Query;

use Exception;
use InvalidArgumentException;

use Tricho\DbConn\ConnManager;
use Tricho\Meta\Table;

class UpdateQuery extends Query {
    protected $table;
    protected $fields;
    protected $pk;
    
    function __construct(Table $table, array $fields, $pk) {
        $pk_error = false;
        if (is_array($pk)) {
            foreach ($pk as $pk_val) {
                if (!is_string($pk_val) and !is_int($pk_val)) {
                    $pk_error = true;
                    break;
                }
            }
        } else if (!is_string($pk) and !is_int($pk)) {
            $pk_error = true;
        }
        if ($pk_error) {
            $error = '$pk must be an int or string, or an array of them';
            throw new InvalidArgumentException($error);
        }
        $this->table = $table;
        $this->fields = $fields;
        $this->pk = $pk;
    }
    
    
    function set($field, $value) {
        if ($this->toString_done) {
            throw new Exception('Already generated query string');
        }
        $this->fields[$field] = $value;
    }
    
    
    function __toString() {
        $conn = $this->conn;
        if ($conn == null) $conn = ConnManager::get_active();
        $table = $conn->quote_ident($this->table);
        $q = "UPDATE {$table} SET";
        $field_num = 0;
        foreach ($this->fields as $field => $value) {
            if (++$field_num != 1) $q .= ',';
            $field = $conn->quote_ident($field);
            $q .= "\n    {$field} = ";
            if ($value instanceof QueryField) {
                $q .= $value->identify('update');
            } else {
                $q .= $conn->quote($value);
            }
        }
        $q .= "\nWHERE ";
        $pk_fields = ($this->table->getPKnames());
        if (count($pk_fields) == 0) {
            throw new Exception('No PKs!');
        } else if (count($pk_fields) == 1) {
            if (is_array($this->pk)) {
                if (count($this->pk) != 1) throw new Exception('PK mismatch');
                $value = reset($this->pk);
            } else {
                $value = $this->pk;
            }
            $field = reset($pk_fields);
            $q .= $conn->quote_ident($field) . ' = ' . $conn->quote($value);
        } else {
            if (!is_array($this->pk) or count($this->pk) != count($pk_fields)) {
                throw new Exception('PK mismatch');
            }
            $pk_vals = array_combine($pk_fields, $this->pk);
            $field_num = 0;
            foreach ($pk_vals as $field => $value) {
                if (++$field_num != 1) $q .= ' AND ';
                $q .= $conn->quote_ident($field) . ' = ' . $conn->quote($value);
            }
        }
        
        return $q;
    }
}
