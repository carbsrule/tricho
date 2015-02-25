<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../../tricho.php';
require_once ROOT_PATH_FILE. 'tricho/data_objects.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);

$col_to_del = $_GET['id'];
$col_to_del_name = $_GET['name'];

// we do not care about warnings
unset ($_SESSION['setup']['warn']);

// deletes this column (moves columns forward)
foreach ($_SESSION['setup']['create_table']['columns'] as $index => $column) {
    if ($index > $col_to_del) {
        $_SESSION['setup']['create_table']['columns'][$index - 1] = $column;
    }
}
unset ($_SESSION['setup']['create_table']['columns'][$index]);

redirect ('table_create1_done.php');
?>
