<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
This file exists to test slow query reporting, and error e-mails generated when queries fail.

Five test queries are run that should succeed - three of these should each generate a slow query report
A further two test queries are run that should fail - each should generate an error e-mail
*/

require_once '../../tricho.php';

define ('LARGE_TABLE_SIZE', 1000);
define ('SMALL_TABLE_SIZE', 20);
$start_time = microtime (true);

echo "<pre>";


// Create the table, and populate it
echo '[', right_now (), "] Preparing tables.\n";
flush ();
$q = "DROP TABLE IF EXISTS Large
    /* Not a SELECT query => should not generate a slow query report */";
execq($q);
$q = "DROP TABLE IF EXISTS SmallJoinTarget
    /* Not a SELECT query => should not generate a slow query report */";
execq($q);

$q = "CREATE TABLE Large (num int primary key, data1 int, data2 int)
    /* Not a SELECT query => should not generate a slow query report */";
execq($q);

execq("START TRANSACTION");

for ($i = 0; $i < LARGE_TABLE_SIZE; $i++) {
    $d1 = rand (1, SMALL_TABLE_SIZE);
    $d2 = rand (1, LARGE_TABLE_SIZE);
    $q = "INSERT INTO Large (num, data1, data2) VALUES ({$i}, {$d1}, {$d2})
        /* Not a SELECT query => should not generate a slow query report */";
    execq($q);
}

$q = "CREATE TABLE SmallJoinTarget (num int, data char(3))
    /* Not a SELECT query => should not generate a slow query report */";
execq($q);
for ($i = 0; $i < SMALL_TABLE_SIZE; $i++) {
    $d = generate_code (3, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ');
    $q = "INSERT INTO SmallJoinTarget (num, data) VALUES ({$i}, '{$d}')
        /* Not a SELECT query => should not generate a slow query report */";
    execq($q);
}

execq("COMMIT");


// Select query #1
echo '[', right_now (), "] Executing a join query (unindexed).\n";
flush ();
$q = "SELECT * FROM Large
    LEFT JOIN SmallJoinTarget ON Large.data1 = SmallJoinTarget.num
    /* No indexes => should generate a slow query report */";
execq($q);


// Select query #2
function slow_query ($arg1, $arg2) {
    echo '[', right_now (), "] Executing a join query (unindexed, from within a function).\n";
    flush ();
    $q = "SELECT * FROM Large
        LEFT JOIN SmallJoinTarget ON Large.data1 = SmallJoinTarget.num
        /* No indexes => should generate a slow query report */";
    execq($q);
}
slow_query (42, 133.7);


// Add an index to the table
echo '[', right_now (), "] Adding indexes.\n";
flush ();
$q = "ALTER TABLE Large ADD INDEX (data1)
    /* Not a SELECT query => should not generate a slow query report */";
execq($q);

$q = "ALTER TABLE SmallJoinTarget ADD primary key (num)
    /* Not a SELECT query => should not generate a slow query report */";
execq($q);


// Select query #3
echo '[', right_now (), "] Executing a join query (indexed).\n";
flush ();
$q = "SELECT * FROM Large
    LEFT JOIN SmallJoinTarget ON Large.data1 = SmallJoinTarget.num
    /* Indexes defined => should not generate a slow query report */";
execq($q);


// Select query #4
echo '[', right_now (), "] Executing table scan.\n";
flush ();
$q = "SELECT SQL_NO_CACHE * FROM Large
    /* Single table scan with no 'WHERE' clause => should not generate a slow query report */";
execq($q);

// Select query #5
echo '[', right_now (), "] Executing table scan with a WHERE clause that ought to be indexed.\n";
flush ();
$q = "SELECT SQL_NO_CACHE * FROM Large WHERE data2 = 1
    /* Single table scan using unindexed 'WHERE' clause => should generate a slow query report */";
execq($q);


// Execute a bad query
echo '[', right_now (), "] Executing bad query.\n";
flush ();
$q = "SELECT * FROM NonExistentTable";
try {
    execq($q, false, true);
} catch (QueryException $ex) {
}


// Execute a bad query from a function
function bad_query ($arg1, $arg2) {
    echo '[', right_now (), "] Executing bad query from a function.\n";
    flush ();
    $q = "SELECT * FROM NonExistentTable";
    try {
        execq($q, false, true);
    } catch (QueryException $ex) {
    }
}
bad_query ('whee', 'foobarbaz');


// Remove the table
echo '[', right_now (), "] Removing test tables.\n";
flush ();
$q = "DROP TABLE IF EXISTS Large";
execq($q);
$q = "DROP TABLE IF EXISTS SmallJoinTarget";
execq($q);


function right_now () {
    global $start_time;
    
    return sprintf ('%11.8f', microtime (true) - $start_time);
}
?>
