<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Meta\Database;

require '../tricho.php';

if ($_GET['table'] == '') {
    if ($_SERVER['HTTP_REFERER'] != '') {
        redirect ($_SERVER['HTTP_REFERER']);
    } else {
        redirect ('./');
    }
} else {
    $db = Database::parseXML();
    if ($db != null) {
        $table = $db->getTable ($_GET['table']);
        if ($table != null) {
            
            list ($urls, $seps) = $table->getPageUrls ();
            redirect ("{$urls['main']}{$seps['main']}t={$_GET['table']}");
            
        } else {
            $_SESSSION['err'] = 'Invalid table selected';
            redirect ('./');
        }
    } else {
        $_SESSSION['err'] = 'XML corrupted, unable to load database details';
        redirect ('./');
    }
}
?>
