<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Query;

use \Exception;

/**
 * Used in an ORDER BY clause to provide ordering.
 * 
 * @package query_builder
 */
class OrderColumn {
    
    private $column;
    private $order_dir;
    
    /**
     * @param QueryField $column the column to order by
     * @param int $dir the direction in which to order, ASC (ORDER_DIR_ASC) or
     *        DESC (ORDER_DIR_DESC)
     */
    function __construct (QueryField $column, $dir = ORDER_DIR_ASC) {
        if ($dir === ORDER_DIR_ASC or $dir === ORDER_DIR_DESC) {
            $this->column = $column;
            $this->order_dir = $dir;
        } else {
            throw new Exception ('Invalid direction');
        }
    }
    
    function __toString () {
        $string = $this->column->identify ('order_by');
        if ($this->order_dir === ORDER_DIR_ASC) {
            $string .= ' ASC';
        } else {
            $string .= ' DESC';
        }
        return $string;
    }
    
    /**
     * Sets the direction used when ordering by this column
     * 
     * @param int $dir the direction: ASC (ORDER_DIR_ASC) or
     *        DESC (ORDER_DIR_DESC)
     */
    function setDirection ($dir) {
        if ($dir === ORDER_DIR_ASC or $dir === ORDER_DIR_DESC) {
            $this->order_dir = $dir;
        } else {
            throw new Exception ('Invalid direction');
        }
    }
    
    /**
     * Gets the column used for ordering
     * 
     * @return QueryField
     */
    function getColumn () {
        return $this->column;
    }
    
    /**
     * Gets the direction used when ordering by this column
     * 
     * @return int ORDER_DIR_ASC or DESC ORDER_DIR_DESC
     */
    function getDirection () {
        return $this->order_dir;
    }
    
}

?>
