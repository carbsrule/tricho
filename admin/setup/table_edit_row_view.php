<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\DataUi\FormManager;

$css_files = ['form_edit.css'];
$js_files = [
    '//code.jquery.com/jquery-1.11.2.min.js',
    '//code.jquery.com/ui/1.11.2/jquery-ui.js',
    'form_edit.js',
];
require 'head.php';

$page_opts = array ('tab' => 'row');
require 'table_head.php';

$form_file = "admin.{$table->getName()}";
$form = FormManager::load($form_file);
if ($form == null) {
    $form = new Form();
}
$form_table = $table;
$success_redirect = 'table_edit_row_view.php?t=' . $table->getName();
$no_heading = true;
require 'form_edit.inc.php';

require 'foot.php';
?>
