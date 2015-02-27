<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';

if (empty($_GET['f'])) {
    report_error('Invalid form');
    die();
}

$form = new Form();
try {
    $form->load($_GET['f'], true);
} catch (Exception $ex) {
    report_error('Invalid form');
    die();
}
?>
<h2>Delete a form</h2>
Are you sure you want to delete <?= $_GET['f']; ?>?<br>

<table>
<tr>
<form method="get" action="./"><td><input type="submit" value="&lt;&lt; No"></td></form>
<form method="post" action="form_del_action.php">
    <input type="hidden" name="form" value="<?= hsc($_GET['f']); ?>">
    <td><input type="submit" value="Yes &gt;&gt;"></td>
</form>
</tr>
</table>
</form>
