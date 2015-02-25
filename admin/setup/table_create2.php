<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
test_setup_login();

$redir = false;
if (!isset($_SESSION['setup']['create_table'])) $redir = true;
if (!$redir and !isset($_SESSION['setup']['create_table']['columns'])) {
    $redir = true;
}
if ($redir) redirect('table_create0.php');

require 'head.php';

$session = $_SESSION['setup']['create_table'];
$columns = $session['columns'];
?>

<h2>Create table <?= $session['table_name']; ?></h2>

<?php report_session_info ('err', 'setup'); ?>

<h3>Primary key</h3>

<form method="post" action="table_create2_action.php">
<?php
// load primary keys
$pk_cols = isset($session['pk_cols'])? $session['pk_cols']: [];

// if no primary keys are defined, try to choose some defaults
if (count($pk_cols) == 0) {
    
    // if there is only one column, make it the PK
    $auto_worked = false;
    if (count($columns) == 1) {
        $pk_cols[] = $columns[0]['name'];
        $auto_worked = true;
    }
    
    // if there is an auto_increment column, make it the PK
    if (!$auto_worked) {
        foreach ($columns as $col) {
            if (in_array('AUTO_INCREMENT', $col['sql_attribs'])) {
                $pk_cols[] = $col['name'];
                $auto_worked = true;
                break;
            }
        }
    }
    
    // if there is a column named 'ID', make it the PK
    if (!$auto_worked) {
        foreach ($columns as $col) {
            if ($col['name'] == 'ID') {
                $pk_cols[] = $col['name'];
                $auto_worked = true;
                break;
            }
        }
    }
}


foreach ($columns as $id => $col) {
    echo '<label for="pk_', $id, '"><input type="checkbox" name="PRIMARY_KEY[]',
        '" value="', hsc($col['name']), '" id="pk_', $id, '"';
    if (in_array($col['name'], $pk_cols)) echo ' checked';
    echo '> ', $col['name'], ' (', $col['engname'], ")</label><br>\n";
}
?>
<p>&nbsp;</p>
<input type="submit" value="Continue &raquo;">
</form>

<?php
require 'foot.php';
