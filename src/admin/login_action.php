<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Meta\Database;

require '../tricho.php';
unset($_SESSION[ADMIN_KEY]['err']);
unset($_SESSION['setup']['err']);

// The temporary install password is to be used until at least one database
// table has been created. If a login fails and there is at least one table
// defined, the system will record a failed login and lockout the user's IP
// if there have been too many failed logins from it.
$access_level = -1;
$res = execq("SHOW TABLES");
if (!$res) {
    $login_num_tables = 0;
    $_SESSION[ADMIN_KEY]['err'] = 'Database error';
    
} else {
    $db = Database::parseXML();
    $login_num_tables = $res->rowCount();
    
    if ($login_num_tables > 0 and ip_locked_out($db, true)) {
        $err = 'Logins from your IP are currently disabled';
        $_SESSION[ADMIN_KEY]['err'] = $err;
        redirect ('login.php');
    }
    
    $user = $_POST['kaudhm'];
    $pass = $_POST['askhd'];
    $access_level = authorise_admin($user, $pass, $login_num_tables);
}

// Process the login
if ($access_level > -1) {
    if ($login_num_tables != 0) clear_failed_logins ();
    
    $_SESSION[ADMIN_KEY]['id'] = $user;
    if ($access_level > 0) {
        $_SESSION['setup']['id'] = $user;
        $_SESSION['setup']['level'] = $access_level;
    } else {
        unset($_SESSION['setup']);
    }
    
    // redirect
    if (isset($_POST['redirect'])) {
        redirect($_POST['redirect']);
    } else {
        redirect('./');
    }
    
}

// Set an error message if the login failed
if (!isset($_SESSION[ADMIN_KEY]['err'])) {
    $_SESSION[ADMIN_KEY]['err'] = 'Incorrect username or password supplied';
}

if ($login_num_tables != 0) {
    $tries = record_failed_login ($_POST['kaudhm']);
    
    // Let the user know if they have just been, or are soon to be, locked out
    if ($tries == 0) {
        $_SESSION[ADMIN_KEY]['err'] .= '<br>Logins from your IP address are now disabled';
    } else if ($tries <= 3) {
        $_SESSION[ADMIN_KEY]['err'] .= "<br>You will be locked out after {$tries} more failed attempt".
            ($tries == 1? '': 's');
    }
}

// determine where to redirect to
$url = 'login.php';
$url_params = array ();
if (@$_POST['redirect'] != '') $url_params['redirect'] = $_POST['redirect'];
if (@$_POST['kaudhm'] != '') $url_params['u'] = $_POST['kaudhm'];

if (count ($url_params) > 0) {
    $url .= '?'. http_build_query ($url_params);
}

redirect ($url);
?>