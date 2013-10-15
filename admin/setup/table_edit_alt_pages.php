<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';

$page_opts = array('tab' => 'pages');
require 'table_head.php';
?>

<?php
report_session_info ('err', 'setup');

/*
$options = array (
    'main',
    'main_action',
    'main_add',
    'main_add_action',
    'main_edit',
    'main_edit_action',
    'main_search_action',
    'main_order'
);
*/

list ($pages, $seps) = $table->getPageUrls ();
?>

<form method="post" action="table_edit_alt_pages_action.php">
<table>
    <tr><th>Original</th><th>Alternate</th></tr>
<?php
foreach ($pages as $id => $option) {
    echo "    <tr>\n";
    echo "        <td>{$id}.php</td><td><input type=\"text\" size=\"30\" name=\"", $id, "\"";
    if ($option != $id.'.php') {
        echo " value=\"{$option}\"";
    }
    echo "></td>\n";
    echo "    </tr>\n";
}
?>
<tr><td colspan="2" align="right"><input type="submit" value="Continue"></td></tr>

</table>
<?php
require 'foot.php';
?>
