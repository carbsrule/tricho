<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';

$table = new Table ('TestViewRemoval');
$id = new Column ();
$id->setName ('ID');
$table->addColumn ($id);
$name = new Column ();
$name->setName ('Name');
$table->addColumn ($name);

$view_items = array ();
$item = new ColumnViewItem ();
$item->setColumn ($id);
$view_items[] = $item;
$item = new ColumnViewItem ();
$item->setColumn ($name);
$view_items[] = $item;

$item = new IncludeViewItem ();
$item->setFilename ('keep_me.php');
$item->setName ('Keep me');
$view_items[] = $item;
$item = new IncludeViewItem ();
$item->setFilename ('delete_me.php');
$item->setName ('Delete me');
$view_items[] = $item;
$item = new IncludeViewItem ();
$item->setFilename ('delete_me_again.php');
$item->setName ('Delete me again');
$view_items[] = $item;

$item = new HeadingViewItem ();
$item->setName ('Keep me');
$view_items[] = $item;
$item = new HeadingViewItem ();
$item->setName ('Delete this heading');
$view_items[] = $item;

$table->setView('edit', $view_items);

$table->removeFromView('edit', $id);
$table->removeFromView('edit', 'delete_me.php');
$table->removeFromView('edit', 'delete this heading');
$table->removeFromView('edit', 'delete me again');

foreach ($table->getView('edit') as $item) {
    echo $item, "<br>\n";
}
?>
