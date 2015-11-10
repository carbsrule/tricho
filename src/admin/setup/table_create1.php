<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Meta\Table;

require_once '../../tricho.php';
require_once 'column_definition.php';

$session = & $_SESSION['setup']['create_table'];

$col = $_GET['id'];
if (isset ($session['columns'][$col])) {
    $meta = $session['columns'][$col];
    if (!isset ($meta['insert_after'])) $meta['insert_after'] = 'retain';
    $action = 'edit';
} else {
    $meta = column_def_defaults ();
    $action = 'add';
}


if (@$meta['class'] != '') $onload_javascript = "column_edit_init();";

require 'head.php';
require_once 'setup_functions.php';
?>

<h2>Create table <?= $session['table_name']; ?></h2>

<?php
// show already defined columns
if (is_array(@$session['columns'])) {
    table_create_list_columns($session['columns'], $col);
}
?>

<h3>Column <?= $col; ?></h3>

<?php
report_session_info ('err', 'setup');

$table = new Table($session['table_name']);
column_def_form($table, $action, 'table_create1_action.php', $meta, array('_col_id' => $col));

require 'foot.php';
?>
