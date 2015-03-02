<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

header ('Content-Type: text/xml; charset=utf-8');
require_once '../tricho.php';
test_admin_login();

$db = Database::parseXML();

// get the 'from' table
$fromTable = $db->getTable ($_GET['t']);
if ($fromTable == null) {
    fatal_error ('Invalid table specified');
}

// get the 'from' column
$fromColumn = $fromTable->get ($_GET['c']);
if ($fromColumn == null) {
    fatal_error ('Invalid column specified');
}

// initialise the dom nodes etc.
$domDocument = new DOMDocument();
$selectNode = $domDocument->createElement ('items');
$domDocument->appendChild ($selectNode);

// get the chooser query
$handler = $fromColumn->getSelectQuery();
$q = cast_to_string($handler);

// output if requested
if ($_SESSION['setup']['view_q']) {
    $optionNode = $domDocument->createElement ('query');
    $optionNode->nodeValue = $q;
    $selectNode->appendChild ($optionNode);
}
$res = execq($q);

// create nodes that match the dataset
while ($row = fetch_assoc($res)) {
    
    // create the node and append to the list
    $optionNode = $domDocument->createElement('item');
    $optionNode->setAttribute('id', $row['ID']);
    $optionNode->nodeValue = htmlspecialchars($row['Value']);
    $selectNode->appendChild($optionNode);
}

// output
echo $domDocument->saveXML ();


/**
 * There was an unrecoverable error
 */
function fatal_error ($message) {
    echo "<error>{$message}</error>";
    exit;
}
?>
