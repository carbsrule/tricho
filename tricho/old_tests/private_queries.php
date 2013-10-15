<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
This file exists to test queries that contain private data (e.g. MD5/SHA-encrypted data)
The private data should never be included in slow query or error report e-mails.
*/
require_once '../../tricho.php';

define ('LARGE_TABLE_SIZE', 1000);
define ('SMALL_TABLE_SIZE', 20);
$start_time = microtime (true);

echo "<pre>";

// case #1
echo '[', right_now (), "] Executing private query: single quote used in MD5.\n";
flush ();
$q = "SELECT MD5('It\\'s a stupid query, isn''t it?') FROM NonExistentTable";
echo "test case #1 query: {$q}\n";
execq($q, false, true, true, true);

// case #2
echo '[', right_now (), "] Executing private query: double quote used in MD5.\n";
flush ();
$q = 'SELECT MD5("It\\"s a stupid query, isn""t it?") FROM NonExistentTable';
echo "test case #2 query: {$q}\n";
execq($q, false, true, true, true);

// case #3
echo '[', right_now (), "] Executing private query with a WHERE clause that contains SHA.\n";
flush ();
$q = 'SELECT Email, CONCAT("a", " ", "b")
    FROM NonExistentTable
    WHERE Password = SHA("asdasdg asdhgashk")';
    echo "test case #3 query: {$q}\n";
execq($q, false, true, true, true);

// case #4
echo '[', right_now (), "] Executing private query: SELECT MD5, SHA and SHA1.\n";
flush ();
$q = "SELECT Md5('a'), SHA1('b'), SHA('c') FROM NonExistentTable";
echo "test case #4 query: {$q}\n";
execq($q, false, true, true, true);

// case #5
echo '[', right_now (), "] Executing private query: CONCAT used in MD5.\n";
flush ();
$q = "SELECT MD5(CONCAT('a', MD5('b'))), OtherField = 'some ''string' FROM NonExistentTable";
echo "test case #5 query: {$q}\n";
execq($q, false, true, true, true);


function right_now () {
    global $start_time;
    
    return sprintf ('%11.8f', microtime (true) - $start_time);
}
?>
