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

$session = &$_SESSION['setup']['table_edit'];

$curr_col = $table->get ($session['chosen_column']);
if ($curr_col == null) {
    report_error ("Invalid column");
    require 'foot.php';
    exit;
}

require 'setup_functions.php';
require 'column_definition.php';

if (isset ($session['edit_column'])) {
    $meta = $session['edit_column'];
} else {
    $meta = $curr_col->getConfigArray ();
    $meta['insert_after'] = 'retain';
}

if ($meta['class'] == 'LinkColumn') $meta['sqltype'] = 'LINK';

column_def_form ($curr_col, 'edit', 'table_edit_col_edit_action.php', $meta);

require 'foot.php';
?>
