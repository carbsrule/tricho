<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../../tricho.php';

$db = new Database();
$table = new Table();
$table->setName('Data');
$table->addColumn(new IntColumn('ID'));
$table->addColumn(new CharColumn('Name'));
$table->addColumn(new IntColumn('OrderNum'));
$db->addTable($table);
$table->setRowIdentifier(array($table->get('Name')));

$table = new Table();
$table->setName('A');
$table->addColumn(new IntColumn('ID'));
$link = new LinkColumn('Data');
$link->setTarget($db->get('Data')->get('ID'));
$table->addColumn($link);
$table->addColumn(new IntColumn('Value'));
$table->addToOrder('view', $link);
$db->addTable($table);

$table = new Table();
$table->setName('B');
$table->addColumn(new IntColumn('ID'));
$link = new LinkColumn('Data');
$link->setTarget($db->get('Data')->get('ID'));
$table->addColumn($link);
$table->addColumn(new IntColumn('Value'));
$table->addToOrder('view', $link);
$db->addTable($table);

$test_tables = array('A', 'B');

foreach ($test_tables as $table_name) {
    $table = $db->get ($table_name);
    echo "<h1>", $table->getName (), "</h1>";
    
    $links = $table->getLinks ();
    foreach ($links as $link) {
        $link->setOrderingMethod (ORDER_DESCRIPTORS);
    }
    echo "<h4>Test importJoinColumnUsingDescriptorOrdering</h4>";
    $main = new MainTable ($table);
    echo "<pre>", htmlspecialchars ($main->getSelectQuery ()->__toString ()), "</pre>\n";
    
    foreach ($links as $link) {
        $link->setOrderingMethod (ORDER_LINKED_TABLE);
    }
    echo "<h4>Test importJoinColumnUsingTableOrdering</h4>";
    $main = new MainTable ($table);
    echo "<pre>", htmlspecialchars ($main->getSelectQuery ()->__toString ()), "</pre>\n";
}
?>
