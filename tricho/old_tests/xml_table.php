<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';

echo '<pre>';

$table = new Table ();

$col1 = new Column ();
$col1->setName ('Whee');
$col1view = new ColumnViewItem();
$col1view->setDetails ($col1, true);

$col2 = new Column ();
$col2->setName ('Whoo');
$col2view = new ColumnViewItem();
$col2view->setDetails ($col2, true);

$col3 = new Column ();
$col3->setName ('Argh');
$col3view = new ColumnViewItem();
$col3view->setDetails ($col3, true);


$table->addColumn ($col1);
$table->addColumn ($col2);
$table->addColumn ($col3);
$table->appendView('list', $col1view);
$table->appendView('list', $col2view);
$table->appendView('list', $col3view);


echo "\nTest 1: \$table->getColumnInView('list', 'Whee');\n";
echo "should return ColumnViewItem for 'Whee'\n";
$result = $table->getColumnInView('list', 'Whee');
echo "{$result}\n";
if ($result === $col1view) {
    echo "Matches\n";
}

echo "\nTest 2: \$table->getColumnInView('list', 'Whoo', true);\n";
echo "should return index of ColumnViewItem for 'Whoo', which should be 1 \n";
$result = $table->getColumnInView('list', 'Whoo', true);
var_dump ($result);

echo "\nTest 3: \$table->getColumnInView('list', 'Stupid');\n";
echo "should return null\n";
$result = $table->getColumnInView('list', 'Stupid');
var_dump ($result);

echo '</pre>';

?>
