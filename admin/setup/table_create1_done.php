<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';
require_once 'setup_functions.php';

$session = & $_SESSION['setup']['create_table'];
?>

<h2>Create table <?= $session['table_name']; ?></h2>

<?php
check_session_response ('setup');

echo '<h3>Columns</h3>';

// show already defined columns
$max_col = 0;
if (is_array ($session['columns'])) {
    table_create_list_columns ($session['columns']);
    $max_col = count ($session['columns']);
}
?>

<br>
<form method="get" action="table_create1.php">
<input type="hidden" name="id" value="<?= $max_col + 1; ?>">
<input type="submit" value="+ Add another column">
</form>

<br>
<form method="get" action="table_create2.php">
<?php
if (@count ($session['columns']) == 0) {
    $disable = ' disabled="disabled"';
} else {
    $disable = '';
}
?>
<input type="submit" value="Move on already &raquo;"<?= $disable; ?>>
</form>

<?php
require 'foot.php';
?>
