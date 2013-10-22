<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

class DatetimeColumn extends TemporalColumn {
    protected $has_date = true;
    protected $has_time = true;
    
    
    static function getAllowedSqlTypes() {
        return array('DATETIME', 'INT');
    }
    
    
    static function getDefaultSqlType() {
        return 'DATETIME';
    }
    
    function identify($context) {
        if ($context == 'row') return $this->name;
        if ($this->sqltype == SQL_TYPE_INT) {
            return 'FROM_UNIXTIME(`' .
                $this->table->getName() . '`.`' . $this->name . '`' .
                ') AS `' . $this->name . '`';
        }
        return parent::identify($context);
    }
    
    
    function displayValue($input_value = '') {
        if ($this->sqltype == SQL_TYPE_INT) {
            if ($input_value > 0) {
                return date('Y-m-d H:i:s', $input_value);
            }
            return '';
        }
        return parent::displayValue($input_value);
    }
}
