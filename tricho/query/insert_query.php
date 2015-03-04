<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Meta\Table;

class InsertQuery extends Query {
    protected $table;
    protected $fields;
    
    function __construct($table, array $fields) {
        if ($table instanceof Table) {
            $table = $table->getName();
        }
        if (!is_string($table)) {
            $error = '$table must be a Table or string';
            throw new InvalidArgumentException($error);
        }
        $this->table = $table;
        $this->fields = $fields;
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
        if ($conn instanceof MysqlConn) {
            $table = $conn->quote_ident($this->table);
            $q = "INSERT INTO {$table} SET";
            $field_num = 0;
            foreach ($this->fields as $field => $value) {
                if (++$field_num != 1) $q .= ',';
                $field = $conn->quote_ident($field);
                $q .= "\n    {$field} = ";
                if ($value instanceof QueryField) {
                    $q .= $value->identify('insert');
                } else {
                    $q .= $conn->quote($value);
                }
            }
        } else {
            $table = $conn->quote_ident($this->table);
            $q = "INSERT INTO {$table}";
            if (count($this->fields) > 0) {
                $q .= ' (';
                $field_num = 0;
                foreach ($this->fields as $field => $value) {
                    if (++$field_num != 1) $q .= ', ';
                    $q .= $conn->quote_ident($field);
                }
                $q .= ')';
            }
            $q .= ' VALUES (';
            $field_num = 0;
            foreach ($this->fields as $value) {
                if (++$field_num != 1) $q .= ', ';
                $q .= $conn->quote($value);
            }
            $q .= ')';
        }
        return $q;
    }
}
