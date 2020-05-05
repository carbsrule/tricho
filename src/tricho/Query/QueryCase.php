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
     * @param mixed $default Default value if none of the cases match
     */
    function __construct($function_name, $params = [], $default = null) {
        if (!is_array($params)) {
            throw new InvalidArgumentException('Params must be an array');
        }
        $this->function_name = (string) $function_name;
        $this->params = $params;
        $this->default = $default;
    }

    function __toString() {
        $case = 'CASE ' . $this->source->identify('select');
        foreach ($this->params as $value => $label) {
            $case .= ' WHEN ' . sql_enclose($value, false);
            $case .= ' THEN ' . sql_enclose($label, false);
        }
        if ($this->default !== null) {
            $case .= ' ELSE ';
            if ($this->default instanceof QueryField) {
                $case .= $this->default->identify('select');
            } else {
                $case .= sql_enclose($this->default);
            }
        }
        $case .= ' END';
        return $case;
    }
}
