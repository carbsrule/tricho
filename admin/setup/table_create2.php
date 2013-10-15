<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';

$table = $_SESSION['setup']['create_table']['table'];
$columns = $table->getColumns ();

?>

<h2>Create table <?= $_SESSION['setup']['create_table']['table_name']; ?></h2>

<?php report_session_info ('err', 'setup'); ?>

<h3>Primary key</h3>

<form method="post" action="table_create2_action.php">
<?php
// load primary keys
try {
    $primary_key_cols = $table->getIndex ('PRIMARY KEY');
} catch (Exception $e) {
    $primary_key_cols = array ();
}

// if no primary keys are defined, try to choose some defaults
if (count ($primary_key_cols) == 0) {
    
    // if there is only one column, make it the PK
    $auto_worked = false;
    if (count ($columns) == 1) {
        $primary_key_cols[] = $columns[0];
        $auto_worked = true;
    }
    
    // if there is an auto_increment column, make it the PK
    if (! $auto_worked) {
        foreach ($columns as $col) {
            if (in_array ('AUTO_INCREMENT', $col->getSqlAttributes ())) {
                $primary_key_cols[] = $col;
                $auto_worked = true;
                break;
            }
        }
    }
    
    // if there is a column named 'ID', make it the PK
    if (! $auto_worked) {
        foreach ($columns as $col) {
            if ($col->getName () == 'ID') {
                $primary_key_cols[] = $col;
                $auto_worked = true;
                break;
            }
        }
    }
}


foreach ($columns as $id => $col) {
    echo '<label for="pk_', $id, '"><input type="checkbox" name="PRIMARY_KEY[', $col->getName (),
        ']" value="1" id="pk_', $id, '"';
    if (@in_array ($col, $primary_key_cols)) {
        echo ' checked';
    }
    echo '> ', $col->getName (), ' (', $col->getEngName (), ")</label><br>\n";
}
?>
<p>&nbsp;</p>
<input type="submit" value="Continue &raquo;">
</form>

<?php
require 'foot.php';
?>
