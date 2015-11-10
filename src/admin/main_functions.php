<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Meta\Table;

/**
 * Shows the children tables that are available for a specific table
 *
 * @param Table $from_table The table to show the children tables of
 * @param string $this_identifier The identifier to use for the current table
 */
function show_children (Table $from_table, $this_identifier = '') {
    global $db;
    
    // determine old parent
    $old_parent = trim(@$_GET['p']);
    if ($old_parent != '') $old_parent = ',' . $old_parent;
    
    // determine children (tis slow i fear)
    $tables = $db->getTables ();
    $child_links = array ();
    foreach ($tables as $to_table) {
        $column = $to_table->getLinkToTable($from_table);
        if ($column == null) continue;
        if (!$column->isParentLink()) continue;
        if ($from_table === $to_table) continue;
        $child_links[] = $column;
    }
    
    // do nothing if no children
    if (count ($child_links) == 0) return;
    
    $this_identifier = trim ($this_identifier);
    $this_table_id = string_to_id ($from_table->getName ());
    
    // show this item
    echo '<ul class="main_edit_tabs">';
    echo "<li class=\"on\" id=\"subtab_{$this_table_id}\">";
    list ($urls, $seps) = $from_table->getPageUrls ();
    echo "<a href=\"{$urls['main_edit']}{$seps['main_edit']}t={$from_table->getName ()}&id={$_GET['id']}&p={$_GET['p']}\">{$from_table->getNameSingle ()}";
    if ($this_identifier != '') echo ": {$this_identifier}";
    echo "</a></li>";
    
    // show the children
    foreach ($child_links as $child_link) {
        // get the link info and an alt eng name if so be it
        $child_table = $child_link->getFromColumn ()->getTable ();
        $child_eng_name = $child_table->getEngName ();
        if ($child_link->getAltEngName () != null) {
            $child_eng_name = $child_link->getAltEngName ();
        }
        
        // get a tab record count if we are meant to
        $count = '';
        if ($child_link->showTabCount ()) {
            
            $clean_int = preg_replace ('/[^0-9]/', '', $_GET['id']);
            if ($clean_int == $_GET['id']) {
                $id_safe = $clean_int;
            } else {
                $id_safe = sql_enclose($_GET['id']);
            }
            
            $q = "SELECT COUNT(*) AS C
                FROM `{$child_table->getName ()}`
                WHERE `{$child_link->getFromColumn ()->getName ()}` = {$id_safe}";
            $res = execq($q);
            if ($_SESSION['setup']['view_q']) echo "<small>[cnt] Q: {$q}</small>";
            $row = fetch_assoc($res);
            $count = " ({$row['C']})";
        }
        
        // display his tab
        $parent = $from_table->getName (). '.'. $_GET['id']. $old_parent;
        $child_table_id = string_to_id ($child_table->getName ());
        list ($urls, $seps) = $child_table->getPageUrls ();
        echo "<li id=\"subtab_{$this_table_id}_{$child_table_id}\">",
            "<a href=\"{$urls['main']}{$seps['main']}t={$child_table->getName ()}&p={$parent}\">{$child_eng_name}{$count}</a></li>";
    }
    echo '</ul>';
}



/**
 * Shows the parent nodes as specified in the array
 * and their siblings, as links, all cute and like
 */
function show_parent_siblings (Table $self, $parents) {
    global $db;
    
    if ($parents == null) return;
    $p = '';
    
    $parents = array_reverse ($parents);
    
    // build array of table => key
    $vals = array ();
    foreach ($parents as $parent) {
        list ($table, $key) = explode ('.', $parent);
        $vals[$table] = $key;
    }
    

    
    // do children
    $first_ancestor = true;
    foreach ($vals as $tbl => $pk_val) {
        
        /*
        if ($first_ancestor) {
            echo "<h2>{$self->getEngName()}</h2>";
            $first_ancestor = false;
        }
        */
        
        $table = $db->getTable ($tbl);
        $parent_table_id = string_to_id ($table->getName ());
        
        // build identifier
        $pks = $table->getPKnames ();
        $pks = array ($pks[0] => $pk_val);
        $identifier = $table->buildIdentifier ($pks);
        if ($identifier != '') $identifier = ': ' . $identifier;
        
        // this table
        list ($urls, $seps) = $table->getPageUrls ();
        echo '<ul class="main_edit_tabs">';
        echo "<li id=\"subtabs_{$parent_table_id}\">",
            "<a href=\"{$urls['main_edit']}{$seps['main_edit']}t={$table->getName ()}&id={$pk_val}&p={$p}\">",
            $table->getNameSingle (). $identifier. '</a></li>';
        
        // determine the parent string for these children
        if ($p != '') {
            $p = $tbl. '.'. $pk_val. ','. $p;
        } else {
            $p = $tbl. '.'. $pk_val;
        }
        
        // the children
        $children = $table->getChildren ();
        foreach ($children as $child) {
            
            // see if there's an alternate name specified for the link
            $child_eng_name = $child->getEngName ();
            $link_col = $child->getLinkToTable ($table);
            if ($link_col != null) {
                $link = $link_col->getLink ();
                if ($link->getAltEngName () != null) {
                    $child_eng_name = $link->getAltEngName ();
                }
            }
            
            // get a tab record count if we are meant to
            $count = '';
            if ($link->showTabCount ()) {
                
                // try and use integer value if possible
                $clean_int = preg_replace ('/[^0-9]/', '', $pk_val);
                if ($clean_int == $pk_val) {
                    $id_safe = $clean_int;
                } else {
                    $id_safe = sql_enclose($pk_val);
                }
                
                $q = "SELECT COUNT(*) AS C
                    FROM `{$child->getName()}`
                    WHERE `{$link->getFromColumn ()->getName ()}` = {$id_safe}";
                $res = execq($q);
                if ($_SESSION['setup']['view_q']) echo "<small>[cnt] Q: {$q}</small>";
                $row = fetch_assoc($res);
                $count = " ({$row['C']})";
            }
            
            $child_table_id = string_to_id ($child->getName ());
            
            // display this link
            list ($urls, $seps) = $child->getPageUrls ();
            if (($self === $child) || ($vals[$child->getName ()] != null)) {
                echo "<li class=\"on\" id=\"subtabs_{$parent_table_id}_{$child_table_id}\">",
                    "<a href=\"{$urls['main']}{$seps['main']}t={$child->getName ()}&p={$p}\">{$child_eng_name}{$count}</a></li>";
                    
            } else {
                echo "<li id=\"subtabs_{$parent_table_id}_{$child_table_id}\">",
                    "<a href=\"{$urls['main']}{$seps['main']}t={$child->getName ()}&p={$p}\">{$child_eng_name}{$count}</a></li>";
            }
        }
        
        echo '</ul>';
        
    }
}

?>
