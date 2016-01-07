<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho;

use InvalidArgumentException;

/**
 * Used to process numeric values stored in strings
 * (e.g. large integers, or fixed point decimal numbers)
 * @author benno 2011-11-18
 */
class StringNumber {
    
    /**
     * Compares two numbers stored as strings
     * @param string $num1
     * @param string $num2
     * @return int One of three values:
     * - 0 if the two numbers are equal
     * - +1 if the first is greater
     * - -1 if the first is lesser
     */
    static function compare ($num1, $num2) {
        $value1 = (string) $num1;
        $value2 = (string) $num2;
        $negative1 = false;
        $negative2 = false;
        if ($value1[0] == '-') {
            $value1 = substr ($value1, 1);
            $negative1 = true;
        }
        if ($value2[0] == '-') {
            $value2 = substr ($value2, 1);
            $negative2 = true;
        }
        
        if ($value1[0] == '.') $value1 = '0' + $value1;
        if ($value2[0] == '.') $value2 = '0' + $value2;
        
        $decimal_pattern = '/^[0-9]+(?:\.[0-9]+)?$/';
        if (!preg_match ($decimal_pattern, $value1)) {
            throw new InvalidArgumentException ("{$num1} is not a valid decimal number");
        }
        if (!preg_match ($decimal_pattern, $value2)) {
            throw new InvalidArgumentException ("{$num2} is not a valid decimal number");
        }
        
        @list($int1, $frac1) = explode('.', $value1);
        @list($int2, $frac2) = explode('.', $value2);
        
        $int_len1 = strlen($int1);
        $int_len2 = strlen($int2);
        $frac_len1 = strlen($frac1);
        $frac_len2 = strlen($frac2);
        
        // make sure integer and fractional parts are the same length in both
        // numbers, to facilitate string comparison
        
        if ($int_len1 < $int_len2) {
            $int1 = str_pad ($int1, $int_len2, '0', STR_PAD_LEFT);
        }
        if ($int_len1 > $int_len2) {
            $int2 = str_pad ($int2, $int_len1, '0', STR_PAD_LEFT);
        }
        
        if ($frac_len1 < $frac_len2) {
            $frac1 = str_pad ($frac1, $frac_len2, '0', STR_PAD_RIGHT);
        }
        if ($frac_len1 > $frac_len2) {
            $frac2 = str_pad ($frac2, $frac_len1, '0', STR_PAD_RIGHT);
        }
        
        // A < Z so use A for -ve numbers and Z for positive
        // (N.B. In ASCII, '-' > '+' so standard signs can't be compared)
        $sign1 = ($negative1? 'A': 'Z');
        $sign2 = ($negative2? 'A': 'Z');
        $value1 = "{$sign1}{$int1}.{$frac1}";
        $value2 = "{$sign2}{$int2}.{$frac2}";
        
        $result = strcmp ($value1, $value2);
        
        // comparison is based on absolute values and so is reversed if both
        // numbers are negative, e.g. |-1| < |-3| but -1 > -3
        if ($negative1 and $negative2) {
            $result = -1 * $result;
        }
        
        return $result;
    }
    
}
?>
