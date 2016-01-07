<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Query;

use InvalidArgumentException;

class QueryCase extends QueryFunction {
    
    /**
     * @param string $function_name This exists only for compatibility reasons,
     *        and hence is ignored
     * @param array $params Unlike a {@see QueryFunction}, the keys are the
     *        comparison values, and the values are the results
     */
    function __construct($function_name, $params = []) {
        if (!is_array($params)) {
            throw new InvalidArgumentException('Params must be an array');
        }
        $this->function_name = (string) $function_name;
        $this->params = $params;
    }
    
    function __toString() {
        $case = 'CASE ' . $this->source->identify('select');
        foreach ($this->params as $value => $label) {
            $case .= ' WHEN ' . sql_enclose($value, false);
            $case .= ' THEN ' . sql_enclose($label, false);
        }
        $case .= ' END';
        return $case;
    }
}
