<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\DataUi;

use InvalidArgumentException;

class RandomString {
    protected $length;
    
    
    function __construct($length) {
        $length = (int) $length;
        if ($length < 1) {
            throw new InvalidArgumentException('Length must be at least 1');
        }
        $this->length = $length;
    }
    
    
    function generate() {
        $string = '';
        $valid = 'abcdefghijklmnopqrstuvwxyz';
        $valid .= strtoupper($valid);
        $max = strlen($valid) - 1;
        for ($i = 0; $i < $this->length; ++$i) {
            $string .= $valid[rand(0, $max)];
        }
        return $string;
    }
}
