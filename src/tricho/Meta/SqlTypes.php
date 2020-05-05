<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

class SqlTypes
{
    static function isValid($type)
    {
        return in_array($type, self::getAll());
    }
    
    
    static function isText($type)
    {
        return in_array($type, ['TEXT', 'TINYTEXT', 'MEDIUMTEXT', 'LONGTEXT']);
    }
    
    
    static function isBlob($type)
    {
        return in_array($type, ['BLOB', 'TINYBLOB', 'MEDIUMBLOB', 'LONGBLOB']);
    }
    
    
    static function isTemporal($type)
    {
        return in_array($type, ['DATE', 'DATETIME', 'TIME']);
    }
    
    
    /**
     * Determines if the type can store binary data
     * @param string $type
     */
    static function isBinary($type)
    {
        return in_array($type, ['BINARY', 'VARBINARY', 'BLOB', 'TINYBLOB', 'MEDIUMBLOB', 'LONGBLOB']);
    }
    
    
    static function getAll()
    {
        return [
            'INT', 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'BIGINT',
            'BIT', 'DECIMAL', 'FLOAT', 'DOUBLE',
            'CHAR', 'VARCHAR', 'TEXT', 'TINYTEXT', 'MEDIUMTEXT', 'LONGTEXT',
            'BINARY', 'VARBINARY', 'BLOB', 'TINYBLOB', 'MEDIUMBLOB', 'LONGBLOB',
            'DATE', 'DATETIME', 'TIME',
            'ENUM', 'SET',
        ];
    }
    
    
    static function getSized()
    {
        return ['DECIMAL', 'VARCHAR', 'CHAR', 'BINARY', 'VARBINARY'];
    }
    
    
    static function getTextual()
    {
        return ['CHAR', 'VARCHAR', 'TEXT', 'TINYTEXT', 'MEDIUMTEXT', 'LONGTEXT'];
    }
    
    
    static function getTextish()
    {
        return ['CHAR', 'VARCHAR', 'TEXT', 'TINYTEXT', 'MEDIUMTEXT',
            'LONGTEXT', 'BLOB', 'TINYBLOB', 'MEDIUMBLOB', 'LONGBLOB'];
    }
    
    
    static function getAutoIncrementable()
    {
        return ['INT', 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'BIGINT'];
    }
    
    
    static function getUnsignable()
    {
        return ['INT', 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'BIGINT', 'DECIMAL', 'FLOAT', 'DOUBLE'];
    }
    
    
    static function getNumeric()
    {
        return ['INTEGER', 'TINYINT', 'SMALLINT', 'MEDIUMINT', 'BIGINT', 'BIT',
            'DECIMAL', 'FLOAT', 'DOUBLE'];
    }
}
