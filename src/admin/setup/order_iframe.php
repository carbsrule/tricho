<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);
require 'order_functions.php';

function show_item ($column) {
    if ($column != null) {
        echo $column->getName (), ' (', $column->getEngName (), ')';
    }
}

function order_iframe ($table, $name) {
    
    list ($in_list, $out_list) = get_in_out_lists ($table, $name);
    
    // echo "In list: <pre>"; print_r ($in_list); echo "</pre><br>\n";
    
    echo "<table>\n";
    $i = 0;
    foreach ($in_list as $id => $in_item) {
        
        if ($name == 'order') {
            $item = $in_item[0];
        } else {
            $item = $in_item;
        }
        
        echo '<tr><td';
        if ($name == 'order') {
            echo " width=\"45\"><a href=\"order_iframe_action.php?list=order&amp;sect=in".
                "&amp;go=rev&amp;id=". $id, "\">";
            switch ($in_item[1]) {
                case 'DESC':
                    echo '<img src="',ROOT_PATH_WEB, IMAGE_ORDER_Z_A,'" border="0">';
                    break;
                case 'ASC':
                    echo '<img src="',ROOT_PATH_WEB, IMAGE_ORDER_A_Z,'" border="0">';
                    break;
                default:
                    echo "??";
            }
            echo '</a></td><td>';
        } else {
            echo '>';
        }
        show_item ($item);
        echo '</td><td>';
        if ($i++ > 0) {
            echo '<a href="order_iframe_action.php?list=', $name, '&amp;sect=in&amp;go=up&amp;id=', $id,
                '"><img src="',ROOT_PATH_WEB, IMAGE_ARROW_UP,'" border="0"></a>';
        } else {
            echo '&nbsp;';
        }
        echo '</td><td><a href="order_iframe_action.php?list=', $name, '&amp;sect=in&amp;go=down&amp;id=', $id,
            '"><img src="',ROOT_PATH_WEB, IMAGE_ARROW_DOWN,'" border="0"></a>', "</td></tr>\n";
    }
    echo "<tr><td colspan=\"3\"><hr></td></tr>\n";
    foreach ($out_list as $id => $item) {
        echo '<tr><td>';
        show_item ($item);
        echo '</td><td colspan="2"><a href="order_iframe_action.php?list=', $name, '&amp;sect=out&amp;go=up&amp;id=', $id, '">',
        '<img src="',ROOT_PATH_WEB, IMAGE_ARROW_UP,'" border="0"></a>';
        echo "</td></tr>\n";
    }
    echo "</table>\n";
}

order_iframe ($_SESSION['setup']['create_table']['table'], $_GET['list']);
?>
