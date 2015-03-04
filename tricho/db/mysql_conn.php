<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Meta\Table;

/**
 * Implements a MySQL database connection using PHP's mysqli module
 */
class MysqlConn extends DbConn {
    static function validate_config(array $config) {
        if (@$config['class'] != __CLASS__) {
            $err = "Connection class doesn't match config";
            throw new InvalidArgumentException($err);
        }
        if (@$config['db'] == '' and @$params['dbname'] == '') {
            $err = 'Database unspecified in config';
            throw new InvalidArgumentException($err);
        }
        if (@$config['user'] == '') {
            $err = 'User unspecified in config';
            throw new InvalidArgumentException($err);
        }
        if (@$config['pass'] == '') {
            $err = 'Password unspecified in config';
            throw new InvalidArgumentException($err);
        }
    }
    
    
    function build_dsn() {
        $dsn_params = array();
        $params = $this->params;
        if (isset($params['db']) and !isset($params['dbname'])) {
            $params['dbname'] = $params['db'];
            unset($params['db']);
        }
        if (!isset($params['charset'])) $params['charset'] = 'utf8';
        
        $vars = array('host', 'post', 'dbname', 'unix_socket', 'charset');
        foreach ($vars as $var) {
            if (isset($params[$var])) $dsn_params[$var] = $params[$var];
        }
        $dsn = '';
        foreach ($dsn_params as $param => $value) {
            if ($dsn != '') $dsn .= ';';
            $dsn .= $param . '=' . $value;
        }
        $dsn = 'mysql:' . $dsn;
        return $dsn;
    }
    
    
    function post_connect() {
        $charset = @$this->params['charset'];
        if (!$charset) $charset = 'utf8';
        if (version_compare(PHP_VERSION, '5.3.6') < 0) {
            $this->conn->query('SET NAMES ' . $charset);
        }
    }
    
    
    function quote_ident($identifier, $long = false) {
        if ($identifier instanceof Table) {
            $identifier = $identifier->getName();
        } else if ($identifier instanceof Column) {
            if ($long) {
                $identifier = $identifier->getTable()->getName() . '.' .
                    $identifier->getName();
            } else {
                $identifier = $identifier->getName();
            }
        }
        $quoted = '';
        $parts = explode('.', $identifier);
        foreach ($parts as $part) {
            $part = str_replace('`', '', $part);
            if ($quoted != '') $quoted .= '.';
            $quoted .= "`{$part}`";
        }
        return $quoted;
    }
}
