<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';

test_setup_login (true, SETUP_ACCESS_FULL);

$_GET['t'] = '__tools';
require_once 'head.php';
echo "<div id=\"main_data\">";

if ($db->getShowSectionHeadings ()) {
    echo "<h2>Import Data</h2>";
}


// show an example of the csv
echo "<h3>Sample of CSV</h3>";
echo "<table>";
echo "<tr>";
foreach ($_SESSION[ADMIN_KEY]['import_csv']['headers'] as $header) {
    echo "<th>{$header}</th>";
}
echo "</tr>";
foreach ($_SESSION[ADMIN_KEY]['import_csv']['lines'] as $line) {
    echo "<tr>";
    foreach ($line as $column) {
        echo "<td>{$column}</td>";
    }
    echo "</tr>";
}
echo "</table>";


$table = $data->getTable ($_SESSION[ADMIN_KEY]['import_csv']['table']);

// loop through columns
// try to match column to header
// if col has a link ($col->getLink() ['col']) add to array

// for each link:
//        present option requesting what to do with that link
//        options:
//         - do nothing
//         - add items to child table (array)
//         -
//
//


echo "</div>";
require_once 'foot.php';
?>
