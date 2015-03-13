<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Meta\LinkColumn;

require_once '../tricho.php';

test_setup_login (true, SETUP_ACCESS_FULL);

$_GET['t'] = '__tools';
require 'head.php';
?>

<div id="main_data">

<h2>Table links checker</h2>

<?php
$_GET['section'] = 'err';
require_once 'tools_tabs.php';
?>


<p>These are all of the links in your database, their details and any errors with the linking</p>
    
<table class="bordered horizonal">
<?php
$tables = $db->getOrderedTables ();
$altrow = 1;
foreach ($tables as $table) {
    
    // iterate through the columns and get all the links
    $links = array ();
    $columns = $table->getColumns ();
    foreach ($columns as $column) {
        if ($column instanceof LinkColumn) {
            $links[] = $column;
        }
    }
    
    // if there are links, show all the links for this table
    if (count ($links) > 0) {
        
        echo "\n<tbody class=\"altrow{$altrow}\">\n";
        if ($altrow == 1) {
            $altrow = 2;
        } else {
            $altrow = 1;
        }
        
        echo "<tr><td>";
        echo "<h3>{$table->getName ()}</h3>";
        echo "</td><td>\n";
                    
        foreach ($links as $link) {
            echo "<p><strong>{$link->getName()}</strong> -&gt; ";
            echo $link->getTarget()->getTable()->getName () . '.' . 
                $link->getTarget()->getName() . '<br><small>';
            
            // show from column type
            echo sql_type_string_from_defined_constant($link->getSqlType());
            if ($link->getSqlSize() != '') echo '(' . $link->getSqlSize() . ')';
            if ($link->isUnsigned()) echo ' UNSIGNED';
            
            echo ' -&gt; ';
            
            // show to column type
            echo get_class($link->getTarget()) . ':';
            echo sql_type_string_from_defined_constant ($link->getTarget()->getSqlType ());
            if ($link->getTarget()->getSqlSize () != '') {
                echo '(' . $link->getTarget()->getSqlSize() . ')';
            }
            if ($link->getTarget()->isUnsigned()) echo ' UNSIGNED';
            
            // report errors
            $errors = array ();
            if ($link->getSqlType() != $link->getTarget()->getSqlType()) {
                $errors[] = 'sql type does not match';
            } else if ($link->isUnsigned() != $link->getTarget()->isUnsigned()) {
                $errors[] = 'sql type does not match';
            }
            if ($link->getSqlSize() != $link->getTarget()->getSqlSize()) {
                $errors[] = 'sql size does not match';
            }
            
            if (count ($errors) == 1) {
                echo " &nbsp; <span class=\"error\">ERROR: {$errors[0]}</span>";
            } else if (count ($errors) > 1) {
                echo ' &nbsp; <span class="error">ERRORS: '. implode (', ', $errors). '</span>';
            }
            
            echo '</small></p>';
        }
        
        echo "</td></tr>";
        echo "</tbody>\n";
    }
}
?>
</table>


</div>

<?php
require 'foot.php';
?>
