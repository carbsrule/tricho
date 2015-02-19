<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../tricho.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);
$db = Database::parseXML();

$help_table = $db->getHelpTable ();

if ($help_table == null) {
    $_SESSION[ADMIN_KEY]['err'] = 'No help table has been defined for this database';
    redirect ('./');
}

$_GET['t'] = trim ($_GET['t']);
$_GET['c'] = trim ($_GET['c']);

$table = $db->get ($_GET['t']);
if ($table == null) {
    $_SESSION[ADMIN_KEY]['err'] = 'Invalid table '. $_GET['t'];
    redirect ('./');
}

$column = $table->get ($_GET['c']);
if ($column == null) {
    $_SESSION[ADMIN_KEY]['err'] = 'Invalid column '. $_GET['t']. '.'. $_GET['c'];
    redirect ('./');
}

$q = "SELECT 1 FROM `". $help_table->getName (). "` WHERE HelpTable = ". sql_enclose ($_GET['t']).
    " AND HelpColumn = ". sql_enclose ($_GET['c']);
$res = execq($q);
if ($res->rowCount() == 0) {
    $q = "INSERT INTO `". $help_table->getName (). "` SET HelpTable = ". sql_enclose ($_GET['t']).
        ", HelpColumn = ". sql_enclose ($_GET['c']);
    execq($q);
}

redirect('edit.php?t=' . $help_table->getName() . "&id={$_GET['t']},{$_GET['c']}");
?>
