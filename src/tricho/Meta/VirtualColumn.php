<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;


/**
 * A special "column" that simply provides form fields and validation,
 * never storing any information in the database
 */
abstract class VirtualColumn extends Column
{
    static function getAllowedSqlTypes()
    {
        return ['VIRTUAL'];
    }

    static function getDefaultSqlType()
    {
        // This is a lie
        return 'VIRTUAL';
    }

    function collateInput ($input, &$original_value)
    {
        return [];
    }

    /**
     * Ignore the type completely and always use VIRTUAL
     */
    function setSqlType($type)
    {
        $this->sqltype = $this->getDefaultSqlType();
        return true;
    }
}
