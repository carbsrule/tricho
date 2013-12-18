<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';
if (!isset($_SESSION['setup']['create_table'])) {
    $_SESSION['setup']['create_table'] = array();
}
$session = &$_SESSION['setup']['create_table'];

if (!isset($session['display'])) $session['display'] = 1;

require_once 'setup_functions.php';

$engine_types = get_available_engines ();
$engine_select = '';
foreach ($engine_types as $engine) {
    $engine_select .= "        <option value=\"{$engine}\"";
    if (@$session['engine'] == $engine) {
        $engine_select .= " selected = selected";
    }
    $engine_select .= ">{$engine}</option>\n";
}

if ($engine_select == '') {
    $engine_select = report_error ('No allowed engines are available', true);
} else {
    $engine_select = "\n        <select name=\"Engine\">\n{$engine_select}</select>\n";
}

$collation_mappings = get_available_collation_mappings ();

$charsets = array_keys($collation_mappings);
if (!in_array(@$session['charset'], $charsets)) {
    $session['charset'] = reset($charsets);
}
$charset_select = '';
foreach ($charsets as $charset) {
    $charset_select .= "        <option value=\"{$charset}\"";
    if ($session['charset'] == $charset) {
        $charset_select .= ' selected="selected"';
    }
    $charset_select .= ">{$charset}</option>\n";
}

if ($charset_select == '') {
    $charset_select = report_error ('No allowed charsets are available', true);
} else {
    $charset_select = "\n        <select id=\"charset\" name=\"Charset\"".
        " onchange=\"on_charset_change ();\">\n{$charset_select}</select>\n";
}

$collations = $collation_mappings[$session['charset']];
$collation_select = '';
foreach ($collations as $collation) {
    $collation_select .= "        <option value=\"{$collation}\"";
    if (@$session['collation'] == $collation) {
        $collation_select .= ' selected="selected"';
    }
    $collation_select .= ">{$collation}</option>\n";
}

if ($collation_select == '') {
    $collation_select = report_error ('No collations are available', true);
} else {
    $collation_select = "\n        <select id=\"collation\" name=\"Collation\">\n".
        "{$collation_select}</select>\n";
}
?>
<h2>Create a table</h2>
<form method="post" action="table_create0_action.php" name="tbldata">
<table>
<tr><td>Table name</td><td><input type="text" name="table_name" value="<?= @$session['table_name']; ?>" onchange="set_english_name ();"></td></tr>
<tr><td>English name</td><td><input type="text" name="table_name_eng" value="<?= @$session['table_name_eng']; ?>" onblur="set_single_name ();"></td></tr>
<tr><td>Single name</td><td><input type="text" name="table_name_single" value="<?= @$session['table_name_single']; ?>"></td></tr>

<tr>
    <td>Engine Type</td>
    <td><?php echo $engine_select; ?></td>
</tr>

<tr>
    <td>Character set</td>
    <td><?php echo $charset_select; ?></td>
</tr>

<tr>
    <td>Collation</td>
    <td><?php echo $collation_select; ?></td>
</tr>

<tr>
    <td>Accessible by</td>
    <td>
        <select name="access_level">
<?php
    $access_levels = array (
        TABLE_ACCESS_ADMIN => 'Admins',
        TABLE_ACCESS_SETUP_LIMITED => 'Setup users (incl. those with limited access)',
        TABLE_ACCESS_SETUP_FULL => 'Setup users (only those with full access)'
    );
    foreach ($access_levels as $key => $val) {
        echo "            <option value=\"{$key}\"";
        if ($key == @$session['access_level']) {
            echo ' selected="selected"';
        }
        echo ">{$val}</option>\n";
    }
?>
        </select>
    </td>
</tr>
<tr>
    <td><label for="static">Static content</label></td>
    <td>
        <input type="checkbox" name="static" id="static" value="1"<?php
    if (@$session['static']) echo ' checked="checked"';
?>>
    </td>
</tr>

<tr><td>Display in menu</td><td><label for="menu_y" class="label_plain"><input type="radio" name="display" id="menu_y" value="1"<?php
if (@$session['display']) echo ' checked="checked"'; ?>>Yes</label>
<label for="menu_n" class="label_plain"><input type="radio" name="display" id="menu_n" value="0"<?php
if (@$session['display'] == 0) echo ' checked="checked"'; ?>>No</label>
</td></tr>
<?php
$options = array('add', 'edit', 'del');
foreach ($options as $option) {
?>
<tr>
    <td>Allow <?= $option; ?> operations</td>
    <td><label for="allow_<?= $option; ?>_y" class="label_plain"><input type="radio" name="allow_<?= $option; ?>" id="allow_<?= $option; ?>_y" value="1"<?php
if (@$session['allow_'. $option] != '0') echo ' checked'; ?>>Yes</label>
<label for="allow_<?= $option; ?>_n" class="label_plain"><input type="radio" name="allow_<?= $option; ?>" id="allow_<?= $option; ?>_n" value="0"<?php
if (@$session['allow_'. $option] == '0') echo ' checked'; ?>>No</label></td>
</tr>
<?php
}
?>

<tr><td colspan="2">Comments<br><textarea name="comments" style="width: 100%; height: 5em;" rows="4" cols="50"><?= @$session['comments']; ?></textarea></td></tr>
<tr><td colspan="2" align="right"><input type="submit" value="Continue &gt;&gt;"></td></tr>
</table>
</form>


<?php
require_once 'foot.php';
?>
