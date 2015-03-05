<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Runtime;
use Tricho\Query\RawQuery;

interface DbConnValidator {
    /**
     * Validates the config for a connection
     * @throws InvalidArgumentException
     */
    static function validate_config(array $config);
}

abstract class DbConn implements DbConnValidator {
    protected $conn = null;
    protected $params = array();
    
    /**
     * @param array $params The following keys are accepted:
     * - host: the server, e.g. localhost
     * - user: the username
     * - pass: the password for the user
     * - db: the database, e.g. MyDatabase
     * - port: the port, e.g. 3306
     */
    function __construct(array $params) {
        $this->params = $params;
    }
    
    /**
     * Creates a PDO connection. @see http://php.net/manual/en/book.pdo.php
     * @throws PDOException If connection fails
     */
    function connect() {
        if ($this->conn != null) return;
        
        $dsn = $this->build_dsn();
        $params = $this->params;
        $user = @$params['user'];
        $pass = @$params['pass'];
        unset($params['user'], $params['pass']);
        $this->conn = new PDO($dsn, $user, $pass, $params);
        $this->post_connect();
    }
    
    
    /**
     * Returns the PDO object that underlies this connection.
     * Useful for running PDO commands for things other than DB queries, e.g.
     * transactions, error handling, etc.
     * @return PDO
     */
    function get_pdo() {
        return $this->conn;
    }
    
    
    function get_params() {
        return $this->params;
    }
    function get_param($name) {
        return @$this->params[$name];
    }
    
    
    function disconnect() {
        // Apparently this is the recommended way to close PDO connections :-/
        $this->conn = null;
        ConnManager::remove($this);
    }
    
    
    /**
     * Executes an SQL query.
     * @param mixed $query A Query object, or a string containing an SQL query
     * @param int $pdo_fetch_mode @see http://php.net/pdo.query
     * @param mixed $extra1 @see http://php.net/pdo.query
     * @param mixed $extra2 @see http://php.net/pdo.query
     * @return PDOStatement
     * @throws QueryException if the query fails
     */
    function exec($query, $pdo_fetch_mode = -1, $extra1 = null, $extra2 = null) {
        $internal = false;
        $query_max_time = 0.0;
        if ($query instanceof Query) {
            $query->set_conn($this);
            $internal = $query->get_internal();
            $query_max_time = $query->get_max_time();
        }
        $query = (string) $query;
        $start = microtime(true);
        switch ($pdo_fetch_mode) {
        case -1:
            $res = $this->conn->query($query);
            break;
        case PDO::FETCH_COLUMN:
        case PDO::FETCH_INTO:
            $res = $this->conn->query($query, $pdo_fetch_mode, $extra1);
            break;
        case PDO::FETCH_CLASS:
            $res = $this->conn->query($query, $pdo_fetch_mode, $extra1, $extra2);
            break;
        default:
            $res = $this->conn->query($query, $pdo_fetch_mode);
        }
        
        $time_taken = (microtime(true) - $start) * 1000.0;
        $query = sql_remove_private($query);
        if ($res instanceof PDOStatement) {
            if (!$internal and Runtime::get('sql_slow_log')) {
                $this->log_slow($query, $time_taken, $query_max_time);
            }
            return $res;
        }
        
        $this->log_error($query, $internal);
    }
    
    
    function log_error($query, $internal) {
        $err = $this->conn->errorInfo();
        $ex = new QueryException($err[2]);
        $ex->setCode($err[1]);
        $to_log = (bool) Runtime::get('sql_error_log');
        if ($internal or !$to_log) throw $ex;
        
        $conn = ConnManager::get_default();
        
        // Check for recent prior errors
        $wait = (int) Runtime::get('sql_error_email_wait');
        $q = new RawQuery("SELECT COUNT(*) AS c
            FROM _tricho_failed_queries
            WHERE DateOccurred > DATE_SUB(NOW(), INTERVAL {$wait} SECOND)
                AND MailSent = 1");
        $q->set_internal(true);
        try {
            $res = $conn->exec($q, PDO::FETCH_COLUMN, 0);
            $prior_errors = $res->fetch();
        } catch (QueryException $ex2) {
            $ex->addError('SQL error lookup failed');
            throw $ex;
        }
        
        // Email maintenance crew
        if ($err[1] > 0) {
            $err = "[{$err[1]}] {$err[2]}";
        } else {
            $err = $err[2];
        }
        $sent = 0;
        if (Runtime::get('sql_error_email') and $prior_errors == 0) {
            $msg = "SQL error occurred:\n{$err}\n\nQuery was:\n{$query}";
            $sent = 1;
            email_error($msg);
        }
        
        // Insert new error
        $data = array(
            'DateOccurred' => new QueryFieldLiteral('NOW()', false),
            'Query' => $query,
            'Error' => $err,
            'MailSent' => $sent
        );
        $q = new InsertQuery('_tricho_failed_queries', $data);
        $q->set_internal(true);
        try {
            $conn->exec($q);
        } catch (QueryException $ex2) {
            $ex->addError('SQL error logging failed');
            throw $ex;
        }
        
        throw $ex;
    }
    
    
    /**
     * Determines if a query ran slowly, and if so, logs it.
     * @param string $query The query to log, if it happens to be slow
     * @param float $time_taken The time taken to run the query (in ms)
     * @param float $query_max_time The maximum expected execution time for this
     *        particular query (also in ms).
     */
    function log_slow($query, $time_taken, $query_max_time) {
        $matches = array();
        preg_match('/[^a-z]*([a-z]+)/i', $query, $matches);
        $first_word = @strtolower($matches[1]);
        $unoptimised = false;
        $explain = null;
        
        switch ($first_word) {
        case 'select':
            list($unoptimised, $explain) = check_query_unoptimised($query);
            if ($unoptimised) {
                $var = 'sql_max_time_select_unoptimised';
            } else {
                $var = 'sql_max_time_select_normal';
            }
            $max_time = (float) Runtime::get($var);
            break;
        case 'insert':
        case 'update':
        case 'delete':
            $var = 'sql_max_time_' . $first_word;
            $max_time = (float) Runtime::get($var);
            break;
        default:
            $max_time = 100000000.0;
        }
        if ($query_max_time > 0.0) $max_time = $query_max_time;
        if ($time_taken < $max_time) return;
        
        $conn = ConnManager::get_default();
        $wait = (int) Runtime::get('sql_slow_email_wait');
        $q = new RawQuery("SELECT COUNT(*) AS c
            FROM _tricho_slow_queries
            WHERE DateOccurred > DATE_SUB(NOW(), INTERVAL {$wait} SECOND)
                AND MailSent = 1");
        $q->set_internal(true);
        $res = $conn->exec($q, PDO::FETCH_COLUMN, 0);
        $prior_queries = $res->fetch();
        
        if (Runtime::get('sql_slow_email') and $prior_queries == 0) {
            $send = 1;
        } else {
            $send = 0;
        }
        
        $data = array(
            'DateOccurred' => new QueryFieldLiteral('NOW()', false),
            'Query' => $query,
            'TimeTaken' => $time_taken,
            'MailSent' => $send
        );
        $q = new InsertQuery('_tricho_slow_queries', $data);
        $q->set_internal(true);
        $conn->exec($q);
        if ($send) {
            slow_query_email($query, $time_taken, $max_time, $unoptimised, $explain);
        }
    }
    
    
    function last_error($raw = false) {
        $err = $this->conn->errorInfo();
        if ($raw) return $err;
        if ($err[2] == '') {
            $err_str = 'Unknown error';
        } else {
            $err_str = $err[2];
            if ($err[1] > 0) {
                $err_str = "[{$err[1]}] {$err_str}";
            }
        }
        return $err_str;
    }
    
    
    /**
     * Makes a datum safe for database insertion
     */
    function quote($datum) {
        if ($datum instanceof Table or $datum instanceof Column) {
            return $this->quote_ident($datum);
        }
        return $this->conn->quote($datum);
    }
    
    
    /**
     * Internal function to be implemented for each connection type.
     * Should only ever be called by DbConn::connect
     * @return string
     */
    abstract function build_dsn();
    
    
    /**
     * Internal function to be implemented for each connection type.
     * Should only ever be called by DbConn::connect
     */
    abstract function post_connect();
    
    
    /**
     * Quotes an identifier (column or table name)
     * @param mixed $identifier A Table, Column, or string
     * @param bool $long True if a column should be referenced with Table.Column
     */
    abstract function quote_ident($identifier, $long = false);
}
