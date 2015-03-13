<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\DbConn\ConnManager;
use Tricho\Meta\Database;

require_once '../../tricho.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);

$db = Database::parseXML();
$table = $db->getTable($_POST['t']);
if ($table == null) redirect ('./');

$index = trim ($_POST['index']);

$url = 'table_edit_indexes.php?t=' . urlencode($_POST['t']);
if ($index == '') {
    $_SESSION['setup']['err'] = 'Invalid index';
    redirect($url);
}

// Check before removing the key to see if there is a constraint that prevents its removal
// If there's an AUTO_INCREMENT column that is contained only in the specified index, and not in any
// other indexes, then the index cannot be removed

$table_auto_inc_col = null;
$res = execq("SHOW COLUMNS FROM `{$table->getName ()}`");
while ($row = fetch_assoc($res)) {
    if (stripos ($row['Extra'], 'auto_increment') !== false) {
        $table_auto_inc_col = $row['Field'];
        break;
    }
}

if ($table_auto_inc_col != null) {
    // we of course only do these checks if there actually is an auto_increment column
    $removal_unsafe = true;
    $res = execq("SHOW INDEX FROM `{$table->getName ()}`");
    while ($row = fetch_assoc($res)) {
        if ($row['Key_name'] != $index and $row['Non_unique'] == 0
                and $row['Column_name'] == $table_auto_inc_col) {
            $removal_unsafe = false;
        }
    }
    
    if ($removal_unsafe) {
        $_SESSION['setup']['err'] = "Cannot delete the index <em>{$_POST['index']}</em>, as it alone ".
            "contains an AUTO INCREMENT column. Add another unique index on the column ".
            "<em>{$table_auto_inc_col}</em> if you want to remove the index <em>{$_POST['index']}</em>.";
        redirect($url);
    }
}

$q = "ALTER TABLE `". $table->getName (). '` DROP INDEX `'. $index. '`';
if (execq($q, false, false, false, false)) {
    $_SESSION['setup']['msg'] = "Index <em>{$_POST['index']}</em> deleted";
    
    $db = Database::parseXML();
    $log_message = "Removed index {$index} from {$table->getName()}";
    log_action ($db, $log_message, $q);
    
} else {
    $conn = ConnManager::get_active(); 
    $_SESSION['setup']['err'] = 'Unable to delete index due to a ' .
        'database error: ' . $conn->last_error();
}


redirect($url);

?>
