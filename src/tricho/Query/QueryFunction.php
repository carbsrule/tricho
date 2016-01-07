<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Query;

use Exception;
use InvalidArgumentException;

use Tricho\Meta\Column;

/**
 * Used to implement MySQL function calls in a {@link SelectQuery}
 * 
 * @package query_builder
 */
class QueryFunction extends AliasedField {
    protected $function_name;
    protected $params;
    
    /**
     * When a CONCAT QueryFunction is created automatically for a LinkColumn
     * on admin/main.php, this refers to the source column of the link.
     */
    protected $source = null;
    
    /**
     * @param string $function_name the name of the function to be called,
     *        e.g. DATE_SUB
     * @param mixed $params a parameter, or an array of parameters. Each
     *        parameter must be either a {@link QueryField}, or a string
     *        (which will be converted to a {@link QueryFieldLiteral}).
     */
    function __construct ($function_name, $params = array ()) {
        
        $this->function_name = (string) $function_name;
        $this->params = array ();
        
        if (!is_array ($params)) {
            if ($params != false) {
                $params = array ($params);
            } else {
                $params = array ();
            }
        }
        
        foreach ($params as $param) {
            if ($param instanceof QueryField) {
                $this->params[] = $param;
            } else if (is_string ($param) or is_int ($param)) {
                $this->params[] = new QueryFieldLiteral ($param);
            }
        }
        
    }
    
    /**
     * @return string
     */
    function __toString () {
        
        if (strcasecmp ($this->function_name, 'CONCAT') == 0 and count ($this->params) == 1) {
            $param = reset ($this->params);
            return $param->identify ('param');
        }
        
        $string_val = $this->function_name. '(';
        
        $param_num = 0;
        foreach ($this->params as $param) {
            if ($param_num++ > 0) $string_val .= ', ';
            $string_val .= $param->identify ('param');
        }
        
        $string_val .= ')';
        
        return $string_val;
        
    }
    
    
    function setSource($source) {
        if ($source == null) {
            $this->source = null;
            return;
        }
        if (!($source instanceof Column)) {
            throw new InvalidArgumentException('Must be a Column or null');
        }
        $this->source = $source;
    }
    function getSource() {
        return $this->source;
    }
    
    
    
    /**
     * adds another parameter to the function call
     * 
     * @param QueryField $param the parameter
     */
    function addParam (QueryField $param) {
        $this->params[] = $param;
    }
    
    /**
     * gets the name of the function
     * 
     * @return string
     */
    function getName () {
        return $this->function_name;
    }
    
    /**
     * Identifies the function in a given context.
     * See {@link QueryColumn::identify()}
     */
    function identify ($context) {
        
        switch (strtolower($context)) {
            case 'select':
                return cast_to_string ($this). ($this->alias != ''? ' AS `'. $this->alias. '`': '');
                break;
            
            case 'param':
            case 'insert':
            case 'update':
                return cast_to_string ($this);
                break;
                
            case 'normal':
            case 'order_by':
                if ($this->alias == '') {
                    return cast_to_string ($this);
                } else {
                    return '`'. $this->alias. '`';
                }
                break;
                
            case 'row':
                if ($this->alias == '') {
                    return cast_to_string ($this);
                } else {
                    return $this->alias;
                }
                break;
            
            default:
                throw new Exception ("Invalid context {$context}, must be 'select', ".
                    "'normal', or 'row'");
        }
    }
    
    
    /**
     * Gets the appropriate TH for use on a main list, perhaps with colspan
     * @return string e.g. <th>Awesome column</th>
     */
    function getTH() {
        if ($this->source != null) {
            $str = $this->source->getEngName();
        } else {
            $str = $this->getAlias();
        }
        return '<th>' . hsc($str) . '</th>';
    }
    
    
    /**
     * gets the appropriate TD for use on a main list, perhaps with colspan
     * 
     * @param string $data the data for this cell
     * @param string $pk the primary key identifier for the row
     * @return string
     */
    function getTD($data, $pk) {
        $td = (is_numeric($data)? '<td align="right">': '<td>');
        return $td . hsc($data) . '</td>';
    }
}

?>
