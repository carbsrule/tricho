<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\DataUi;

use \Exception;

/**
 * Used to display cells that have order arrows (up and down) for re-ordering rows
 * 
 * @package main_system
 */
class MainOrderColumn {
    
    private $table_name;
    private $order_url;
    
    /**
     * @param string $table_name the name of the table that the order column
     *        belongs to
     * @param string $heading the text that will displayed inside the TH
     * @param string $url the URL to the order page for the table (e.g.
     *        main_order.php)
     */
    function __construct ($table_name, $heading, $url) {
        $this->type = MAIN_COL_TYPE_ORDER;
        $this->table_name = $table_name;
        $this->heading = cast_to_string ($heading);
        $this->order_url = $url;
    }
    
    /**
     * Gets the appropriate TH, with colspan of 2 since we use a column for the
     * up arrow and one for the down
     * 
     * @return string
     */
    function getTH () {
        return "<th colspan=\"2\">{$this->heading}</th>";
    }
    
    /**
     * Gets two appropriate TDs, one for the up arrow and one for the down
     * 
     * @return string
     */
    function getTD ($previous_row, $current_row, $next_row) {
        
        if ($previous_row instanceof MainRow) {
            $prev = $previous_row->getOrderIdentifier ();
        } else if ($previous_row === null) {
            $prev = null;
        } else {
            throw new Exception ("First parameter (\$previous_row) must be a MainRow object, or null");
        }
        
        if ($current_row instanceof MainRow) {
            $curr = $current_row->getOrderIdentifier ();
        } else if ($current_row === null) {
            $curr = null;
        } else {
            throw new Exception ("Second parameter (\$current_row) must be a MainRow object, or null");
        }
        
        if ($next_row instanceof MainRow) {
            $next = $next_row->getOrderIdentifier ();
        } else if ($next_row === null) {
            $next = null;
        } else {
            throw new Exception ("Third parameter (\$next_row) must be a MainRow object, or null");
        }
        
        $tds = "<td class=\"order_col\">";
        if ($curr === $prev and $curr !== null) {
            $tds .= "<a href=\"{$this->order_url}t={$this->table_name}&amp;p={$_GET['p']}&amp;d=u&amp;id=".
                $current_row->getPrimaryKey ().'"><img src="'. ROOT_PATH_WEB. IMAGE_ARROW_UP. '" border="0"></a>';
        } else {
            $tds .= '&nbsp;';
        }
        $tds .= "</td><td class=\"order_col\">";
        if ($curr === $next and $curr !== null) {
            $tds .= "<a href=\"{$this->order_url}t={$this->table_name}&amp;p={$_GET['p']}&amp;d=d&amp;id=".
                $current_row->getPrimaryKey (). '"><img src="'. ROOT_PATH_WEB. IMAGE_ARROW_DOWN. '" border="0"></a>';
        } else {
            $tds .= '&nbsp;';
        }
        $tds .= "</td>";
        
        return $tds;
    }

    public function __toString () {
        return __CLASS__. " { table_name: {$this->table_name}; }";
    }
}

?>
