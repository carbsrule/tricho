<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../tricho.php';

unset ($_SESSION[ADMIN_KEY]);
if (ADMIN_KEY == 'admin') {
    unset ($_SESSION['setup']);
}
redirect ('./');

?>
