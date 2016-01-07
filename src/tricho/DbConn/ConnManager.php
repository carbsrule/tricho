<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\DbConn;

use InvalidArgumentException;

class ConnManager {
    static private $connections = array();
    static private $configs = array();
    
    /**
     * To be called by DbConn::connect, so that the most recent connection can
     * be tracked
     */
    static function add(DbConn $conn) {
        $key = array_search($conn, self::$connections, true);
        if ($key !== false) {
            unset(self::$connections[$key]);
        }
        self::$connections[] = $conn;
    }
    
    
    /**
     * Connect using a specific configuration
     * @param string $config_id The ID of the configuration (@see add_config())
     * @param bool $add True to add this connection to the stack of known
     *        connections. This should always be true for user calls.
     * @return DbConn the connection that was opened
     */
    static function connect($config_id, $add = true) {
        $config = @self::$configs[$config_id];
        if (!$config) {
            $err = 'Unknown config ID: ' . $config_id;
            throw new InvalidArgumentException($err);
        }
        $class = $config['class'];
        unset($config['class']);
        $conn = new $class($config);
        $conn->connect();
        if ($add) self::add($conn);
        return $conn;
    }
    
    
    /**
     * Gets a connection to the default database; creating it when called for
     * the first time.
     * @return DbConn
     */
    static function get_default() {
        static $conn = null;
        if ($conn) return $conn;
        $configs = array_keys(self::$configs);
        $config_id = reset($configs);
        $conn = self::connect($config_id, false);
        return $conn;
    }
    
    
    /**
     * Gets the current active DB connection (of a specific type, if desired)
     * @param string $class The name of a specific connection class, if desired,
     *        e.g. MysqlConn
     * @return DbConn or NULL
     */
    static function get_active($class = '') {
        // Connect using default db config if no active connections
        if (count(self::$connections) == 0) {
            $default_id = self::get_default_config_id();
            if ($default_id) {
                self::connect($default_id);
            }
        }
        
        $class = (string) $class;
        if ($class == '') return end(self::$connections);
        
        foreach (array_reverse(self::$connections) as $conn) {
            if ($conn instanceof $class) return $conn;
        }
        return false;
    }
    
    
    static function remove(DbConn $conn) {
        $key = array_search($conn, self::$connections, true);
        if ($key !== false) {
            unset(self::$connections[$key]);
        }
    }
    
    
    static function get_default_config_id() {
        $keys = array_keys(self::$configs);
        return reset($keys);
    }
    
    
    static function get_config($id) {
        return @self::$configs[$id];
    }
    
    
    static function add_configs(array $configs) {
        foreach ($configs as $id => $config) {
            self::add_config($id, $config);
        }
    }
    
    
    /**
     * Adds a configuration for a database connection
     */
    static function add_config($id, array $config) {
        $id = (string) $id;
        if (isset(self::$configs[$id])) {
            throw new InvalidArgumentException('Duplicate ID');
        }
        if (!isset($config['class'])) {
            throw new InvalidArgumentException('Config specifies no class');
        }
        if (!starts_with($config['class'], 'Tricho\\')) {
            $config['class'] = "Tricho\\DbConn\\{$config['class']}";
        }
        if (!class_exists($config['class'])) {
            throw new InvalidArgumentException('Config specifies unknown class');
        }
        $config['class']::validate_config($config);
        self::$configs[$id] = $config;
    }
}
