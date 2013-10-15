<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

header ('Content-Type: text/html; charset=UTF-8');
require_once '../tricho.php';
test_admin_login ();

if (!$db instanceof Database) $db = Database::parseXML ('tables.xml');

// get help table
$help_table = $db->getHelpTable ();
if ($help_table == null) {
    echo '<p>Sorry, there is no help available.</p>';
    exit;
}

$eng_name = 'Help';
    
// check table
$_GET['t'] = trim ($_GET['t']);
if ($_GET['t'] == '') {
    echo '<p>Sorry, there is no help available for this topic.</p>';
    exit;
} else {
    $table = $db->getTable ($_GET['t']);
    if ($table != null) {
        $eng_name = $table->getEngName (). ' - ';
    }
}

// check column
$_GET['c'] = trim ($_GET['c']);
if ($_GET['c'] == '') {
    echo '<p>Sorry, there is no help available for this topic.</p>';
    exit;
} else {
    if ($table != null) {
        $column = $table->get ($_GET['c']);
        if ($column != null) {
            $eng_name .= $column->getEngName ();
        } else {
            $eng_name .= $_GET['c'];
        }
    }
}

// get
$q = "SELECT HelpText FROM `{$help_table->getName ()}` WHERE HelpTable = " .
    sql_enclose((string) $_GET['t']) . " AND HelpColumn = " .
    sql_enclose((string) $_GET['c']). " LIMIT 1";
$res = execq($q);

// output
if ($res->rowCount() == 0) {
    echo '<p>Sorry, there is no help available for this topic.</p>';
    exit;
    
} else {
    $row = fetch_assoc($res);
    
    @include 'help_header.php';
    echo "<h1>{$eng_name}</h1>";
    echo "<p>{$row['HelpText']}</p>";
    @include 'help_footer.php';

}

?>
