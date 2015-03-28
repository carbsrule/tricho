<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Runtime;
use Tricho\DataUi\MenuHolder;
use Tricho\Meta\Table;

header ('Content-Type: text/html; charset=UTF-8');

if (!defined ('ROOT_PATH_FILE')) require_once '../tricho.php';
test_admin_login();

use Tricho\Meta\Database;

$db = Database::parseXML();

require_once ROOT_PATH_FILE. ADMIN_DIR. 'setup_user_options.php';

// Run extra CMS code if there is any
$cms_init_file = ROOT_PATH_FILE. ADMIN_DIR. 'cms_init.php';
if (is_file ($cms_init_file)) require $cms_init_file;

if (!isset ($onload)) $onload = array ();
if (!isset ($js_files)) $js_files = array ();
$onload[] = 'nice_labels ();';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title><?= Runtime::get('site_name'); ?> Administration</title>
        <link rel="stylesheet" type="text/css" href="<?= ROOT_PATH_WEB; ?>css/messages.css">
        <link rel="stylesheet" type="text/css" href="<?= ROOT_PATH_WEB. ADMIN_DIR; ?>css/tools.css">
        <link rel="stylesheet" type="text/css" href="<?= ROOT_PATH_WEB. ADMIN_DIR; ?>css/layout.css">
        <link rel="stylesheet" type="text/css" href="<?= ROOT_PATH_WEB. ADMIN_DIR; ?>css/style.css">
<?php
// include extra CSS and JS files in the skin directory (subdirs are not traversed)
$skin_dir = ROOT_PATH_FILE. ADMIN_DIR. 'skin';
if (is_dir ($skin_dir)) {
    $files = glob ("{$skin_dir}/*.css");
    foreach ($files as $file) {
        $file = substr ($file, strlen (ROOT_PATH_FILE));
?>
        <link rel="stylesheet" type="text/css" href="<?= ROOT_PATH_WEB. $file; ?>">
<?php
    }
    
    $files = glob ("{$skin_dir}/*.js");
    foreach ($files as $file) {
        $file = substr ($file, strlen (ROOT_PATH_FILE));
?>
        <script type="text/javascript" src="<?= ROOT_PATH_WEB. $file; ?>"></script>
<?php
    }
}

$_tmces = array('tinymce.js', 'tiny_mce.js', 'tinymce.min.js');
foreach ($_tmces as $_tmce) {
    if (@is_file(ROOT_PATH_FILE . 'tinymce/' . $_tmce)) {
?>
        <script type="text/javascript" src="<?= ROOT_PATH_WEB; ?>tinymce/<?= $_tmce; ?>"></script>
<?php
        break;
    }
}
unset($_tmces, $_tmce);

$scripts = array (
    ADMIN_DIR. 'functions.js',
    ADMIN_DIR. 'search_functions.js',
    ADMIN_DIR. 'tree.js',
    'tricho/functions.js',
    'tricho/ajax/config.js',
    'tricho/ajax/queue.js',
    'tricho/ajax/base_handlers.js'
);
foreach ($scripts as $script) {
?>
        <script type="text/javascript" src="<?= ROOT_PATH_WEB. $script; ?>"></script>
<?php
}

// _add_edit.js for current table, if it exists
if (strpos ($_SERVER['PHP_SELF'], '_add.php') !== false
        or strpos ($_SERVER['PHP_SELF'], '_edit.php') !== false) {
    $table_js_name = 'add_edit_' . preg_replace('/\.php$/', '.js', class_name_to_file_name($_GET['t']));
    if (file_exists ($table_js_name)) {
        $onload[] = 'init();';
        $js_files[] = $table_js_name;
    }
}

foreach ($js_files as $js_file) {
    if ($js_file[0] != '/') $js_file = ROOT_PATH_WEB. ADMIN_DIR. $js_file;
?>
        <script type="text/javascript" src="<?= $js_file; ?>"></script>
<?php
}

if (@$inline_js != '') {
?>
        <script language="javascript" type="text/javascript"><?= $inline_js; ?></script>
<?php
}
?>
        <script src="//code.jquery.com/jquery-1.11.2.min.js"></script>
    </head>
<body<?php if (@count ($onload) > 0) echo ' onload="', implode (' ', $onload), '"'; ?>>
<div id="header">
<h1>Administration</h1>
    
<?php

if (test_setup_login (false, SETUP_ACCESS_LIMITED)) {
    
    echo "<p class=\"admin_view_options\">\n";
    
    echo "<strong>View options:</strong>\n";
    
    if (test_setup_login (false, SETUP_ACCESS_FULL)) {
        show_setup_user_option ('Show queries', 'q');
        show_setup_user_option ('Show column names', 'c');
    }
    show_setup_user_option ('Show edit help links', 'h');
    
    echo " &nbsp; <a href=\"", ROOT_PATH_WEB, ADMIN_DIR,
        "sql.php\" class=\"setup_quicklink\">SQL &raquo;</a>\n";
    echo " &nbsp; <a href=\"", ROOT_PATH_WEB, ADMIN_DIR,
        "struct.php\" class=\"setup_quicklink\">Struct &raquo;</a>\n";
    echo " &nbsp; <a href=\"", ROOT_PATH_WEB, ADMIN_DIR,
        "info.php\" class=\"setup_quicklink\">Version &raquo;</a>\n";
    
    echo "</p>\n";
}
?>

</div>
<table id="main_container">
<tr>

<td id="area_choices">
<?php
// show tables available for viewing/editing
$tables = $db->getTables ();

// find selected table
if (@$_GET['p'] != '') {
    // get the last table in the parent chain
    $parts = explode (',', $_GET['p']);
    $parts = array_pop ($parts);
    $parts = explode ('.', $parts);
    $selected_table = $db->getTable ($parts[0]);
    
} else {
    // just get the current table
    $selected_table = $db->getTable(@$_GET['t']);
}

echo "    <ul class=\"level1\">\n";
if (file_exists ('home.php')) {
    echo "        <li";
    if (@$_GET['t'] == '__home') echo ' class="on"';
    echo "> <a href=\"", ROOT_PATH_WEB, ADMIN_DIR, "home.php\">Home</a></li>\n";
}

// Specify which tables to group in holders
$ungrouped_tables = array ();
foreach ($tables as $check_table) {
    if ($check_table->getDisplay () and $check_table->checkAuth ()) {
        $ungrouped_tables[] = $check_table;
    }
}
$holders = array (
    // new MenuHolder ('Settings', array ('SettingsTable1', 'SettingsTable2')),
);

// If using holders at all, automatically create two groups:
// "Tricho" (Tricho's inbuilt tables)
// "Other" (remaining tables that haven't been put into any other groups)
if (count($holders) > 0) {
    if (test_setup_login(false, SETUP_ACCESS_LIMITED)) {
        $setup_holder = new MenuHolder(
            'Tricho',
            array('_tricho_users', '_tricho_log', '_tricho_login_failures')
        );
    }
    $other_tables = array ();
    foreach ($ungrouped_tables as $ungrouped_table) {
        $other_tables[] = $ungrouped_table;
    }
    if (count($other_tables) > 0) {
        $holders[] = new MenuHolder('Other', $other_tables);
    }
    if (test_setup_login(false, SETUP_ACCESS_LIMITED)) {
        $holders[] = $setup_holder;
    }
}

// push internal Tricho tables to the end of the list (e.g. logging, IP lockouts, etc.)
$menu_tables = array ();
$menu_tables_internal = array ();
foreach ($ungrouped_tables as $menu_table) {
    if (substr ($menu_table->getName (), 0, 1) == '_') {
        $menu_tables_internal[] = $menu_table;
    } else {
        $menu_tables[] = $menu_table;
    }
}
foreach ($menu_tables_internal as $menu_table) {
    $menu_tables[] = $menu_table;
}
unset ($menu_tables_internal);
unset ($ungrouped_tables);

foreach ($holders as $holder) {
    if ($holder instanceof Table) {
        $holder->menuDraw ($selected_table === $holder);
    } else if ($holder instanceof MenuHolder) {
        $holder->draw ();
    }
}

foreach ($menu_tables as $menu_table) {
    if ($menu_table->getDisplay () and $menu_table->checkAuth ()) {
        $menu_table->menuDraw ($selected_table === $menu_table);
    }
}
if (test_setup_login (false, SETUP_ACCESS_LIMITED)) {
    if (@$_GET['t'] == '__tools') {
        $class = ' class="on"';
    } else {
        $class = '';
    }
    echo "        <li{$class}><a href=\"", ROOT_PATH_WEB, ADMIN_DIR,
        "tools.php\">Tools</a></li>\n";
    if (is_dir ('setup')) {
        echo "        <li><a href=\"", ROOT_PATH_WEB, ADMIN_DIR,
            "setup/table_edit_pre.php?table=", urlencode ($_GET['t']), "&amp;action=Edit\">Setup</a></li>\n";
    }
}
echo "        <li><a href=\"", ROOT_PATH_WEB, ADMIN_DIR, "logout.php\">Log Out</a></li>\n";
echo "    </ul>\n";
?>
</td>
<td>
