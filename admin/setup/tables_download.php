<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../../tricho.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);

header ('Content type: text/plain');
header ("Content-Disposition: attachment; filename=tables.xml");
echo file_get_contents ('../tables.xml');
?>
