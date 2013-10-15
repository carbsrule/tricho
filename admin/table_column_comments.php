<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';

test_setup_login (true, SETUP_ACCESS_LIMITED);

$_GET['t'] = '__tools';
require 'head.php';
?>

<div id="main_data">

<h2>Table and column comments</h2>

<?php
$_GET['section'] = 'db';
require_once 'tools_tabs.php';
?>

<p>This tool shows all of the table and column comments. If a table or column is not shown,
it does not have any comments.</p>


<?php
echo '<table class="bordered horizonal">';
$tables = $db->getOrderedTables ();
$no_com_tbl = array();
$altrow = 1;
foreach ($tables as $table) {

    // get the table comment
    $tbl_comments = str_replace ("\n", '<br>', trim ($table->getComments ()));
    
    // get the column comments
    $col_comments = array();
    $no_com_cols = array();
    $columns = $table->getColumns ();
    foreach ($columns as $column) {
        $comments = str_replace ("\n", '<br>', trim ($column->getComment ()));
        if ($comments != '') {
            $col_comments[$column->getName ()] = $comments;
        } else {
            $no_com_cols[] = $column->getName ();
        }
    }
    
    
    // show, if we have some
    if ($tbl_comments != '' or count ($col_comments) > 0) {
        echo "\n<tbody class=\"altrow{$altrow}\">\n";
        if ($altrow == 1) { $altrow = 2; } else { $altrow = 1; }
        
        $rowspan = count ($col_comments) + (count ($no_com_cols) > 0 ? 1 : 0);
        if ($rowspan == 0) $rowspan = 1;
        
        echo "<tr><td rowspan=\"{$rowspan}\" class=\"comments_left\">";
        
        echo "<h3>{$table->getName ()}</h3>";
        
        // show table comment
        if ($tbl_comments != '') {
            echo "<p>{$tbl_comments}</p>";
        } else {
            echo "<p><span class=\"error\">This table does not have any comments</span></p>";
            if (count ($col_comments) == 0) $no_com_tbl[] = $table->getName ();
        }
        
        echo "</td>\n";
        
        // show column comments
        if (count ($col_comments) > 0) {
            $index = 0;
            foreach ($col_comments as $column => $comment) {
                ++$index;
                
                // work out the display classes
                $classes = '';
                if ($index == 1) {
                    $classes = 'comments_top';
                }
                if ($index == count ($col_comments) and count ($no_com_cols) == 0) {
                    $classes .= ' comments_bottom';
                }
                $classes = trim ($classes);
                
                // draw this cell
                if ($index < count ($col_comments)) {
                    echo "<th class=\"{$classes}\">{$column}</th><td class=\"{$classes} comments_right\">{$comment}</td></tr>\n<tr>";
                    
                } else {
                    echo "<th class=\"{$classes}\">{$column}</th><td class=\"{$classes} comments_right\">{$comment}</td></tr>\n";
                }
            }
            
            // columns without comments
            if (count ($no_com_cols) > 0) {
                echo "<tr><td colspan=\"2\" class=\"comments_bottom comments_right\">";
                echo "<em>Columns with no comments:</em> " . implode (', ', $no_com_cols) . '</td>';
            }
            
        } else {
            echo "<td colspan=\"2\" class=\"comments_top comments_bottom comments_right\">";
            echo "<span class=\"error\">None of the columns in this table have any comments</span></td>";
        }
        
        echo '</tr>';
        echo "\n</tbody>\n";
        
        $has_comment = true;
        
    } else {
        $no_com_tbl[] = $table->getName ();
    }
}

echo "</table>\n\n";


// there are no comments at all
if ($has_comment) {
    
    // show which tables do not have comments
    if (count ($no_com_tbl) > 0) {
        echo '<p>&nbsp;</p>';
        echo '<p class="error"><strong>Tables without comments:</strong><br>' . implode (', ', $no_com_tbl) . '</p>';
    }
    
} else {
    report_error ("There are no comments defined for this database.");
}
?>


</div>
<?php
require_once 'foot.php';
?>
