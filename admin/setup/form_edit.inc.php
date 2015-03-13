<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Runtime;
use Tricho\DataUI\ColumnFormItem;

if (!class_exists('Tricho\Runtime', false)) die('Include me');

if (empty($no_heading)) {
    if ($form != null) {
        echo "<h2>Edit form</h2>\n";
    } else {
        echo "<h2>Create form</h2>\n";
    }
}
?>

<form method="post" action="form_edit_action.php" style="float: left;">
<input type="hidden" name="table" value="<?= hsc($form_table->getName()); ?>">

<?php
if ($form) {
?>
<p>Form name: <?= hsc($form_file); ?></p>
<input type="hidden" name="form" value="<?= hsc($form_file); ?>">
<?php
} else {
?>
<p>Form name: <input type="text" name="form"></p>
<?php
}

$ext_dir = Runtime::get('root_path') . 'tricho/ext/';
$modifiers = glob("{$ext_dir}*/DataUi/*Modifier.php");
if (count($modifiers) > 0) {
?>

<p>Modifier: <select name="modifier">
<option value="">- Select modifier -</option>
<?php
    $form_mod = null;
    if ($form) $form_mod = $form->getModifier();
    if ($form_mod) $form_mod = get_class($form_mod);
    foreach ($modifiers as $mod) {
        $ext = trim_start($mod, $ext_dir);
        $ext = substr($ext, 0, strpos($ext, DIRECTORY_SEPARATOR));
        $mod = trim_start($mod, $ext_dir . $ext);
        $short_mod = basename($mod, '.php');
        $mod = 'Tricho' . str_replace(DIRECTORY_SEPARATOR, '\\', $mod);
        
        // Trim '.php'
        $mod = substr($mod, 0, -4);
        $selected = '';
        if ($mod == $form_mod) $selected = ' selected="selected"';
        $ext = hsc($ext);
        $mod = hsc($mod);
        
        echo "<option value=\"{$mod}\"{$selected}>", hsc($short_mod),
            " ({$ext})</option>\n";
    }
?>
</select>
</p>
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
<?php
if (!empty($success_redirect)) {
    echo '<input type="hidden" name="r" value="', hsc($success_redirect),
        "\">\n";
}
?>
</form>

<br class="breaker">
