<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Meta\Database;

require_once '../../tricho.php';
test_setup_login(true, SETUP_ACCESS_LIMITED);

require 'order_functions.php';


function show_item ($column) {
    if ($column != null) {
        echo $column->getName (), ' (', $column->getEngName (), ')';
    }
}

function order_iframe ($table, $name) {
    
    list ($in_list, $out_list) = get_in_out_lists ($table, $name);
    
    $param = 't=' . urlencode($table->getName());
    $out_span = 5;
    
    echo "<table>\n";
    $i = 0;
    foreach ($in_list as $id => $in_item) {
        $item = $in_item[0];
        echo '<tr><td width="45"><a href="table_edit_order_iframe_action.php',
            "?{$param}&amp;list=order&amp;sect=in&amp;go=rev&amp;id=", $id,
            "\">";
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
        show_item ($item);
        echo '</td><td>';
        if ($i++ > 0) {
            echo '<a href="table_edit_order_iframe_action.php?', $param,
                '&amp;list=', $name, '&amp;sect=in&amp;go=up&amp;id=',
                $id, '"><img src="', ROOT_PATH_WEB, IMAGE_ARROW_UP,
                '" border="0"></a>';
        } else {
            echo '&nbsp;';
        }
        echo '</td><td><a href="table_edit_order_iframe_action.php?', $param,
            '&amp;list=', $name, '&amp;sect=in&amp;go=down&amp;id=', $id,
            '"><img src="',ROOT_PATH_WEB, IMAGE_ARROW_DOWN,'" border="0"></a>',
            "</td></tr>\n";
    }
    echo "<tr><td colspan=\"{$out_span}\"><hr></td></tr>\n";
    foreach ($out_list as $id => $in_item) {
        $item = $in_item;
        echo '<tr><td colspan="2">';
        show_item ($item);
        echo '</td><td><a href="table_edit_order_iframe_action.php?', $param,
            '&amp;list=', $name, '&amp;sect=out&amp;go=up&amp;id=', $id,
            '"><img src="', ROOT_PATH_WEB, IMAGE_ARROW_UP, '" border="0"></a>';
        echo "</td><td>&nbsp;</td></tr>\n";
    }
    echo "</table>\n";
}
?>
<html>
<head>
<style>
.confirmation {
    border: 1px solid #00DD00; background-color: #F4FFF4; color: #009000;
    font-weight: bold; padding: 3px 5px 3px 5px; font-size: small;
}
</style>
</head>
<body>

<?php
$db = Database::parseXML();
$table = $db->getTable($_GET['t']);

// TODO: show message: "xml saved"

if (@$_SESSION['setup']['ordernum_changed']) {
    unset($_SESSION['setup']['ordernum_changed']);
    echo '<p class="confirmation">Would you like to <a href="../fix_ordernum.php?table='
        . urlencode ($table->getName ()) . '" target="_top">fix the ordernumber values</a> for this table?</p>';
}

order_iframe ($table, 'order');
?>

</body>
</html>
