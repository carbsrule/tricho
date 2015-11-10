<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\DataUi\FormManager;
use Tricho\DataUi\Form;

$css_files = ['form_edit.css'];
$jquery = '//code.jquery.com/jquery-1.11.2.min.js';
if (file_exists('../../js/jquery.min.js')) {
    $jquery = '../../js/jquery.min.js';
} else if (file_exists('../../js/jquery.js')) {
    $jquery = '../../js/jquery.js';
}
$jquery_ui = '//code.jquery.com/ui/1.11.2/jquery-ui.js';
if (file_exists('../../js/jquery-ui.min.js')) {
    $jquery_ui = '../../js/jquery-ui.min.js';
} else if (file_exists('../../js/jquery-ui.js')) {
    $jquery_ui = '../../js/jquery-ui.js';
}
$js_files = [
    $jquery,
    $jquery_ui,
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
