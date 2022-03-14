<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

$_detect_uname = php_uname('n');
$_detect_uname = preg_replace('/[^a-z0-9_\-\.]/i', '', $_detect_uname);
if (file_exists(__DIR__ . "/{$_detect_uname}.php")) {
    require __DIR__ . "/{$_detect_uname}.php";
} else {
    require __DIR__ . '/dev.php';
}
unset($_detect_uname);
require __DIR__ . '/all.php';
