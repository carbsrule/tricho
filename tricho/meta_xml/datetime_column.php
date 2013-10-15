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
        return array('DATETIME');
    }
    
    
    static function getDefaultSqlType() {
        return 'DATETIME';
    }
}
