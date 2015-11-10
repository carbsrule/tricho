<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';
test_setup_login (true, SETUP_ACCESS_FULL);

$url = 'info.php?view=session';
if (!isset($_POST['key'])) redirect($url);

$ok = false;
if (isset($_POST['del'])) $ok = true;
if (isset($_POST['change']) and isset($_POST['value'])) $ok = true;
if (!$ok) redirect($url);

$parts = explode('|', $_POST['key']);
foreach ($parts as $key => $val) {
    if ($val == '') {
        unset($parts[$key]);
        continue;
    }
    break;
}
if (count($parts) == 0) redirect($url);
if (reset($parts) == '_tricho') redirect($url);
if (reset($parts) == 'setup') redirect($url);
if (reset($parts) == 'admin') {
    if (count($parts) == 1) redirect($url);
    if (count($parts) == 2 and end($parts) == 'id') redirect($url);
}


$key = array_pop($parts);
$session = &$_SESSION;
foreach ($parts as $part) {
    $session = &$session[$part];
}

if (isset($_POST['del'])) {
    unset($session[$key]);
    redirect($url);
}

$type = gettype($session[$key]);
switch ($type) {
    case 'string': $value = (string) $_POST['value']; break;
    case 'integer': $value = (int) $_POST['value']; break;
    case 'double': $value = (float) $_POST['value']; break;
    case 'boolean': $value = (bool) (int) $_POST['value']; break;
    default: die('No good!: ' . $type); redirect($url);
}

$session[$key] = $value;
redirect($url);
