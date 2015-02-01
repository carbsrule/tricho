<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
test_setup_login();

$form = null;
if (!empty($_GET['f'])) {
    $form = FormManager::load($_GET['f']);
    if ($form == null) {
        require 'head.php';
        echo "<h2>Edit form: {$_GET['f']}</h2>\n";
        report_error('Invalid form');
        require 'foot.php';
        die();
    }
}

$db = Database::parseXML();
if ($form != null) {
    $form_table = $form->getTable();
} else {
    if (empty($_GET['t'])) {
        require 'head.php';
        echo "<h2>Create a form</h2>\n";
?>
<form action="" method="get">
<select name="t">
    <option value="">- Select table -</option>
<?php
foreach ($db->getTables() as $each_table) {
    echo '    <option value="', hsc($each_table->getName()), '">', hsc($each_table->getName()), "</option>\n";
}
?>
</select>
<input type="submit" value="Continue &raquo;">
</form>
<?php
        require 'foot.php';
        die();
    } else {
        $form_table = $db->get($_GET['t']);
        if (!$form_table) {
            require 'head.php';
            echo "<h2>Create a form</h2>\n";
            report_error('Invalid table');
            require 'foot.php';
            die();
        }
    }
}

$css_files = ['form_edit.css'];
$js_files = [
    '//code.jquery.com/jquery-1.11.2.min.js',
    '//code.jquery.com/ui/1.11.2/jquery-ui.js',
    'form_edit.js',
];
require 'head.php';
if ($form != null) {
    echo "<h2>Edit form</h2>\n";
} else {
    echo "<h2>Create form</h2>\n";
}
?>

<form method="post" action="form_edit_action.php" style="float: left;">
<input type="hidden" name="table" value="<?= hsc($form_table->getName()); ?>">

<?php
if ($form) {
?>
<p>Form name: <?= hsc($_GET['f']); ?></p>
<input type="hidden" name="form" value="<?= hsc($_GET['f']); ?>">
<?php
} else {
?>
<p>Form name: <input type="text" name="form"></p>
<?php
}
?>

<fieldset class="item-selection">
<legend>Items from <?= $form_table->getName(); ?></legend>
<p><select name="col">
<option value="">- Select column -</option>
<?php
$cols = $form_table->getColumns();
foreach ($cols as $col) {
    $name = hsc($col->getName());
    $class = hsc(get_class($col));
    echo "<option value=\"{$name}\" data-class=\"{$class}\">{$name}</option>\n";
}
?>
</select> <input type="submit" class="faux" value="Add column"></p>

<div class="sortable" id="sortable-items">
<?php
if ($form != null) {
    $items = $form->getItems();
    foreach ($items as $item) {
        $html = '';
        if ($item instanceof ColumnFormItem) {
            list($column, $label, $value, $apply) = $item->toArray();
            
            // This must match the definition in form_edit.js
            $html = '<div><input type="hidden" name="cols[]" value=":name"><input type="hidden" name="labels[]" value=":label"><input type="hidden" name="apply[]" value=":apply">:name <span class="type">(:type)</span><span class="handle">[===]</span><span class="delete">[DEL]</span></div>';
            $html = str_replace(':name', $column->getName(), $html);
            $html = str_replace(':type', get_class($column), $html);
            $html = str_replace(':label', hsc($label), $html);
            $html = str_replace(':apply', hsc($apply), $html);
        }
        
        
        echo $html, "\n";
    }
}
?>
</div>
</fieldset>

<div class="item-edit display-none"></div>

<p><input class="submit" type="submit" value="Save form"></p>
</form>

<br class="breaker">

<?php
require 'foot.php';
