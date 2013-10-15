<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
require_once ROOT_PATH_FILE. 'tricho/data_objects.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);

?>
<script language="JavaScript">
<!--
function del (col) {
    document.forms.updater.del.value = col;
    document.forms.updater.submit();
    return true;
}

function up (col) {
    document.forms.updater.move.value = col;
    document.forms.updater.dir.value = 'up';
    document.forms.updater.submit();
    return true;
}

function down (col) {
    document.forms.updater.move.value = col;
    document.forms.updater.dir.value = 'down';
    document.forms.updater.submit();
    return true;
}
// -->
</script>
<?php

// echo '<p>Col: ', $_GET['col'], "</p>\n";

$col_index = -1;
$cols = $_SESSION['setup']['create_table']['columns'];
foreach ($cols as $id => $curr_col) {
    if ($curr_col['name'] == $_GET['col']) {
        $col_index = $id;
        break;
    }
}
if ($col_index != -1) {
    $desc_list = $_SESSION['setup']['create_table']['columns'][$col_index]['link']['desc'];
    if (@count($desc_list) > 0) {
        echo "<form name=\"updater\" method=\"post\" action=\"table_link_desc_action.php\">\n",
            "<input type=\"hidden\" name=\"del\"><input type=\"hidden\" name=\"move\">",
            "<input type=\"hidden\" name=\"col\" value=\"", $_GET['col'],
            "\"><input type=\"hidden\" name=\"dir\"></form>";
        echo "<table>\n";
        $i = 0;
        foreach ($desc_list as $id => $desc) {
            echo "<tr><td>", ($id + 1),    "<input type=\"button\" value=\"-\" onClick=\"del ($id);\"></td><td>";
            list ($type, $info) = explode (':', $desc, 2);
            if ($type == 'sep') {
                echo "&lt;$info&gt;";
            } else {
                echo "*$info";
            }
            echo "</td><td>";
            if ($i > 0) {
                echo "<input type=\"button\" value=\"^\" onClick=\"up ($id);\">";
            } else {
                echo '&nbsp;';
            }
            echo "</td><td>";
            if (++$i < count($desc_list)) {
                echo "<input type=\"button\" value=\"v\" onClick=\"down ($id);\">";
            } else {
                echo '&nbsp;';
            }
            echo "</td></tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p>No description given</p>";
    }
    
}

?>
