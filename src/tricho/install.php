<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Installer;
use Tricho\Runtime;
use Tricho\Meta\Database;

require 'runtime.php';
Runtime::set('root_path', realpath(__DIR__ . '/..') . '/');
Runtime::set('installer_path', $_SERVER['PHP_SELF']);
Runtime::set('live', false);
Runtime::set('master_salt', 'dummy');
Runtime::set('sql_slow_log', false);
Runtime::set('sql_error_log', false);

require 'constants.php';
require 'functions_base.php';
require 'functions_db.php';
require 'autoload.php';

Database::setDefaultPath('tricho/install/installer.tables.xml');

require 'install/Installer.php';
require 'install/InstallerFormModifier.php';
session_start();

$err = false;
// check data dir is writeable and that tables.xml doesn't already exist
$data_dir = realpath(__DIR__) . '/data/';
$config_dir = realpath(__DIR__) . '/config/';
$files = glob("{$data_dir}/*");
if (count($files) > 0) {
    $err = "<p class=\"confirmation\">Tricho is already installed :)</p>\n";
} else if (!is_writeable($data_dir)) {
    $err = "<p class=\"error\"><strong>Error:</strong> data dir <code>{$data_dir}</code> not writeable.</p>\n";
} else if (!is_writeable($config_dir)) {
    $err = "<p class=\"error\"><strong>Error:</strong> config dir <code>{$config_dir}</code> not writeable.</p>\n";
}

if (php_sapi_name() == 'cli') {
    if ($err) {
        echo strip_tags($err);
        exit(1);
    }
    
    echo "CLI mode not yet implemented\n";
    exit(1);
    
    $arr = [];
    $installer->install($arr);
    exit(0);
}

if ($err) {
    require 'install/head.php';
    echo $err;
    require 'install/foot.php';
    exit(1);
}

$installer = new Installer;

if (count($_POST) > 0) {
    $installer->install($_POST);
    exit(0);
}

require 'install/head.php';
?>

<p>To set up your new site, please fill in the form below.</p>

<p><b>N.B. all fields are required</b></p>

<?php
check_session_response();
echo $installer->renderForm();
?>

<?php
require 'install/foot.php';
