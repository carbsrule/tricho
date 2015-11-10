<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../../tricho.php';
test_setup_login (true, SETUP_ACCESS_FULL);

if (strpos (__FILE__, '/test/') === false) {
    die ('Invalid action');
}
$test_path = realpath (dirname (__FILE__). '/../tables.xml');
$dev_path = str_replace ('/test/', '/dev/', $test_path);
if (!file_writeable ($dev_path)) {
    $_SESSION['setup']['err'] = 'Invalid path to dev file';
    redirect ('./');
}
$res = @copy ($test_path, $dev_path);
if ($res) {
    $_SESSION['setup']['msg'] = 'Copied tables.xml to dev dir';
} else {
    $_SESSION['setup']['err'] = 'Failed to copy tables.xml to dev dir';
}
redirect ('./');
?>
