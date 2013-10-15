<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';

$page_opts = array ('tab' => 'indexes');
require 'table_head.php';

try {
    $pk_cols = $table->getIndex ('PRIMARY KEY');
} catch (Exception $e) {
    $pk_cols = array ();
}

$columns = $table->getColumns ();

echo "<script language=\"Javascript\" src=\"table_edit_indexes.js\"></script>\n";

echo "<form method=\"post\" name=\"indexes\" action=\"table_edit_pk_action.php\">\n";



// Primary Key index
echo "<h4>Primary key</h4>\n";
foreach ($columns as $id => $col) {
    echo "<label for=\"col_{$id}\" class=\"label_plain\"><input type=\"checkbox\" name=\"fields[]\" id=\"col_{$id}\" value=\"{$col->getName ()}\"";
    if (in_array ($col, $pk_cols, true)) {
        echo ' checked';
    }
    echo '>', $col->getName ();
    echo "</label><br>\n";
}
echo "<input type=\"submit\" value=\"Modify index\">\n";
echo "</form>\n";




// determine and show other (i.e. non-PK) indexes
$res = execq("SHOW INDEXES FROM `". $table->getName (). "`");
$indexes = array ();
while ($row = fetch_assoc($res)) {
    
    // do comparison on PHP side because WHERE clause on SHOW INDEXES is only supported for MySQL 5 --
    // and is not listed in the MySQL manual as supported at all, even though it works.
    if (strcasecmp ($row['Key_name'], 'PRIMARY') == 0) continue;
    
    $index_name = $row['Key_name'];
    
    // if the index is not listed, add it
    if (!isset ($indexes[$index_name])) {
        $index = array ();
        
        // determine index type
        if ($row['Non_unique'] == 0) {
            $index['type'] = 'UNIQUE';
        } else if ($row['Index_type'] == 'FULLTEXT') {
            $index['type'] = 'FULLTEXT';
        } else {
            $index['type'] = 'INDEX';
        }
        
        $indexes[$index_name] = $index;
    }
    
    // add column
    $column = $row['Column_name'];
    if ($row['Sub_part'] != null) {
        $column .= ' (' . $row['Sub_part'] . ')';
    }
    $indexes[$index_name][] = $column;
}


// display indexes
if (count($indexes) > 0) {
    echo "<h4>Existing indexes</h4>\n";
    echo "<p>These are the current indexes:</p>\n";
    echo "<table class=\"table_cols\">\n";
    echo "<tr><th>Name</th><th>Type</th><th>Fields</th><th>Actions</th></tr>\n";
    foreach ($indexes as $name => $details) {
        
        $type = $details['type'];
        unset ($details['type']);
        $fields = implode (', ', $details);
    
        echo "<tr>";
        echo "    <td>{$name}</td>\n";
        echo "    <td>{$type}</td>\n";
        echo "    <td>{$fields}</td>\n";
        echo "    <td><input type=\"button\" onclick=\"window.location='table_del_index.php?i=",
            addslashes ($name), "';\" value=\"Delete\"></td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
}



// new index
echo "<h4>New index</h4>\n";
echo "<form method=\"post\" action=\"table_add_index.php\" name=\"new_index\">\n";

?>
<table>
    <tr>
        <td style="padding: 1em; width: 650px; vertical-align: top;">
            <fieldset>
                <legend>Columns to include</legend>
                <table id="describe_with" style="display: none; width: 100%;"></table>
                <div id="describe_none" style="padding: 0.5em;">None selected yet</div>
            </fieldset>
            
            <table>
                <tr><td>Index name</td><td><input type="text" name="index_name" size="10"></td></tr>
                <tr>
                    <td>Type</td>
                    <td>
                        <select name="type" id="index_type">
                            <option name="index">INDEX</option>
                            <option name="unique">UNIQUE</option>
                            <option name="fulltext">FULLTEXT</option>
                        </select>
                    </td>
                </tr>
                <tr><td colspan="2" align="right"><input type="submit" value="Add index"></td></tr>
            </table>
        </td>
        
        <td style="padding: 1em; vertical-align: top;">
            <table>
            <!-- add column -->
                <tr><td colspan="2"><strong>Add column</strong></td></tr>
                <tr>
                    <td>Column</td>
                    <td>
                        <select name="index_col" id="new_col_name" onchange="update_index_col_options();">
<?php
$cols = $table->getColumns ();
foreach ($columns as $id => $col) {
    
    // determine type - text types are allowed to index a set number of characters at the start of the field,
    // and can also use FULLTEXT indexes.
    switch ($col->getSqlType ()) {
        case SQL_TYPE_CHAR:
        case SQL_TYPE_VARCHAR:
        case SQL_TYPE_TEXT:
        case SQL_TYPE_TINYTEXT:
        case SQL_TYPE_MEDIUMTEXT:
        case SQL_TYPE_LONGTEXT:
        case SQL_TYPE_BLOB:
        case SQL_TYPE_TINYBLOB:
        case SQL_TYPE_MEDIUMBLOB:
        case SQL_TYPE_LONGBLOB:
            $type = 'text';
            break;
        
        default:
            $type = 'other';
    }
    echo "                            <option value=\"{$type}:{$col->getName ()}\">{$col->getName ()}</option>\n";
}
?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>Prefix</td>
                    <td><input type="text" name="prefix" id="new_col_prefix" size="2" maxlength="3"></td>
                </tr>
                <tr>
                    <td colspan="2">
                        <input type="button" value="Add" onclick="add_index_col ();">
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<?php
echo "</form>\n";

echo "<script language=\"javascript\">\n";
echo "update_index_col_options ();\n";
echo "var up_image = '" . ROOT_PATH_WEB . IMAGE_ARROW_UP . "';\n";
echo "var down_image = '" . ROOT_PATH_WEB . IMAGE_ARROW_DOWN . "';\n";
echo "draw_nodes();\n";
echo "</script>\n";

require 'foot.php';
?>
