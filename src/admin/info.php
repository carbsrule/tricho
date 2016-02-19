<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Runtime;
use Tricho\Admin\SessionEditor;
use Tricho\DbConn\ConnManager;
use Tricho\Meta\EmailColumn;

require_once '../tricho.php';

if (@$_GET['view'] == 'session') {
    test_setup_login (true, SETUP_ACCESS_FULL);
} else {
    test_setup_login (true, SETUP_ACCESS_LIMITED);
}

$_GET['t'] = '__tools';
require 'head.php';

echo '<div id="main_data">';

if ($db->getShowSectionHeadings ()) {
    echo "<h2>Info</h2>";
}

$_GET['section'] = 'gen';
require_once 'tools_tabs.php';


if (@$_GET['view'] == 'phpinfo') {
    
    // send phpinfo() to an output handler so we can kill off head, css, etc.
    ob_start ();
    @phpinfo();
    $info = ob_get_contents ();
    ob_end_clean ();
    $css = preg_replace ('/^.*?<style.*?>(.*?)<\/style>.*?$/s', '$1', $info);
    $info = preg_replace ('/^.*?<body>(.*?)<\/body>.*?$/s', '$1', $info);
    
    // remove center alignment
    $info = str_replace('<div class="center">', '<div>', $info);
    
    // output css
    echo "\n\n<style>\n";
    $lines = explode ("\n", $css);
    foreach ($lines as $line) {
        if ($line == '') continue;
        echo "#php-info {$line}\n";
    }
    echo "#php-info h1 {color: #0000BB;}\n</style>\n\n";
    
    echo <<< ENDSCRIPT
<script type="text/javascript">
$(document).ready(function() {
    $('#php-info td.e').each(function() {
        var h = $(this).html();
        if(h != 'disable_functions' && h != 'url_rewriter.tags') return;
        $(this).parent().find('td.v').each(function() {
            $(this).html($(this).html().replace(/,/g, ', '));
        });
    });
});
</script>
ENDSCRIPT;
    
    // output
    echo '<p><a href="info.php">&laquo; back</a></p>';
    echo "\n\n\n<div id=\"php-info\">\n{$info}\n</div>\n\n\n";
    require 'foot.php';
    exit;
}

if (@$_GET['view'] == 'session') {
    echo '<p><a href="info.php">&laquo; back</a></p>';
    echo '<style type="text/css">';
    echo '.session .session .session { margin-left: 30px; padding-left: 4px; padding-bottom: 4px; }';
    echo '.session p { margin-top: 4px; margin-bottom: 4px; }';
    echo '</style>';
    SessionEditor::display($_SESSION);
    require 'foot.php';
    exit;
}

if (@$_GET['view'] == 'server') {
    echo '<p><a href="info.php">&laquo; back</a></p>';
    echo '<pre>';
    print_human ($_SERVER);
    echo '</pre>';
    echo "</div>\n";
    require 'foot.php';
    exit;
}

?>
<script type="text/javascript">
function showSection(section) {
    document.getElementById(section).style.display = 'block';
    document.getElementById(section + '_btn').style.display = 'none';
}
function hideSection(section) {
    document.getElementById(section).style.display = 'none';
    document.getElementById(section + '_btn').style.display = '';
}
</script>
<?php

/* ENVIRONMENT */
echo "<p><strong>Environment</strong>\n";
echo '<br> &nbsp; &nbsp; DNS name: ' . $_SERVER['SERVER_NAME'], "\n";
echo '<br> &nbsp; &nbsp; IP: ' . $_SERVER['SERVER_ADDR'], "\n";
echo '<br> &nbsp; &nbsp; Machine name: ' . php_uname ('n'), "\n";
echo '<br> &nbsp; &nbsp; OS: ' . php_uname ('s r v'), "\n";
echo '<br> &nbsp; &nbsp; Architecture: ' . php_uname ('m'), "\n";
if (!empty($_SERVER['SERVER_SOFTWARE'])) {
    echo '<br> &nbsp; &nbsp; Web server: ', hsc($_SERVER['SERVER_SOFTWARE']); echo "\n";
}
echo "</p>\n";

$err_span = '<span style="color: red; font-weight: bold;">';

/* TRICHO */
echo '<p><strong>Tricho</strong>';
echo '<br> &nbsp; &nbsp; Version: '. TRICHO_VERSION, "\n";
echo '<br> &nbsp; &nbsp; Installed directory: '. ROOT_PATH_FILE, "\n";
echo '<br> &nbsp; &nbsp; Current working directory: '. getcwd (), "\n";
if (substr (getcwd (), 0, strlen (ROOT_PATH_FILE)) != ROOT_PATH_FILE) {
    echo "<br> &nbsp; &nbsp; {$err_span}Warning: Path Errors Detected!</span>\n";
}

$xhtml = (Runtime::get('xhtml'))? 'true': 'false';
$live = (Runtime::get('live'))? 'true': 'false';

// Make invalid email addresses stand out in ugly red
$from_email = SITE_EMAIL;
$email_col = new EmailColumn('Email');
$email_col->setValidationType('basic');
try {
    $email_col->collateInput($from_email, $junk);
} catch (DataValidationException $ex) {
    $from_email = "{$err_span}{$from_email}</span>";
}

$emails_to_check = array (
    'error' => preg_split ('/,\s*/', SITE_EMAILS_ERROR),
    'admin' => preg_split ('/,\s*/', SITE_EMAILS_ADMIN)
);
$resultant_emails = array ();
foreach ($emails_to_check as $key => $emails) {
    foreach ($emails as $email) {
        $result = $email;
        try {
            $email_col->collateInput($email, $junk);
        } catch (DataValidationException $ex) {
            $result = "{$err_span}{$email}</span>";
        }
        $resultant_emails[$key][] = $result;
    }
}

echo "<br> &nbsp; &nbsp; Use XHTML tags: {$xhtml}\n";
echo "<br> &nbsp; &nbsp; Live site: {$live}\n";
echo "<br> &nbsp; &nbsp; Email from: {$from_email}\n";
echo '<br> &nbsp; &nbsp; Email errors to: ', implode (', ', $resultant_emails['error']), "\n";
echo '<br> &nbsp; &nbsp; Email admin messages to: ',
    implode (', ', $resultant_emails['admin']), "\n";
echo "</p>\n";

if (defined ('CMS_VERSION')) {
    
    // TODO: show which CMS product it is (basic, complex) - i.e. whether or not it does page revisions
    echo "<p><strong>CMS</strong>",
        "<br>Version: ". CMS_VERSION.
        "</p>\n";
}


/* PHP */
echo "<p><strong>PHP</strong>\n";
echo '<br> &nbsp; &nbsp; Version: '. phpversion (). ' ('. php_sapi_name (). ")\n";
if (php_sapi_name () == 'apache2handler') {
    echo '<br> &nbsp; &nbsp; '. apache_get_version (), "\n";
}
echo '<br> &nbsp; &nbsp; Timezone: '. @date_default_timezone_get(), "\n";
$php_time = @date('Y-m-d H:i:s');
if ($res = execq('SELECT NOW(), @@session.time_zone')) {
    $row = fetch_row($res);
    list ($mysql_time, $mysql_timezone) = $row;
}
echo '<br> &nbsp; &nbsp; Current Date/Time: '. $php_time;
if ($php_time != $mysql_time) {
    echo '<br> &nbsp; &nbsp; <span style="color: red; font-weight: bold;">',
        "Warning: Date/Time Errors Detected!</span>\n";
}
$extns = get_loaded_extensions ();
echo "<span id=\"extns_btn\"><br> &nbsp; &nbsp; <u class=\"clickable\"",
    " onclick=\"showSection('extns');\">Loaded Extensions</u></span>\n";
echo '<div id="extns" style="display: none;">';
echo ' &nbsp; &nbsp; The following extensions are loaded:';
foreach ($extns as $extn) {
    echo '<br> &nbsp; &nbsp; &nbsp; &nbsp; '. $extn. ' '. phpversion ($extn), "\n";
}
echo '<br> &nbsp; &nbsp; <u class="clickable" onClick="hideSection(\'extns\');">Close</u>';
echo '</div>';

/* MYSQL */
$conn = ConnManager::get_active();
$pdo = $conn->get_pdo();
echo '<p><strong>MySQL</strong>';
echo "<br> &nbsp; &nbsp; Version: ",
    $pdo->getAttribute(PDO::ATTR_SERVER_VERSION), "\n";
echo "<br> &nbsp; &nbsp; Database: {$conn->get_param('db')}\n";
$host = $conn->get_param('host');
if (!$host) $host = 'localhost';
echo "<br> &nbsp; &nbsp; Server: {$host}\n";
echo '<br> &nbsp; &nbsp; Timezone: '. $mysql_timezone, "\n";
echo '<br> &nbsp; &nbsp; Current Date/Time: '. $mysql_time, "\n";
if ($php_time != $mysql_time) {
    echo '<br> &nbsp; &nbsp; <span style="color: red; font-weight: bold;">Warning: Date/Time Errors Detected!</span>';
}

$res = execq('SHOW TABLES');
$num = $res->rowCount();
echo "<br> &nbsp; &nbsp; Number of tables: {$num}";

$num_cols = 0;
while ($row = fetch_row($res)) {
    $q = "SHOW COLUMNS FROM `{$row[0]}`";
    $res2 = execq($q);
    $num_cols += $res2->rowCount();
}

echo "<br> &nbsp; &nbsp; Number of columns: {$num_cols}";
echo "</p>\n";

/* GD */
if (in_array ('gd', $extns)) {
    echo '<p id="gd_btn"><strong><u class="clickable" onclick="showSection(\'gd\');">',
        "GD</u></strong></p>\n";
    $info = gd_info ();
    echo '<div id=\'gd\' style=\'display: none;\'>';
    echo '<strong>GD</strong>';
    echo '<br>&nbsp; &nbsp; Version: ' . $info['GD Version'];
    unset ($info['GD Version']);
    foreach ($info as $key => $val) {
        if ($val == null) continue;
        if ($val == 1) {
            echo '<br> &nbsp; &nbsp; ', $key, "\n";
        } else {
            echo '<br> &nbsp; &nbsp; ', $key, ': ', $val, "\n";
        }
    }
    echo '<br> &nbsp; &nbsp; <u class="clickable" onclick="hideSection(\'gd\');">Close</u>';
    echo '</div>';
}


/* PEAR */
$output = array ();
// unable to get working...

// ini_set ('include_path', ini_get ('include_path'))

$output = array ();
exec ('pear list', $output);

$found_separator = false;
$pear_packages = array ();
foreach ($output as $id => $line) {
    if (!$found_separator) {
        if ($line[0] == '=') {
            $found_separator = true;
        }
        unset ($output[$id]);
    } else {
        list ($package_name, $version, $type) = preg_split ('/\s+/', $line);
        $pear_packages[$package_name] = array (
            'version' => $version,
            'state' => $type
        );
    }
}

@include 'PEAR.php';
@include_once 'PEAR/Dependency2.php';

if (class_exists ('PEAR_Dependency2') or count ($pear_packages) > 0) {
    
    echo "<p><strong>PEAR</strong>\n";
    
    echo "<br>&nbsp; &nbsp; Version: ";
    if (class_exists ('PEAR_Dependency2')) {
        echo @PEAR_Dependency2::getPEARVersion();
    } else {
        echo $pear_packages['PEAR']['version'];
    }
    
    if (count ($pear_packages) > 0) {
        echo "<br>Packages installed:</p>\n";
        echo "<table>";
        foreach ($pear_packages as $package_name => $package) {
            echo "<tr><td>{$package_name}</td><td>{$package['version']}</td><td>{$package['state']}</td></tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "Unable to retrieve package information</p>\n";
    }
}


/* more info */
echo '<p><strong>More Information</strong>';
echo '<br> &nbsp; &nbsp; <a href="info.php?view=phpinfo">Full PHP Details</a>';
if ($_SESSION['setup']['level'] == SETUP_ACCESS_FULL) {
    echo '<br> &nbsp; &nbsp; <a href="info.php?view=session">View Session</a>';
}
echo '<br> &nbsp; &nbsp; <a href="info.php?view=server">View $_SERVER details</a>';
echo '<br> &nbsp; &nbsp; <a href="db_stats.php">Database statistics</a>';
echo '</p>';

echo '</div>';

require_once 'foot.php';

?>
