<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

// Details when run outside of web environment (e.g. cron scripts)
if (!isset($_SERVER['SERVER_NAME'])) {
    $_SERVER['SERVER_NAME'] = 'live.example.com';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_REFERER'] = '';
    $_SERVER['HTTP_USER_AGENT'] = '';
}

use Tricho\Runtime;
$_data = array(
    'live' => true,
    'site_name' => 'My site',
    'email_check_level' => 'dns',
);
foreach ($_data as $_key => $_val) {
    Runtime::set($_key, $_val, true);
}
unset($_data, $_key, $_val);

ConnManager::add_configs(array(
    'default' => array(
        'class' => 'MysqlConn',
        'db' => 'live_db',
        'user' => 'live_user',
        'pass' => 'live_pass',
        'charset' => 'utf8'
    )
));

// Always use www sub-domain if not accessed via IP address
$_ip_pattern = '/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/';
if (!starts_with($_SERVER['SERVER_NAME'], 'www.')
    and !preg_match($_ip_pattern, $_SERVER['SERVER_NAME']))
{
    $_SERVER['SERVER_NAME'] = 'www.' . $_SERVER['SERVER_NAME'];
}
unset($_ip_pattern);


# SITE DETAILS
// The root path of this site, from a web browser's perspective. Usually /
// but if multiple sites on the same domain, it becomes, say, /site_name/
define('ROOT_PATH_WEB', '/');

// the site will send emails from this address
// (single address)
define('SITE_EMAIL', 'specify@email.address');

// the site will email these addresses when errors occur,
// e.g. when a database query fails
// (comma-separated list)
define('SITE_EMAILS_ERROR', 'specify@email.address');

// the site will email these addresses with admin information,
// e.g. when a new user registration needs to be approved
// (comma-separated list)
define('SITE_EMAILS_ADMIN', 'specify@email.address');

// The port that the force_https function should use when a https connection is needed.
define('HTTPS_PORT', 443);


# SECURITY
// file permissions and owners. these are optional. if not set, no action (chmod, chown, chgrp) is taken
//define('FILE_PERMISSIONS_FILE', 0644);
//define('FILE_PERMISSIONS_DIR', 0755);
