<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * @package query_builder
 */

/**
 * Used to incorporate a literal value into a query, e.g. 5, NULL, or
 * "hey there"
 * 
 * @package query_builder
 */
class QueryFieldLiteral extends AliasedField {
    
    /**
     * @param mixed $value the literal value to be used
     * @param bool $escape whether to escape the literal. By default, escaping
     *        is used. You should leave this alone unless you're directly
     *        adding function calls, for example if you used
     *        <code>$literal = new QueryFieldLiteral ('NOW()');</code> and
     *        added that to a query, you would get the literal string NOW(),
     *        rather than the result of the function call.
     */
    function __construct ($value, $escape = true) {
        
        // Escaping makes no sense for NULL values, so disregard it
        if ($value === null) {
            $this->name = null;
            return;
        }
        
        if ($escape) {
            $this->name = cast_to_string (sql_enclose ($value));
        } else {
            $this->name = cast_to_string ($value);
        }
        $this->alias = null;
    }
    
    // NOOP functions to override what's specified in the abstract class
    /**
     * This is an empty function to override the abstract method specified in
     * {@link QueryField}
     */
    function setName () {
    }
    
    /**
     * This is an empty function to override the abstract method specified in
     * {@link QueryField}
     */
    function setAlias () {
    }
    
    /**
     * This overrides the abstract method specified in {@link QueryField}.
     * Context doesn't matter for a literal.
     */
    function identify ($context) {
        return $this->__toString ();
    }
    
    /**
     * @return string
     */
    function __toString () {
        if ($this->name === null) return 'NULL';
        return $this->name;
    }
    
}

?>
