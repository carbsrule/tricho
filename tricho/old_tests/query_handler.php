<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../../tricho.php';

$q = "DROP TABLE IF EXISTS Test";
execq($q);

$q = "CREATE TEMPORARY TABLE Test (a TINYINT, b TINYINT, d DATE)";
execq($q);

$q = "INSERT INTO Test (a, b, d) VALUES (10, 10, '2007-01-01'), (10, 20, '2007-01-01'),
    (10, 30, '2007-01-01'), (20, 10, '2007-01-02'), (20, 20, '2007-01-02'), (40, 20, '2007-01-02')";
execq($q);



/* TEST 1 */
$handler = new SelectQuery ();

$table = new QueryTable ('Test');
$handler->setBaseTable ($table);

$col_a = new QueryColumn ($table, 'a');
$col_b = new QueryColumn ($table, 'b');
$func1 = new QueryFunction ('COUNT', array($col_a));
$func2 = new QueryFunction ('POW', array($col_b, new QueryFieldLiteral ('2', false)));

$func1->setAlias ('count');
$func2->setAlias ('bee_squared');
$col_b->setAlias ('bee');

$handler->addSelectField ($col_b);
$handler->addSelectField ($func2);
$handler->addSelectField ($func1);

$handler->addGroupBy ($func2);

$q = (string) $handler;
echo "<p>Test 1:</p><pre>{$q}\n\n";
$res = execq($q);
while ($row = fetch_assoc($res)) {
    foreach ($row as $key => $val) {
        echo "{$key} = {$val}    ";
    }
    echo "\n";
}
echo '</pre>';



/* TEST 2 */
$handler = new SelectQuery ();

$table = new QueryTable ('Test');
$handler->setBaseTable ($table);

$col_a = new QueryColumn ($table, 'a');
$col_b = new QueryColumn ($table, 'b');
$func1 = new QueryFunction ('COUNT', array($col_a));
$func2 = new QueryFunction ('POW', array($col_b, new QueryFieldLiteral ('2', false)));

$func1->setAlias ('count');

$handler->addSelectField ($col_b);
$handler->addSelectField ($func2);
$handler->addSelectField ($func1);

$handler->addGroupBy ($func2);

$q = (string) $handler;
echo "<br><p>Test 2:</p><pre>{$q}\n\n";
$res = execq($q);
while ($row = fetch_assoc($res)) {
    foreach ($row as $key => $val) {
        echo "{$key} = {$val}    ";
    }
    echo "\n";
}
echo '</pre>';



/* TEST 3 */
$handler = new SelectQuery ();

$table = new QueryTable ('Test');
$handler->setBaseTable ($table);

$col_a = new QueryColumn ($table, 'a');
$col_b = new QueryColumn ($table, 'b');
$func1 = new QueryFunction ('COUNT', array($col_a));
$func2 = new QueryFunction ('POW', array($col_b, new QueryFieldLiteral ('2', false)));

$func1->setAlias ('count');

$handler->addSelectField ($col_b);
$handler->addSelectField ($func2);
$handler->addSelectField ($func1);

$handler->addGroupBy ($col_b);

$q = (string) $handler;
echo "<br><p>Test 3:</p><pre>{$q}\n\n";
$res = execq($q);
while ($row = fetch_assoc($res)) {
    foreach ($row as $key => $val) {
        echo "{$key} = {$val}    ";
    }
    echo "\n";
}
echo '</pre>';



/* TEST 4 */
$handler = new SelectQuery ();

$table = new QueryTable ('Test');
$handler->setBaseTable ($table);

$col_a = new QueryColumn ($table, 'a');
$col_b = new QueryColumn ($table, 'b');
$func1 = new QueryFunction ('COUNT', array($col_a));
$func2 = new QueryFunction ('POW', array($col_b, new QueryFieldLiteral ('2', false)));

$func1->setAlias ('count');
$col_b->setAlias ('bee');

$handler->addSelectField ($col_b);
$handler->addSelectField ($func2);
$handler->addSelectField ($func1);

$handler->addGroupBy ($col_b);

$q = (string) $handler;
echo "<br><p>Test 4:</p><pre>{$q}\n\n";
$res = execq($q);
while ($row = fetch_assoc($res)) {
    foreach ($row as $key => $val) {
        echo "{$key} = {$val}    ";
    }
    echo "\n";
}
echo '</pre>';



/* TEST 5 */
$handler = new SelectQuery ();

$table = new QueryTable ('Test');
$handler->setBaseTable ($table);

$col_d = new DateTimeQueryColumn ($table, 'd');
$col_b = new QueryColumn ($table, 'b');
$func1 = new QueryFunction ('COUNT', array($col_b));

$func1->setAlias ('count');
$col_b->setAlias ('bee');
$col_d->setAlias ('crazee');

$handler->addSelectField ($col_d);
$handler->addSelectField ($func1);

$handler->addGroupBy ($col_d);

$q = (string) $handler;
echo "<br><p>Test 5:</p><pre>{$q}\n\n";
$res = execq($q);
while ($row = fetch_assoc($res)) {
    foreach ($row as $key => $val) {
        echo "{$key} = {$val}    ";
    }
    echo "\n";
}
echo '</pre>';


$q = "DROP TABLE Test";
execq($q);
?>
