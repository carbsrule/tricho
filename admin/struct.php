<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);

$_GET['t'] = '__tools';
require 'head.php';

// do checkboxes
if (isset ($_POST)) {
    $_SESSION[ADMIN_KEY]['struct'] = array ();
    
    if (is_array(@$_POST['tables'])) {
        foreach ($_POST['tables'] as $id => $val) {
            if ($val == 1) $_SESSION[ADMIN_KEY]['struct'][] = $id;
        }
    }
}

// determine version
$res = execq("SELECT VERSION() as v");
$row = fetch_assoc($res);
list ($major_version, $junk) = explode ('.', $row['v']);

// build query
if ($major_version < 5) {
    $show = 'SHOW TABLES';
} else {
    $show = 'SHOW FULL TABLES';
}
if (@$_POST['filter'] != '') {
    $conn = ConnManager::get_active();
    $safe_filter = $conn->quote($_POST['filter']);
    if (substr($safe_filter, -1) == "'") {
        $safe_filter = substr($safe_filter, 0, -1) . "%'";
    }
    $show .= ' LIKE ' . $safe_filter;
}

?>

<div id="main_data">
<?php
$res = execq($show);

// work out which tables the user can't access
$disabled_tables = array ();
$tables = $db->getTables ();
foreach ($tables as $table) {
    if ($table->getAccessLevel () == TABLE_ACCESS_SETUP_FULL and
            $_SESSION['setup']['level'] == SETUP_ACCESS_LIMITED) {
        $disabled_tables[] = $table->getName ();
    }
}

// hide protected tables
$db_rows = array ();
while ($row = fetch_row($res)) {
    if (!in_array ($row[0], $disabled_tables)) $db_rows[] = $row;
}
$num = count ($db_rows);

$tbls = array();
?>
<form method="post" action="struct.php" name='struct'>
<?php
echo "<h2>Database Structure</h2>";

$_GET['section'] = 'db';
require_once 'tools_tabs.php';
?>


<?php if ($num > 0) { ?>
<input type='button' onclick="checkall('struct', 'tables'); return false;" value="Select All">
<input type='button' onclick="uncheckall('struct', 'tables'); return false;" value="Select None">
<?php } ?>

&nbsp;<code>SHOW TABLES </code><input type='text' name='filter' value="<?= @$_POST['filter']; ?>"style="font-family: monospace; font-size: small;">


<table class="export_tables">
<?php
// work out how many rows and cols
$per_col = 8;
$num_cols = ceil ($num / $per_col);
if ($num_cols > 4) {
    $per_col = ceil ($num / 4);
    $num_cols = 4;
}

// work out values of cells
$i = 0;
$cells = array ();
foreach ($db_rows as $db_row) {
    $row = $i % $per_col;
    $col = floor ($i / $per_col);
    $cells[$col][$row] = $db_row;
    $i++;
}
$num_rows = count(@$cells[0]);


// display cells
$j = 0;
for ($row = 0; $row < $num_rows; $row++) {
    echo "<tr>\n";
    for ($col = 0; $col < $num_cols; $col++) {
        echo "<td>";
        if (isset ($cells[$col][$row])) {
            
            echo "<label for=\"tables[{$cells[$col][$row][0]}]\"><input type=\"checkbox\" name=\"tables[{$cells[$col][$row][0]}]\" id=\"tables[{$cells[$col][$row][0]}]\" value=\"1\"";
            
            if (@in_array ($cells[$col][$row][0], $_SESSION[ADMIN_KEY]['struct'])) {
                echo ' checked';
                $tbls[] = $cells[$col][$row][0];
            }
            
            if ($cells[$col][$row][1] == 'VIEW') {
                echo "> {$cells[$col][$row][0]} (view)</label> &nbsp;\n";
            } else {
                echo "> {$cells[$col][$row][0]}</label> &nbsp;\n";
            }
            
        } else {
            echo "&nbsp;";
        }
        echo "</td>\n";
    }
    echo "</tr>\n";
}
?>
</table>


<?php
// extra tables
$extra = array();
if (@is_array ($_SESSION[ADMIN_KEY]['struct'])) {
    foreach ($_SESSION[ADMIN_KEY]['struct'] as $tbl) {
        if (!in_array ($tbl, $tbls)) {
            $extra[] = $tbl;
        }
    }
}

// display extra tables
$num = count ($extra);
if ($num > 0) {
    sort ($extra);
    
    // work out how many rows and cols
    $per_col = 8;
    $num_cols = ceil ($num / $per_col);
    if ($num_cols > 4) {
        $per_col = ceil ($num / 4);
        $num_cols = 4;
    }
    
    // work out values of cells
    $i = 0;
    $cells = array ();
    foreach ($extra as $table_name) {
        $row = $i % $per_col;
        $col = floor ($i / $per_col);
        $cells[$col][$row] = $table_name;
        $i++;
    }
    $num_rows = count($cells[0]);
    
    echo '<p><strong>Previously selected tables:</strong></p>';
    echo '<table class="export_tables">';
    
    // display cells
    $j = 0;
    for ($row = 0; $row < $num_rows; $row++) {
        echo "<tr>\n";
        for ($col = 0; $col < $num_cols; $col++) {
            echo "<td>";
            if (isset ($cells[$col][$row])) {
                echo "<input type=\"checkbox\" name=\"tables[{$cells[$col][$row]}]\" id=\"tables[{$cells[$col][$row]}]\" value=\"1\" checked>";
                echo "<label for=\"tables[{$cells[$col][$row]}]\">{$cells[$col][$row]}</label> &nbsp;\n";
            } else {
                echo "&nbsp;";
            }
            echo "</td>\n";
        }
        echo "</tr>\n";
    }
    echo "</table>";
}
?>


<table>
    <tr valign="top">
        <td>
            Display style
        </td>
        <td>
            <label for="style_vert" class="label_plain">
                <input type="radio" id="style_vert" name="style" value="vert"<?php if (@$_POST['style'] != 'horiz') echo ' checked'; ?>>Vertical
            </label>
            <label for="style_horiz" class="label_plain">
                <input type="radio" id="style_horiz" name="style" value="horiz"<?php if (@$_POST['style'] == 'horiz') echo ' checked'; ?>>Horizontal
            </label>
        </td>
     </tr>
     <tr valign="top">
        <td>
            Include type info
        </td>
        <td>
            <label for="full_y" class="label_plain">
                <input type="radio" id="full_y" name="full_data" value="y"<?php if (@$_POST['full_data'] != 'n') echo ' checked'; ?>>Yes
            </label>
            <label for="full_n" class="label_plain">
                <input type="radio" id="full_n" name="full_data" value="n"<?php if (@$_POST['full_data'] == 'n') echo ' checked'; ?>>No
            </label>
        </td>
    </tr>
    <tr>
        <td colspan="2" align="right">
            <input type="submit" value="View data">
        </td>
    </tr>
 </table>
</form>


<?php
// show details of the selected tables
if (@count($_SESSION[ADMIN_KEY]['struct']) > 0) {
    sort ($_SESSION[ADMIN_KEY]['struct']);
    
    foreach ($_SESSION[ADMIN_KEY]['struct'] as $id => $table) {
        if ($_POST['style'] != 'horiz' or $_POST['full_data'] != 'n') echo "<h3>$table</h3>\n";
        
        $res = execq("SHOW COLUMNS FROM `{$table}`");
        $i = 0;
        if ($_POST['full_data'] != 'n') {
            if ($_POST['style'] == 'horiz') {
                echo "<p>\n";
                while ($row = fetch_assoc($res)) {
                    if ($i++ > 0) echo ', ';
                    
                    if ($row['Key'] == 'PRI') {
                        echo "<span class=\"struct_pk\">{$row['Field']}</span>";
                    } else if ($row['Key'] != '') {
                        echo "<span class=\"struct_index\">{$row['Field']}</span>";
                    } else {
                        echo $row['Field'];
                    }
                    
                    echo strtoupper (" {$row['Type']}");
                    if ($row['Null'] == 'NO') echo ' NOT NULL';
                    if ($row['Default'] !== null) echo ' DEFAULT '. hsc (sql_enclose ($row['Default']));
                    if ($row['Extra'] != '') echo ' ', strtoupper ($row['Extra']);
                }
                echo "</p>\n";
            } else {
                echo "<table>\n";
                while ($row = fetch_assoc($res)) {
                    echo "<tr>\n<td>";
                    if ($row['Key'] == 'PRI') {
                        echo "<span class=\"struct_pk\">{$row['Field']}</span>";
                    } else if ($row['Key'] != '') {
                        echo "<span class=\"struct_index\">{$row['Field']}</span>";
                    } else {
                        echo $row['Field'];
                    }
                    echo "</td>\n";
                    echo "<td>", strtoupper ($row['Type']);
                    if ($row['Null'] == 'NO') echo ' NOT NULL';
                    if ($row['Default'] !== null) echo ' DEFAULT '. hsc (sql_enclose ($row['Default']));
                    if ($row['Extra'] != '') echo ' ', strtoupper ($row['Extra']);
                    echo "</td></tr>\n";
                }
                echo "</table>\n";
            }
        } else {
            echo "<p>\n";
            if ($_POST['style'] == 'horiz') {
                if (@count($_SESSION[ADMIN_KEY]['struct']) == 1) echo "<b>{$table}: </b>";
                while ($row = fetch_assoc($res)) {
                    if ($i++ > 0) echo ', ';
                    
                    if ($row['Key'] == 'PRI') {
                        echo '<span class="struct_pk">';
                    } else if ($row['Key'] != '') {
                        echo '<span class="struct_index">';
                    }
                    
                    if (@count($_SESSION[ADMIN_KEY]['struct']) > 1) {
                        echo $table, '.';
                    }
                    echo $row['Field'];
                    if ($row['Key'] == 'PRI' or $row['Key'] != '') echo '</span>';
                }
            } else {
                while ($row = fetch_assoc($res)) {
                    if ($i++ > 0) echo '<br>';
                    if ($row['Key'] == 'PRI') {
                        echo "<span class=\"struct_pk\">{$row['Field']}</span>";
                    } else if ($row['Key'] != '') {
                        echo "<span class=\"struct_index\">{$row['Field']}</span>";
                    } else {
                        echo $row['Field'];
                    }
                    echo "\n";
                }
            }
            echo "</p>\n";
        }
        
    }
}
?>
</div>
<?php
require "foot.php";
?>
