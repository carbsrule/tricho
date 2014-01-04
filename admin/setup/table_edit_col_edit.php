<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

$onload_javascript = "column_edit_init ();";
require 'head.php';

$page_opts = array ('tab' => 'cols');
require 'table_head.php';

$curr_col = $table->get($_GET['col']);
if ($curr_col == null) {
    report_error ("Invalid column");
    require 'foot.php';
    exit;
}

$id = "{$_GET['t']}.{$_GET['col']}";
if (!isset($_SESSION['setup']['table_edit']['edit_column'])) {
    $_SESSION['setup']['table_edit']['edit_column'] = array();
}
$session = &$_SESSION['setup']['table_edit']['edit_column'];

require 'setup_functions.php';
require 'column_definition.php';

if (isset($session[$id])) {
    $meta = $session[$id];
} else {
    $meta = $curr_col->getConfigArray ();
    $meta['insert_after'] = 'retain';
}

if ($meta['class'] == 'LinkColumn') $meta['sqltype'] = 'LINK';

$action = 'table_edit_col_edit_action.php';
$hidden_fields = array('col' => "{$_GET['t']}.{$_GET['col']}");
column_def_form($curr_col, 'edit', $action, $meta, $hidden_fields);

require 'foot.php';
?>
