<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Query;

use \DbConn;
use \ConnManager;

abstract class Query {
    protected $conn;
    protected $internal = false;
    protected $max_time = 0.0;
    
    abstract function __toString();
    
    
    function set_conn(DbConn $conn) {
        $this->conn = $conn;
    }
    
    
    /**
     * @param bool $internal True if the query is internal (i.e. should not be
     *        logged if an error occurs, or takes too long)
     */
    function set_internal($internal) {
        $this->internal = (bool) $internal;
    }
    function get_internal() {
        return $this->internal;
    }
    
    
    /**
     * @param float $max_time The maximum amount of time (in milliseconds) that
     *        this query is expected to run for. If it takes longer, a slow
     *        query report will be generated if slow queries are being logged.
     */
    function set_max_time($max_time) {
        $this->max_time = (float) $max_time;
    }
    function get_max_time() {
        return $this->max_time;
    }
    
    
    /**
     * Executes this query
     */
    function exec() {
        $conn = $this->conn;
        if ($conn == null) $conn = ConnManager::get_active();
        $conn->exec($this);
    }
}
