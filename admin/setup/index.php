<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../../tricho.php';

if (test_setup_login(false, SETUP_ACCESS_LIMITED) === false) {
    redirect(ROOT_PATH_WEB . ADMIN_DIR . 'login.php');
}

require 'head.php';

check_session_response ('setup');
?>

<p>Please select an option above</p>

<?php
require 'foot.php';
?>
