<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';

$page_opts = array('tab' => 'buttons');
require 'table_head.php';
?>

<?php
report_session_info ('err', 'setup');

$options = array (
    'add',
    'edit',
    'cancel',
    'main_add',
    'main_delete',
    'delete_alert',
    'main_csv',
    'no_records',
    'not_found',
    'add_cond',
    'apply_conds',
    'clear_conds',
);

$alts = $table->getAltButtons ();
?>

<form method="post" action="table_edit_alt_buttons_action.php">
<table>
    <tr><th>Original</th><th>Alternate</th></tr>
<?php
foreach ($options as $id => $option) {
    echo "    <tr>\n";
    echo "        <td>{$option}</td><td><input type=\"text\" name=\"button[{$option}]\" value=\"",
        htmlspecialchars($alts[$option]), "\"></td>\n";
    echo "    </tr>\n";
}
?>
<tr><td colspan="2" align="right"><input type="submit" value="Continue"></td></tr>

</table>
<?php
require 'foot.php';
?>
