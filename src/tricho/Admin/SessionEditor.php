<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Admin;

class SessionEditor {
    static function otype($var) {
        if (is_array($var)) return 'array';
        if (is_string($var)) return 'string';
        if (is_int($var)) return 'int';
        if (is_float($var)) return 'float';
        if (is_bool($var)) return 'bool';
        if (is_null($var)) return 'null';
        if (is_object($var)) return get_class($var);
        if (is_resource($var)) return 'resource';
        return '??? (' . gettype($var) . ')';
    }
    
    
    /**
     * Displays a value for viewing editing or deleting
     * Strings, ints, and floats can be edited; everything else can be viewed.
     * @param mixed $value A value somewhere within the $_SESSION array
     * @param string $keypath The path to the value, separated by pipes (|)
     * @return void This function prints its output directly
     */
    static function display($value, $keypath = '') {
        static $scalars = ['string', 'int', 'float', 'bool'];
        
        $type = self::otype($value);
        
        $editable = true;
        if (starts_with($keypath, '_tricho')) $editable = false;
        if (starts_with($keypath, 'setup')) $editable = false;
        if (in_array($keypath, ['admin', 'admin|id'])) $editable = false;
        
        echo '<div class="session">';
        if ($keypath != '') {
            $pos = strrpos($keypath, '|');
            if ($pos !== false) {
                $name = substr($keypath, $pos + 1);
            } else {
                $name = $keypath;
            }
            if ($editable) {
                echo '<form method="post" action="session_edit_action.php">';
                echo '<input type="hidden" name="key" value="', hsc($keypath), '">';
                echo "<strong>", hsc($name), '</strong> ', hsc($type);
                echo ' <input type="submit" name="del" value="Delete">';
                echo "</form>";
            } else {
                echo "<p><strong>", hsc($name), '</strong> ', hsc($type), '</p>';
            }
        }
        
        if ($type == 'array') {
            foreach ($value as $key => $val) {
                $path = $keypath;
                if ($path) $path .= '|';
                $path .= $key;
                self::display($val, $path);
            }
        } else if (in_array($type, $scalars)) {
            if (!$editable) {
                echo "<p>", hsc($value), "</p></div>";
                return;
            }
            echo '<form method="post" action="session_edit_action.php">';
            echo '<input type="hidden" name="key" value="', hsc($keypath), '">';
            if ($type == 'bool') {
                echo '<select name="value">';
                $opts = [1 => 'true', 0 => 'false'];
                foreach ($opts as $opt => $opt_name) {
                    echo '<option value="', $opt, '"';
                    if ($value == $opt) echo ' selected="selected"';
                    echo '>', $opt_name, '</option>';
                }
                echo '</select>';
            } else {
                echo '<input name="value" value="', hsc($value), '">';
            }
            echo '<input type="submit" name="change" value="Change">';
            echo '</form>';
        } else if (is_object($value)) {
            if (method_exists($value, '__toString')) {
                echo "<p>", (string) $value, "</p>\n";
            } else {
                echo "<p>", spl_object_hash($value), "</p>\n";
            }
        }
        
        echo "</div>";
    }
}
