<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

if (strpos(__FILE__, '/test/') !== false
        or strpos(__FILE__, '/dev/') !== false
        or strpos(@$_SERVER['SERVER_NAME'], 'localhost') !== false) {
    require __DIR__ . '/test.php';
} else {
    require __DIR__ . '/live.php';
}
require __DIR__ . '/all.php';
