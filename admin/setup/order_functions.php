<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

function get_in_out_lists ($table, $name) {
    
    if ($name == 'show') {
        list ($in_list, $out_list) = $table->getViewList ();
        
    } else if ($name == 'order') {
        $in_list = $table->getOrder ('view');
        
        // new
        $exclude_list = array();
        foreach ($in_list as $item) {
            $exclude_list[] = $item[0];
        }
        $out_list_temp = array_diff($table->getColumns(), $exclude_list);
        
        // force numbered list
        $out_list = array();
        foreach ($out_list_temp as $item) {
            $out_list[] = $item;
        }
        
    } else if (substr($name, 0, 6) == 'search') {
        $in_list = $table->getSearch();
        $out_list_temp = array_diff($table->getColumns(), $in_list);
        
        // force numbered list
        $out_list = array();
        foreach ($out_list_temp as $item) {
            $out_list[] = $item;
        }
        
    } else {
        // some error here
    }
    
    return array ($in_list, $out_list);
}

?>
