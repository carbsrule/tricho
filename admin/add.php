<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../tricho.php';
test_admin_login ();
require 'main_functions.php';

$db = Database::parseXML();
$table = $db->getTable ($_GET['t']); // use table name
force_redirect_to_alt_page_if_exists($table, 'add');

if (!$table->checkAuth ()) {
    $_SESSION[ADMIN_KEY]['err'] = 'Invalid table';
    redirect ('./');
}

list ($urls, $seps) = $table->getPageUrls ();

require 'head.php';

tricho\Runtime::load_help_text($table);

// get the view items
$view_items = $table->getView('add');
$form = new Form();
$form->setType('add');
$form->setTable($table);

// alt button text
$button_text = $table->getAltButtons ();
if (@$button_text['add'] == '') $button_text['add'] = 'Add';
if (@$button_text['cancel'] == '') $button_text['cancel'] = 'Cancel';

// include JS editor stuff if there are any columns that require it, and check for file fields
$file_uploads_required = false;
$tinymce_fields = array ();
foreach ($view_items as $item) {
    if (!($item instanceof ColumnViewItem)) continue;
    $col = $item->getColumn();
    if ($col instanceof TinymceColumn) {
        $tinymce_fields[] = $col;
    } else if ($col instanceof FileColumn) {
        $file_uploads_required = true;
    }
}

// Richtext editor stuff
$has_a_richtext_editor = false;
if (count($tinymce_fields) > 0) {
    $has_a_richtext_editor = true;
?>
<script type="text/javascript">
<!--
<?php
init_tinymce($tinymce_fields);
?>
//-->
</script>
<noscript><p><b>Javascript must be enabled to use this form.</b></p></noscript>
<?php
}

// main form
echo "<div id=\"main_data\">\n";

// tabs
$parents = array();
$parent_table = null;
if (trim(@$_GET['p']) != '') {
    $parents = explode (',', $_GET['p']);
    if (count ($parents) > 0) {
        list ($parent_table) = explode ('.', $parents[0]);
    }
}

if ($db->getShowPrimaryHeadings ()) {
    if (count($parents) > 0) {
        
        list ($ancestor_name) = explode ('.', $parents[count($parents) - 1]);
        $ancestor_table = $db->getTable ($ancestor_name);
        
        echo "<h2>{$ancestor_table->getEngName ()}</h2>";
    } else {
        echo "<h2>{$table->getEngName ()}</h2>";
    }
}

show_parent_siblings($table, $parents);

if ($db->getShowSectionHeadings()) {
    if (count($parents) > 0 or $db->getShowPrimaryHeadings ()) {
        echo "<h3>Adding new {$table->getNameSingle ()}</h3>";
    } else {
        echo "<h2>Adding new {$table->getNameSingle ()}</h2>";
    }
}

// comments
if ($parent_table != null) {
    $filename = 'advice/' . strtolower ($_GET['t']) . '.' . strtolower ($parent_table) . '.add.php';
    if (file_exists ($filename)) {
        @include $filename;
    } else {
        @include 'advice/' . strtolower($_GET['t']) . '.add.php';
    }
} else {
    @include 'advice/' . strtolower($_GET['t']) . '.add.php';
}

// online help
$help_table = $db->getHelpTable ();
$help_columns = array ();
if ($help_table != null) {
    $q = "SELECT HelpColumn, QuickHelp, HelpText
        FROM `{$help_table->getName()}` WHERE HelpTable = '{$_GET['t']}'";
    if (@$_SESSION['setup']['view_q']) echo "<pre>Help Q: {$q}</pre>";
    $res = execq($q);
    while ($row = fetch_assoc($res)) {
        $help_columns[$row['HelpColumn']] = array (
            'QuickHelp' => trim ($row['QuickHelp']),
            'HelpText' => (trim ($row['HelpText']) != ''? true: false)
        );
    }
}

check_session_response('admin');

// Display form
$id = empty($_GET['f'])? '': $_GET['f'];
$form = new Form($id);
if ($id == '') $id = $form->getID();
$form->setFormURL('add.php?t=' . $table->getName());
$form->setActionURL('add_action.php');
$form->load("admin.{$table->getName()}");
$form->setType('add');
if (!isset($_SESSION['forms'][$id])) {
    $_SESSION['forms'][$id] = ['values' => [], 'errors' => []];
}
$session = &$_SESSION['forms'][$id];
$doc = $form->generateDoc($session['values'], $session['errors']);

$form_el = $doc->getElementsByTagName('form')->item(0);
$params = ['type' => 'hidden', 'name' => '_t', 'value' => $table->getName()];
HtmlDom::appendNewChild($form_el, 'input', $params);

echo $doc->saveXML($doc->documentElement);

echo "</div>\n";

require "foot.php";
?>
