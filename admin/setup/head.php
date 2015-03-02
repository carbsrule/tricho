<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../../tricho.php';
require_once ROOT_PATH_FILE. 'tricho/data_objects.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);

use Tricho\Runtime;
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title>Setup: <?= tricho\Runtime::get('site_name'); ?></title>
        <link rel="stylesheet" type="text/css" href="../../css/messages.css">
        <link rel="stylesheet" type="text/css" href="style.css">
<?php
if (!empty($css_files)) {
    $css_files = (array) $css_files;
    foreach ($css_files as $css_file) {
?>
        <link rel="stylesheet" type="text/css" href="<?= hsc($css_file); ?>">
<?php
    }
}
?>
        <script type="text/javascript" src="../../tricho/functions.js"></script>
        <script type="text/javascript" src="../../tricho/dom.js"></script>
        <script type="text/javascript" src="functions.js"></script>
        <script type="text/javascript" src="../functions.js"></script>
        <script type="text/javascript" src="../../tricho/ajax/config.js"></script>
        <script type="text/javascript" src="../../tricho/ajax/queue.js"></script>
        <script type="text/javascript" src="../../tricho/ajax/base_handlers.js"></script>
<?php
if (!empty($js_files)) {
    $js_files = (array) $js_files;
    foreach ($js_files as $js_file) {
?>
        <script type="text/javascript" src="<?= hsc($js_file); ?>"></script>
<?php
    }
}
?>
<?php
if (strpos ($_SERVER['PHP_SELF'], 'table_create0.php') !== false) {
?>
        <script type="text/javascript" src="collations.js.php"></script>
<?php
}
if (strpos ($_SERVER['PHP_SELF'], 'table_create1.php') !== false or
        strpos ($_SERVER['PHP_SELF'], 'table_edit_col_add.php') !== false or
        strpos ($_SERVER['PHP_SELF'], 'table_edit_col_edit.php') !== false) {
?>
        <script type="text/javascript" src="column_classes.js.php"></script>
<?php
}
?>
    </head>
<?php

echo '    <body onload="';
if (strpos ($_SERVER['SCRIPT_NAME'], 'table_edit.php') !== false) {
    echo 'show_tree_options (); ';
}
if (isset($onload_javascript)) {
    echo $onload_javascript;
    if ($onload_javascript[strlen (rtrim ($onload_javascript)) - 1] != ';') {
        echo '; ';
    } else {
        echo ' ';
    }
    unset ($onload_javascript);
}
echo "nice_labels();\">\n";
?>
    
    <h1>Setup</h1>
<?php

// check write permissions
$filename = Runtime::get('root_path') . 'tricho/data/tables.xml';

$permissions_ok = true;
if (is_file($filename) and !is_writeable($filename)) {
    $permissions_ok = false;
}
if (!is_writeable(dirname($filename))) {
    $permissions_ok = false;
}

if ($permissions_ok) {
    $db = Database::parseXML();
?>

<table id="setup-head"><tr>
<td>
<?php
    $tables = $db->getOrderedTables();
    $forms = FormManager::loadAll();
    if (count($tables) > 0) {
?>
    <form action="table_edit_pre.php" method="get">
    Tables:
    <select name="table">
<?php
        foreach ($tables as $table) {
            
            // check user has access to the table
            if ($table->getAccessLevel () == TABLE_ACCESS_SETUP_FULL and
                    $_SESSION['setup']['level'] == SETUP_ACCESS_LIMITED) {
                continue;
            }
            
            echo "<option value=\"", $table->getName (), "\"";
            if ($table->getName() == @$_GET['t']) {
                echo ' selected="selected"';
            }
            echo ">", $table->getName(), ' (', $table->getEngName(),
                ")</option>\n";
        }
?>
    </select>
    <input name="action" type="submit" value="Edit">
    <input name="action" type="submit" value="Copy">
    <input name="action" type="submit" value="Delete">
    </form>
<?php
    } else {
        echo "<p class=\"warning\">No tables are defined for this database</p>\n";
    }
    
    if (count($forms) > 0) {
?>
    <form action="form_edit_pre.php" method="post">
    Forms:
    <select name="form">
<?php
    $selected_form = @$_GET['f'];
    foreach ($forms as $each_form) {
        echo '<option value="', hsc($each_form), '"';
        if ($each_form == $selected_form) echo ' selected="selected"';
        echo '>', hsc($each_form), "</option>\n";
    }
?>
    </select>
    <input name="task" type="submit" value="Edit">
    <input name="task" type="submit" value="Delete">
    </form>
<?php
    }
?>
</td>
<td>
    <ul style="margin-bottom: 3px;">
        <li><a href="database_details.php">Change database settings</a></li>
        <li><a href="table_create0.php">Create a table</a></li>
        <li><a href="form_edit.php">Create a form</a></li>
        <li><a href="tables_download.php">Download tables.xml</a></li>
<?php
    if ($_SESSION['setup']['level'] == SETUP_ACCESS_FULL) {
        if (strpos (__FILE__, '/test/') !== false) {
            $test_path = realpath (dirname (__FILE__). '/../tables.xml');
            $dev_path = str_replace ('/test/', '/dev/', $test_path);
            if (file_writeable ($dev_path)) {
                $msg = '';
                $test_size = filesize ($test_path);
                $dev_size = @filesize ($dev_path);
                if ($dev_size == 0) {
                    $msg = ' [new]';
                } else if ($test_size != $dev_size
                        or file_get_contents ($test_path) != @file_get_contents ($dev_path)) {
                    $msg = ' [different]';
                }
                if ($msg) {
                    echo "        <li><a href=\"tables_copy.php\">Copy tables.xml to dev</a>{$msg}</li>\n";
                }
            }
        }
    }
    
    
    if (@$_GET['t'] == '') {
?>
        <li><a href="../">Administration</a></li>
<?php
    } else {
?>
        <li><a href="../browse.php?t=<?= urlencode($_GET['t']); ?>">Administration</a></li>
<?php
    }
?>
        <li><a href="../logout.php">Log out</a></li>
    </ul>
</td>
</tr></table>
<?php
} else {
    report_error("Write permission denied for tables.xml or data dir");
}

report_session_info ('err', 'setup');
?>
