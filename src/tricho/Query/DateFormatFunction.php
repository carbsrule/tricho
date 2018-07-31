<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Query;

use Exception;

/**
 * Shorthand for using DATE_FORMAT function in MySQL
 */
class DateFormatFunction extends QueryFunction {
    protected $date_format;


    /**
     * @param AliasedField $field DATE/TIME/DATETIME column to format
     * @todo Treat TIME/DATETIME fields correctly
     * @todo Use user locale for format style
     */
    function __construct(QueryField $field)
    {
        $params = [$field, '%e/%c/%Y'];

        if (!defined('UPPER_CASE_AM_PM') or UPPER_CASE_AM_PM === false) {
            $format = new QueryFunction('DATE_FORMAT', $params);
            parent::__construct('LOWER', $format);
        } else {
            parent::__construct('DATE_FORMAT', $params);
        }
    }
}
