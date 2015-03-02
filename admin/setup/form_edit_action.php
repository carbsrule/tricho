<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Runtime;

require '../../tricho.php';
test_setup_login();

$form_name = basename(@$_POST['form'], '.form.xml');
$name_pattern = '/^[-_a-z0-9]+(\.[-_a-z0-9]+)*$/i';
if (empty($_POST['table']) or !preg_match($name_pattern, $form_name)) {
    $_SESSION['setup']['err'] = 'Invalid submission';
    redirect('./');
}

$db = Database::parseXML();
$table = $db->get($_POST['table']);
if (!$table) {
    $_SESSION['setup']['err'] = 'Invalid submission';
    redirect('./');
}

$form = FormManager::load($form_name);
if ($form == null) {
    $form = new Form();
    $form->setFile($form_name);
}

$table = $db->get($_POST['table']);
if (!$table) {
    throw new Exception('Invalid table specified');
}
$form->setTable($table);

if (!empty($_POST['modifier'])) {
    $form->setModifier(new $_POST['modifier']());
} else {
    $form->setModifier(null);
}

$form->removeAllItems();
if (empty($_POST['cols'])) goto no_cols;
foreach ($_POST['cols'] as $key => $col) {
    $item = new ColumnFormItem($table->get($col));
    $label = @$_POST['labels'][$key];
    if ($label) $item->setLabel($label);
    $item->setApply(@$_POST['apply'][$key]);
    $form->addItem($item);
}
no_cols:

$bytes_written = FormManager::save($form);

if ($bytes_written > 0) {
    $_SESSION['setup']['msg'] = 'Form saved';
} else {
    $_SESSION['setup']['err'] = 'Form save failed';
}

if (!empty($_POST['r'])) redirect($_POST['r']);
redirect('./');
