<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
test_setup_login();

$path = '../../tricho/data/' . basename($_POST['form']) . '.form.xml';
if (!file_exists($path)) {
    report_error("Unknown form");
    die ();
}

if (unlink($path)) {
    $_SESSION['setup']['msg'] = "Deleted form";
} else {
    $_SESSION['setup']['err'] = "Failed to delete form";
}
redirect ('./');
?>
