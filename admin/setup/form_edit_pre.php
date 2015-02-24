<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../../tricho.php';
test_setup_login(true, SETUP_ACCESS_LIMITED);

if (empty($_POST['task']) or empty($_POST['form'])) {
    redirect('./');
}

switch ($_POST['task']) {
case 'Edit':
    redirect('form_edit.php?f=' . urlencode($_POST['form']));
    break;

case 'Delete':
    redirect('form_del.php?f=' . urlencode($_POST['form']));
    break;
}
redirect('./');
