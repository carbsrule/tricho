<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\DataUi\Form;
use Tricho\DataUi\FormManager;
use Tricho\Meta\Database;

require '../../tricho.php';
test_setup_login(true, SETUP_ACCESS_LIMITED);

$db = Database::parseXML();
$table = $db->getTable($_POST['t']);
if ($table == null) {
    $_SESSION['setup']['err'] = 'Invalid table specified';
    redirect('./');
}

$column = $table->get($_POST['col']);
if ($column == null) {
    $_SESSION['setup']['err'] = 'Unknown column specified';
    redirect('./');
}

$forms = FormManager::loadAll();
$changed_forms = [];
foreach ($forms as $form_file) {
    $form = FormManager::load($form_file);
    if (!$form) continue;
    if ($form->getTable() != $table) continue;
    $item = $form->getColumnItem($column);
    if (!$item) continue;
    $form->removeItem($item);
    $changed_forms[] = $form;
}

$url = 'table_edit_cols.php?t=' . urlencode($_POST['t']);
if ($table->removeColumn($column)) {
    foreach ($changed_forms as $form) {
        FormManager::save($form);
    }
    
    // Allow removing definition even if column is missing from the actual DB
    $db->dumpXML('', null);
    try {
        $q = "ALTER TABLE `" . $table->getName() . "` DROP COLUMN `" .
            $_POST['col'] . '`';
        execq($q);
        
        $log_message = "Removed column " . $table->getName () . '.' . $_POST['col'];
        log_action($log_message, $q);
    } catch (QueryException $ex) {
    }
    redirect($url);
    
} else {
    $_SESSION['setup']['err'] = 'Unable to remove column.';
}

redirect($url);
?>
