<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../../tricho.php';

$session = &$_SESSION['setup']['table_edit'];

if ($session['add_column']['type'] != '') {
    $onload_javascript = "column_edit_init ();";
}

require 'head.php';

$page_opts = array ('tab' => 'cols');
require 'table_head.php';
require 'setup_functions.php';
require 'column_definition.php';

$table = $db->getTable ($session['chosen_table']);

echo '<h3>Add column ', count($table->getColumns ()) + 1, "</h3>\n";

if (isset ($session['add_column'])) {
    $meta = $session['add_column'];
} else {
    $meta = column_def_defaults ();
}
column_def_form ($table, 'add', 'table_edit_col_add_action.php', $meta);

require 'foot.php';
?>
