<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
test_setup_login(true, SETUP_ACCESS_LIMITED);

$db = Database::parseXML();
$table = $db->getTable($_POST['t']);
if (!$table) redirect('./');

// clear existing alternate pages
list($page_urls, $seps) = $table->getPageUrls();
foreach ($page_urls as $id => $page) {
    $table->unsetAltPage($id);
}

// add new choices
foreach ($page_urls as $id => $page) {
    if (str_replace(' ', '', $_POST[$id]) != '') {
        $table->setAltPage($id, str_replace(' ', '', $_POST[$id]));
    }
}

// test: show data
// echo "<pre>", print_r ($table->getAltPages (), true), "</pre>\n";

// store xml
$url = 'table_edit_alt_pages.php?t=' . urlencode($_POST['t']);
$db->dumpXML('', $url);
?>
