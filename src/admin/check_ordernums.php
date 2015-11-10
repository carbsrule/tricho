<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';

test_setup_login (true, SETUP_ACCESS_FULL);

$_GET['t'] = '__tools';
require 'head.php';
?>

<div id="main_data">

<h2>OrderNum columns checker</h2>

<?php
$_GET['section'] = 'err';
require_once 'tools_tabs.php';
?>

<p>These are all of the OrderNum columns in your database, their details and any errors with
    the details of the columns</p>

<table class="bordered horizonal">
<?php
$tables = $db->getOrderedTables ();
$altrow = 1;
foreach ($tables as $table) {
    
    // iterate through the columns and get all the links
    $has_ordernum = false;
    $columns = $table->getColumns ();
    foreach ($columns as $column) {
        if ($column->getOption () == 'ordernum') {
            /* we have an order column! */
            
            echo "\n<tbody class=\"altrow{$altrow}\">\n";
            if ($altrow == 1) {
                $altrow = 2;
            } else {
                $altrow = 1;
            }
            
            echo "<tr><td>\n";
            echo "    <h3>{$table->getName ()}</h3>\n";
            $table_name = htmlspecialchars (urlencode ($table->getName ()));
            echo "    <p><a href=\"setup/table_edit_pre.php?table={$table_name}&action=EditMainView\">Edit table</a></p>\n";
            echo "</td><td>\n";
            
            // show the oder by
            $order = $table->getOrder ('view');
            echo "    <p><strong>Order:</strong>\n";
            foreach ($order as $orderitem) {
                echo "    <br>{$orderitem[0]->getName ()} {$orderitem[1]}\n";
                if ($orderitem[0] === $column) {
                    $has_ordernum = true;
                }
            }
            echo "    </p>\n\n";
            
            
            // check for errors
            $errors = array ();
            if (! $has_ordernum) {
                $errors[] = 'OrderNum column is not in the table order';
                
            } else {
                // ordernum needs to be last
                $last = array_pop ($order);
                if ($last[0] != $column) {
                    $errors[] = 'The OrderNum column is not the last column in the order list';
                }
                $order[] = $last;
                
                // create an array of all the parent columns
                $parent_cols = array ();
                foreach ($table->getColumns () as $col) {
                    $link = $col->getLink ();
                    if ($link != null) {
                        if ($link->isParent ()) {
                            $parent_cols[] = $col;
                        }
                    }
                }
                
                // we need at least one of the order by columns to be a parent column, if we have parent columns
                if (count ($parent_cols) > 0) {
                    $found = false;
                    foreach ($order as $col) {
                        if (in_array ($col[0], $parent_cols, true)) {
                            $found = true;
                            break;
                        }
                        if ($col[0] === $column) break;
                    }
                    
                    if (!$found) {
                        $errors[] = 'Table has columns with parent links, but the OrderNum column is not
                            preceded by at least one of these columns';
                    }
                }
                
                // check trees
                if ($table->getDisplayStyle () == TABLE_DISPLAY_STYLE_TREE) {
                    // get the col that links back to itself with (e.g. ParentID)
                    foreach ($table->getColumns () as $col) {
                        $link = $col->getLink ();
                        if ($link != null) {
                            if ($link->getToColumn()->getTable() === $table) {
                                $tree_col = $col;
                                break;
                            }
                        }
                    }
                    
                    if ($tree_col == null) {
                        $errors[] = 'Table is marked as being a tree display table but it does not have a self linking column';
                        
                    } else {
                        // see that this column is in the order
                        $found = false;
                        foreach ($order as $col) {
                            if ($col[0] === $tree_col) {
                                $found = true;
                                break;
                            }
                            if ($col[0] === $column) break;
                        }
                        
                        if (!$found) {
                            $errors[] = 'OrderNum column is not proceeded by the self-linking column used by the tree view';
                        }
                    }
                }
            }
            
            
            // show errors
            if (count ($errors) == 1) {
                echo "    <p class=\"error\">ERROR: {$errors[0]}</p>\n";
            } else if (count ($errors) > 1) {
                echo '    <p class="error">ERRORS:<br>'. implode ('<br>', $errors). "</p>\n";
            }
            
            echo "</td></tr>\n";
            echo "</tbody>\n";
            
            break;
        }
    }
    
}
?>
</table>


</div>

<?php
require 'foot.php';
?>
