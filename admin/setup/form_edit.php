<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Runtime;

require '../../tricho.php';
test_setup_login();

$form = null;
if (!empty($_GET['f'])) {
    $form_file = $_GET['f'];
    $form = FormManager::load($form_file);
    if ($form == null) {
        require 'head.php';
        echo "<h2>Edit form: {$form_file}</h2>\n";
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

require 'form_edit.inc.php';

require 'foot.php';
