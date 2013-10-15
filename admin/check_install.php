<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

$output = '';

function out_begin ($title) {
    global $output;
    $output .= "<table>\n";
    $output .= "<tr><th colspan=\"2\">{$title}</th></tr>\n";
}

function out_message ($param, $value) {
    global $output;
    $output .= "<tr><td>{$param}</td><td class=\"msg\">{$value}</td></tr>\n";
}

function out_warning ($param, $value) {
    global $output, $num_warns;
    $output .= "<tr><td>{$param}</td><td class=\"warn\">{$value}</td></tr>\n";
    $num_warns++;
}

function out_error ($param, $value) {
    global $output, $num_errs;
    $output .= "<tr><td>{$param}</td><td class=\"err\">{$value}</td></tr>\n";
    $num_errs++;
}

function out_end () {
    global $output;
    $output .= "</table>\n\n";
}

header ('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>Tricho install checker tool</title>
    
    <style type="text/css">
    body {
        font-family: sans-serif;
        margin: 20px;
    }
    
    p {
        font-size: 12px;
    }
    
    th {
        background-color: #EEEEEE;
        font-size: 12px;
        padding: 2px 8px;
        text-align: left;
    }
    
    td {
        padding: 2px 14px 2px 3px;
        font-size: 12px;
    }
    
    table {
        margin: 10px 0px;
        border: 1px #EEEEEE solid;
        width: 450px;
    }
    
    .msg     { color: #009000; width: 250px; }
    .warn    { color: #E76917; width: 250px; }
    .err     { color: #900000; width: 250px; }
    </style>
</head>
<body>
<h2>Tricho install checker tool</h2>
<p>This tool runs a set of automated tests to ensure the integrity of the tricho install.<p>
<br>


<?php
$admin_dir = __DIR__ . '/';
$root_dir = realpath($admin_dir . '..') . '/';
require_once $root_dir . 'tricho/runtime.php';
tricho\Runtime::set('root_path', $root_dir, true);
require_once $root_dir . 'tricho/functions_base.php';
require_once $root_dir . 'tricho/autoload.php';


/* PHP version, dumb config options */
out_begin ('Server settings');
$php_version = phpversion ();
$min_php_version = '5.1.0';

if (version_compare ($php_version, $min_php_version, '<')) {
    out_error ('PHP version', $php_version);
} else {
    out_message ('PHP version', $php_version);
}

if (ini_get ('register_globals')) {
    out_error ('register_globals', 'enabled');
} else {
    out_message ('register_globals', 'disabled');
}

if (ini_get ('magic_quotes_gpc')) {
    out_error ('magic_quotes_gpc', 'enabled');
} else {
    out_message ('magic_quotes_gpc', 'disabled');
}

if (ini_get ('safe_mode')) {
    out_error ('safe_mode', 'enabled');
} else {
    out_message ('safe_mode', 'disabled');
}

if (ini_get ('open_basedir')) {
    out_error ('open_basedir', ini_get ('open_basedir'));
} else {
    out_message ('open_basedir', 'disabled');
}

if (!preg_match ('/apache/i', $_SERVER['SERVER_SOFTWARE'])) {
    out_warning ('Server', $_SERVER['SERVER_SOFTWARE']);
} else {
    $parts = explode (' ', $_SERVER['SERVER_SOFTWARE']);
    out_message ('Server', $parts[0]);
}
out_end ();



/* Check various directories are writable */
out_begin ('Directories');
$session_path = session_save_path ();
if ($session_path) {
    $pos = strpos ($session_path, ";");
    if ($pos !== FALSE) {
        $session_path = substr ($session_path, $pos + 1);
    }
    
} else {
    $session_path = '/tmp';
}
if (!is_writable ($session_path)) {
    out_error ('Session save path', $session_path);
} else {
    out_message ('Session save path', $session_path);
}

$upload_path = ini_get ('upload_tmp_dir');
if (!$upload_path) {
    $upload_path = '/tmp';
}
if (!is_writeable ($upload_path)) {
    out_error ('Upload temp dir', $upload_path);
} else {
    out_message ('Upload temp dir', $upload_path);
}

if (file_exists($root_dir . '.htaccess')) {
    if (!is_writeable($root_dir . '.htaccess')) {
        out_error ('.htaccess', 'not writeable');
    } else {
        out_message ('.htaccess', 'writeable');
    }
}

$doc = new DOMDocument ();
$result = @$doc->load ($admin_dir. 'tables.xml');

if (! $result) {
    out_error ('tables.xml', 'invalid');
    
} else {
    out_message ('tables.xml', 'valid');
    $column_nodes = $doc->getElementsByTagName ('column');
    
    // Load needed dirs from the tables.xml file
    $dirs_needed = array ();
    foreach ($column_nodes as $node) {
        if ($node->getAttribute ('option') == 'richtext2') {
            if (!in_array ('xstd_files', $dirs_needed)) {
                $dirs_needed[] = 'xstd_files';
                $dirs_needed[] = 'xstd_images';
            }
            
        } else if ($node->getAttribute ('option') == 'file' or $node->getAttribute ('option') == 'image') {
            $param_nodes = $node->getElementsByTagName ('param');
            
            foreach ($param_nodes as $node2) {
                if ($node2->getAttribute ('name') == 'storage_location') {
                    if (!in_array ($node2->getAttribute ('value'), $dirs_needed)) {
                        $dirs_needed[] = $node2->getAttribute ('value');
                    }
                    break;
                }
            }
        }
    }
    
    // Check the dirs are writable
    foreach ($dirs_needed as $dir) {
        if (!is_writeable($root_dir . $dir)) {
            out_error($dir, 'not writeable');
        } else {
            out_message($dir, 'writeable');
        }
    }
}
out_end ();



/* Basic config file existence */
$database_tests = true;
out_begin('Config files');
if (!file_exists($root_dir . 'tricho.php')) {
    out_error('Base', 'not found');
    $database_tests = false;
} else {
    out_message('Base', 'found');
}

if (!file_exists($root_dir . 'tricho/config/detect.php')) {
    out_error('Server detect', 'not found');
    $database_tests = false;
} else {
    out_message('Server detect', 'found');
}
out_end();



/* Database tests */
if ($database_tests) {
    out_begin ('Database');
    
    require_once $root_dir . 'tricho/config/detect.php';
    
    $conn_ok = false;
    try {
        $connection = ConnManager::get_active();
        $conn_ok = true;
    } catch (PDOException $ex) {
        out_error('Connection error', $ex->getMessage());
    }
        
    if ($conn_ok) {
        out_message ('Connects', 'yes');
        $pdo = $connection->get_pdo();
        
        // Get the MySQL version
        $mysql_version = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
        if (!$mysql_version) {
            out_error ("MySQL version", 'query failed');
        } else {
            $min_mysql_version = '5.1';
            
            if (version_compare($mysql_version, $min_mysql_version, '<')) {
                out_error ('MySQL version', $mysql_version);
            } else {
                out_message ('MySQL version', $mysql_version);
            }
        }
        
        // Get a list of available databases
        $res = $pdo->query('SHOW DATABASES');
        if (!$res) {
            out_error ("Available databases", 'query failed');
        } else {
            $db = array ();
            while ($row = $res->fetch(PDO::FETCH_NUM)) {
                if ($row[0] == 'information_schema') continue;
                if ($row[0] == 'test') continue;
                $db[] = $row[0];
            }
            out_message ("Available databases", implode ('<br>', $db));
        }
        
        // Get the name of the database specified in the config file
        $config_id = ConnManager::get_default_config_id();
        $config = ConnManager::get_config($config_id);
        
        $db = false;
        if (isset($config['db'])) $db = $config['db'];
        if (isset($config['dbname'])) $db = $config['dbname'];
        if (!$db) {
            out_error ('Configured database', 'UNKNOWN');
        } else {
            out_message ('Configured database', $db);
        }
    }
    
    out_end ();
}



/* Newsletter requirements */
if (file_exists ($admin_dir. 'newsletters')) {
    out_begin ('Newsletter requirements');
    
    $found = include_once 'Mail.php';
    if (!$found) {
        out_error ('PEAR <i>Mail</i> class', 'not found');
    } else {
        out_message ('PEAR <i>Mail</i> class', 'found');
    }
    
    $found = include_once 'Mail/mime.php';
    if (!$found) {
        out_error ('PEAR <i>Mail Mime</i> class', 'not found');
    } else {
        out_message ('PEAR <i>Mail </i> class', 'found');
    }
    
    out_end ();
}


$main_output = $output;
$output = '';


/* Show an overview of the test results */
out_begin ('Overview');
if ($num_errs > 0) {
    out_error ('Errors reported', $num_errs);
} else {
    out_message ('Errors reported', 0);
}
    
if ($num_warns > 0) {
    out_warning ('Warnings reported', $num_warns);
} else {
    out_message ('Warnings reported', 0);
}
out_end ();


echo $output;
echo $main_output;
?>


</body>
</html>
