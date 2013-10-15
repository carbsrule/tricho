<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', true);
header('Content-type: text/plain');
require '../../tricho.php';

$conn = ConnManager::get_active();

$res = execq("SELECT NOW()");
$row = $res->fetch();
print_r($row);

$res = execq("SELECT CONCAT('Yeah ', " . sql_enclose('man') . ')');
$row = $res->fetch();
print_r($row);

execq('DROP TABLE IF EXISTS _test_crud');
$q = "CREATE TABLE _test_crud (
    ID INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(20) NOT NULL DEFAULT ''
)";
if (!execq($q)) {
    echo "Failed to create _test_crud table\n", $conn->last_error();
}

$q = new InsertQuery('_test_crud', array('Name' => "Bob's Balloons"));
execq($q);
echo $q, "\n";

$res = execq("SELECT * FROM _test_crud");
$row = $res->fetch();
print_r($row);
