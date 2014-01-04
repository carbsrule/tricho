<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../../tricho.php';

$session = &$_SESSION['setup']['table_edit'];

if (@$session['add_column'][$_GET['t']]['type'] != '') {
    $onload_javascript = "column_edit_init ();";
}

require 'head.php';

$page_opts = array ('tab' => 'cols');
require 'table_head.php';
require 'setup_functions.php';
require 'column_definition.php';

$table = $db->getTable($_GET['t']);

echo '<h3>Add column ', count($table->getColumns ()) + 1, "</h3>\n";

if (isset($session['add_column'][$_GET['t']])) {
    $meta = $session['add_column'][$_GET['t']];
} else {
    $meta = column_def_defaults ();
}

$action = 'table_edit_col_add_action.php';
$hidden_fields = array('t' => $_GET['t']);
column_def_form($table, 'add', $action, $meta, $hidden_fields);

require 'foot.php';
?>
