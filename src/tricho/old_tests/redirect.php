<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';

if ($_GET['r'] == '__NULL__') redirect ('');
if ($_GET['r'] != '') redirect ($_GET['r']);

echo "<h1>Test redirect ()</h1>\n";

$locations = array (
    '../',
    '..',
    '/',
    '../admin',
    '../../admin',
    '/admin',
    '.',
    './',
    '../old_tests',
    '../old_tests/',
    '../old_tests/redirect.php',
    '__NULL__'
);

echo "<ul>\n";
foreach ($locations as $loc) {
    echo "<li><a href=\"", basename ($_SERVER['PHP_SELF']), "?r={$loc}\">{$loc}</a></li>\n";
}
echo "</ul>\n";
?>
