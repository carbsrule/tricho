<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';

/*
The root is determined here in case there's a .htaccess mapping that makes all requests be directed to
a subdirectory. If the browser requests (for example) '/admin' which is transparently redirected to /v1/admin,
then the URL base of redirects will be that of the redirect rather than the initial request,
e.g. "login_form.php" will mean "/v1/admin/login.php" instead of the desired "/admin/login.php"
Therefore, the 'Location' redirects here must be explicitly specified
*/
$root = ROOT_PATH_WEB. dirname (substr (__FILE__, strlen (ROOT_PATH_FILE))). '/';

if (@$_SESSION[ADMIN_KEY]['id'] == '') {
    header ("Location: {$root}login.php");
} else {
    
    // go to the home page that describes what to do
    header ("Location: {$root}home.php");
}

?>
