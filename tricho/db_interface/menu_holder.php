<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * @package main_system
 */

/**
 * Used to group menu items in the admin area
 * 
 * @package main_system
 * @author benno, 2009-09-04
 */
class MenuHolder {
    
    public $name = '???';
    public $tables = array ();
    public $active = false;
    
    /**
     * @param string $name The name of the group
     * @param array $tables Each element is either a Table object, a table name
     *        (string), or an array with 2 elements: [0] name, [1] URL
     */
    function __construct ($name, $tables) {
        global $ungrouped_tables, $selected_table;
        
        $this->name = $name;
        foreach ($tables as $table) {
            
            if (is_array ($table)) {
                $this->tables[] = $table;
                continue;
            }
            
            foreach ($ungrouped_tables as $key => $menu_table) {
                if ((is_string ($table) and $menu_table->getName () == $table)
                        or ($table instanceof Table and $menu_table === $table)) {
                    unset ($ungrouped_tables[$key]);
                    if (!$menu_table->getDisplay () or !$menu_table->checkAuth ()) break;
                    $this->tables[] = $menu_table;
                    if ($selected_table === $menu_table) $this->active = true;
                    break;
                }
            }
        }
    }
    
    
    /**
     * Draws this holder as a list item, and any children in a sub-list
     */
    function draw () {
        
        // Ugly, but necessary. See admin/head.php
        global $selected_table;
        
        if ($this->active) {
            $class = ' on';
            $sub_class = '';
        } else {
            $class = '';
            $sub_class = ' class="display-none"';
        }
        echo "<li class=\"holder{$class}\"><a href=\"#\" onclick=\"show_sublist (this.parentNode); return false;\">",
            $this->name, "</a>";
        if (count ($this->tables) > 0) {
            echo "<ul{$sub_class}>\n";
            $current_page = $_SERVER['REQUEST_URI'];
            
            foreach ($this->tables as $inner_table) {
                
                if (is_array ($inner_table)) {
                    list ($name, $url) = $inner_table;
                    
                    // Convert local URLs to be relative to the web root (inclusive of opening slash)
                    if ($url[0] != '/' and strpos ($url, '://') === false) {
                        $url = ROOT_PATH_WEB. ADMIN_DIR. $url;
                    }
                    
                    echo "        <li";
                    if ($current_page == $url) echo ' class="on"';
                    echo "> <a href=\"{$url}\">{$name}</a></li>\n";
                    continue;
                }
                
                $inner_table->menuDraw ($selected_table === $inner_table);
            }
            echo "</ul>\n";
        }
        echo "</li>\n";
    }
}
?>
