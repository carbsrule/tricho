<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';
require_once '../tricho/data_objects.php';
test_admin_login ();
$db = Database::parseXML ('tables.xml');
$table = $db->getTable ($_POST['_t']);
list ($urls, $seps) = $table->getPageUrls ();

// clear previous search data, so that search can be updated according to what has been posted
unset ($_SESSION[ADMIN_KEY]['partition'][$table->getName ()]);

$partition = $table->getPartition ();
if ($partition !== null) {
    $result = validate_type (
        $_POST[$partition->getName ()],
        $partition->getType (),
        $partition->getTextFilterArray (),
        false,
        $partition
    );
    
    if (! $result->isRubbish ()) {
        $_SESSION[ADMIN_KEY]['partition'][$table->getName ()] = $result->getValue ();
    }
}

// redirect back
$url = "{$urls['main']}{$seps['main']}t={$table->getName ()}";
if ($_GET['p'] != '') $url .= "&p={$_GET['p']}";
redirect ($url);

?>
