<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho;

use Exception;
use InvalidArgumentException;
use ReflectionClass;

use Tricho\Meta\Table;

/**
 * Stores constant-ish information (configuration etc.) that needs to be
 * accessible site-wide. Some contents may be set to read-only during site
 * initialisation, and still others may be changed by program flow.
 * @author benno
 * @todo move help text into a Form class that describes the ViewItems on an
 *             add/edit form.
 */
class Runtime {
    static private $data = array();
    static private $read_only = array();
    static private $column_classes = array();
    static private $help_text = array();
    
    static function is_set($key) {
        return isset(self::$data[$key]);
    }
    
    
    /**
     * Sets a runtime value
     * @param string $key
     * @param mixed $value
     * @param bool $read_only If true, once set, the value can't be modified
     */
    static function set($key, $value, $read_only = false) {
        $key = (string) $key;
        if (isset(self::$data[$key]) and in_array($key, self::$read_only)) {
            throw new \InvalidArgumentException('Overwrite read-only value');
        }
        
        self::$data[$key] = $value;
        
        if ($read_only) self::$read_only[] = $key;
    }
    
    /**
     * Adds an item to an array value
     */
    static function add($key, $value) {
        $key = (string) $key;
        if (isset(self::$data[$key]) and in_array($key, self::$read_only)) {
            throw new \InvalidArgumentException('Modify read-only value');
        }
        
        if (!isset(self::$data[$key])) self::$data[$key] = array();
        self::$data[$key][] = $value;
    }
    
    
    /**
     * Gets a runtime value
     * @param string $key
     */
    static function get($key) {
        $key = (string) $key;
        if (!isset(self::$data[$key])) {
            throw new \InvalidArgumentException('Unknown value: ' . $key);
        }
        return self::$data[$key];
    }
    
    
    /**
     * Gets all runtime values
     * @return array
     */
    static function get_all() {
        return self::$data;
    }
    
    
    /**
     * Adds a class name that can be used when creating new columns
     * @param string $class_name The name of the class to be selectable.
     *        Note that abstract classes and classes that have already been
     *        added will be ignored.
     */
    static function add_column_class($class_name) {
        settype($class_name, 'string');
        if (!class_exists($class_name)) return;
        $reflection = new ReflectionClass($class_name);
        if ($reflection->isAbstract()) return;
        if (!in_array($class_name, self::$column_classes)) {
            self::$column_classes[] = $class_name;
        }
    }
    
    /**
     * Gets the list of class names that can be used for new columns
     * @return array Each element is a string
     */
    static function get_column_classes() {
        return self::$column_classes;
    }
    
    
    static function load_help_text(Table $table) {
        $help_table = $table->getDatabase()->getHelpTable();
        if ($help_table == null) {
            throw new \Exception('No help table available');
        }
        $q = "SELECT HelpColumn, IF(LENGTH(TRIM(HelpText)) > 0, 1, 0) AS HasLongHelp, QuickHelp
            FROM `{$help_table->getName ()}`
            WHERE HelpTable = " . sql_enclose($table->getName());
        $res = execq($q);
        $help = array();
        while ($row = fetch_assoc($res)) {
            $help[$row['HelpColumn']] = $row;
        }
        self::$help_text = $help;
    }
    
    static function get_help_text() {
        return self::$help_text;
    }
}
