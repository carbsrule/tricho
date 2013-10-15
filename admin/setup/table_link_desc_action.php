<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
require_once ROOT_PATH_FILE. 'tricho/data_objects.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);

// echo "POST: <pre>"; print_r ($_POST); echo "</pre>\n";

$col_index = -1;
$cols = $_SESSION['setup']['create_table']['columns'];
foreach ($cols as $id => $curr_col) {
    if ($curr_col['name'] == $_POST['col']) {
        $col_index = $id;
        break;
    }
}

if ($col_index != -1) {
    if ($_POST['add'] != '') {
        $size = @count($_SESSION['setup']['create_table']['columns'][$col_index]['link']['desc']);
        $_SESSION['setup']['create_table']['columns'][$col_index]['link']['desc'][$size] = $_POST['add'];
    } else if ($_POST['del'] != '') {
        unset ($_SESSION['setup']['create_table']['columns'][$col_index]['link']['desc'][$_POST['del']]);
        $i = $_POST['del'] + 1;
        // shift all lower elements on to the end of this array
        while (isset ($_SESSION['setup']['create_table']['columns'][$col_index]['link']['desc'][$i])) {
            $_SESSION['setup']['create_table']['columns'][$col_index]['link']['desc'][$i - 1] =
                $_SESSION['setup']['create_table']['columns'][$col_index]['link']['desc'][$i];
            unset ($_SESSION['setup']['create_table']['columns'][$col_index]['link']['desc'][$i]);
            $i++;
        }
    } else if ($_POST['move'] != '') {
        $desc =& $_SESSION['setup']['create_table']['columns'][$col_index]['link']['desc'];
        if ($_POST['dir'] == 'up') {
            if (isset ($desc[$_POST['move'] - 1])) {
                $temp = $desc[$_POST['move']];
                $desc[$_POST['move']] = $desc[$_POST['move'] - 1];
                $desc[$_POST['move'] - 1] = $temp;
            }
        } else if ($_POST['dir'] == 'down') {
            if (isset ($desc[$_POST['move'] + 1])) {
                $temp = $desc[$_POST['move']];
                $desc[$_POST['move']] = $desc[$_POST['move'] + 1];
                $desc[$_POST['move'] + 1] = $temp;
            }
        }
    }
}

redirect('table_link_desc.php?col=' . $_POST['col']);
?>
