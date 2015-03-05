<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Runtime;
use Tricho\Util\SqlParser;

require '../tricho.php';

$root = Runtime::get('root_path');
require "{$root}tricho/db/sql_parser.php";

function install_error($field) {
    if (!isset($_SESSION['install']['err'][$field])) return '';
    $err = $_SESSION['install']['err'][$field];
    $output = '<p class="error">';
    $output .= implode("<br>\n", $err);
    $output .= "</p>\n";
    return $output;
}

// check _tricho_users has no data
$q = "SHOW TABLES LIKE '_tricho%'";
$res = execq($q);
if ($row = fetch_row($res)) {
    die('<p>Database already installed :)</p>');
}

// check admin dir or admin/tables.xml is writeable
$writeable = true;
$xml_loc = "{$root}admin/tables.xml";
if (file_exists($xml_loc)) {
    if (!is_writeable($xml_loc)) $writeable = false;
} else if (!is_writeable("{$root}admin")) {
    $writeable = false;
}
if (!$writeable) {
    die('<p><strong>Error:</strong> tables.xml not writeable.</p>');
}

if (count($_POST) > 0) {
    $_POST['user'] = @trim($_POST['user']);
    
    $errs = array();
    if ($_POST['user'] == '') {
        $errs['user'][] = 'Required field';
    }
    if (strlen($_POST['pass']) < PASSWORD_MIN_LENGTH) {
        $errs['pass'][] = 'Minimum ' .  PASSWORD_MIN_LENGTH . ' characters';
    }
    if ($_POST['pass'] != $_POST['pass2']) {
        $errs['pass'][] = "Passwords didn't match";
    }
    
    if (count($errs) > 0) {
        $_SESSION['install']['user'] = $_POST['user'];
        $_SESSION['install']['err'] = $errs;
        redirect($_SERVER['PHP_SELF']);
    }
    
    execq("START TRANSACTION");
    $parser = new SqlParser();
    $queries = $parser->parse(file_get_contents('install/tables.sql'));
    if (!$queries) {
        die('<p><strong>Error:</strong> missing table create queries.</p>');
    }
    foreach ($queries as $q) execq($q);
    
    $queries = $parser->parse(file_get_contents('install/tlds.sql'));
    if (!$queries) {
        die('<p><strong>Error:</strong> missing TLD values.</p>');
    }
    foreach ($queries as $q) execq($q);
    
    // user insert
    $db = Database::parseXML('install/tables.xml');
    $table = $db->get('_tricho_users');
    if (!$table) {
        die('<p><strong>Error:</strong> missing definition of _tricho_users.</p>');
    }
    $q = new InsertQuery($table, array(
        'User' => $_POST['user'],
        'Pass' => $table->get('Pass')->encrypt($_POST['pass']),
        'AccessLevel' => 2
    ));
    execq($q);
    
    // copy tables.xml
    if (!copy('install/tables.xml', $xml_loc)) {
        die('<p><strong>Error:</strong> failed to copy tables.xml</p>');
    }
    
    execq('COMMIT');
    
    unset($_SESSION['install']);
    
    $_SESSION['admin']['id'] = $_POST['user'];
    $_SESSION['setup']['id'] = $_POST['user'];
    $_SESSION['setup']['level'] = 2;
    
    redirect(ROOT_PATH_WEB . "admin/");
}

header('Content-Type: text/html; charset=UTF-8');
$user = '';
if (isset($_SESSION['install']['user'])) {
    $user = hsc($_SESSION['install']['user']);
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Tricho installation</title>
    <link rel="stylesheet" type="text/css" href="<?= ROOT_PATH_WEB; ?>css/messages.css">
    <style type="text/css">
    input[type=text] { width: 228px; }
    input[type=password] { width: 228px; }
    .label { margin-bottom: 4px; }
    .error {
        margin-top: 4px;
        margin-bottom: 4px;
        width: 218px;
        border-radius: 2px;
    }
    .input { margin-top: 4px; }
    </style>
</head>
<body>
<h1>Tricho installation</h1>

<p>As part of the installation, you need to set up a user account.<br>
Please do so below.</p>

<form method="post" action="<?= $_SERVER['PHP_SELF']; ?>">
<p class="label"><label for="user">User</label></p>
<?= install_error('user'); ?>
<p class="input"><input id="user" type="text" name="user" value="<?= $user; ?>" maxlength="30"></p>


<p class="label"><label for="pass">Password</label></p>
<?= install_error('pass'); ?>
<p class="input"><input id="pass" type="password" name="pass"></p>

<p class="label"><label for="pass2">Confirm password</label></p>
<p class="input"><input id="pass2" type="password" name="pass2"></p>

<p><input type="submit" value="Install"></p>
</form>

</body>
</html>
