<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
?>

<html>
<head>
    <title>Test suite: Find select fields</title>
    <style type="text/css">
        .floater {
            min-height: 150px;
            float: left;
            margin-left: 10px;
            margin-top: 10px;
        }
    </style>
</head>
<body>

<?php
function show_handler ($handler) {
    $fields = $handler->getSelectFields ();
    
    echo "<table border=\"1\" style=\"border-collapse: collapse;\">\n";
    foreach ($fields as $id => $field) {
        echo "    <tr>\n";
        echo "        <td>{$id}</td>\n        <td>";
        if ($field instanceof QueryColumn) {
            
            $alias = $field->getAlias ();
            $table = $field->getTable ();
            $table_alias = $table->getAlias ();
            
            echo $table->getName (). '.'. $field->getName ();
            
            echo "</td>\n        <td>";
            
            if ($alias != '' or $table_alias != '') {
                echo ($table_alias != ''? $table_alias: $table->getName ()),
                    '.', ($alias != ''? $alias: $field->getName ());
            } else {
                echo '&nbsp;';
            }
            
        } else if ($field instanceof QueryFunction) {
            
            echo $field->getName ();
            
            echo "</td>\n        <td>";
            
            if ($field->getAlias () != '') {
                echo $field->getAlias ();
            } else {
                echo '&nbsp;';
            }
            
        } else {
            
            echo $field->getName ();
            
            echo "</td>\n        <td>&nbsp;";
            
        }
        echo "        </td>\n";
        echo "    </tr>\n";
    }
    echo "</table>\n";
    
}

$handler = new SelectQuery ();

$real_table = new QueryTable ('Table');
$aliased_table = new QueryTable ('FauxTable');
$aliased_table->setAlias ('Table');

$cols[0] = new QueryColumn ($real_table, 'Column');
$cols[1] = new QueryColumn ($real_table, 'FauxColumn', 'Column');
$cols[2] = new QueryColumn ($aliased_table, 'Column');
$cols[3] = new QueryColumn ($aliased_table, 'FauxColumn', 'Column');
$cols[4] = new QueryFieldLiteral ('Column');
$cols[5] = new QueryFunction ('FUNC', new QueryFieldLiteral ('FauxInput'));
$cols[6] = new QueryFunction ('FUNC', new QueryFieldLiteral ('FauxInput'));
$cols[6]->setAlias ('Column');

foreach ($cols as $col) {
    $handler->addSelectField ($col);
}

/* **** base **** */
echo "<div class=\"floater\">\n";
// pretend that we're doing a join -- that way the table references will always be provided
$handler->setBaseTable ($real_table);
$handler->addJoin (new QueryJoin ($aliased_table, new LogicTree ()));
echo 'Original: ', show_handler ($handler);
echo "</div>\n";

/* **** (1) **** */
echo "<div class=\"floater\">\n";
$mod_handler = clone $handler;
$fields_to_remove = $mod_handler->findSelectFields ('', 'Column', FIND_SELECT_TYPE_ANY);
foreach ($fields_to_remove as $rem_field) $mod_handler->removeSelectField ($rem_field);
echo 'Remove column (no table defn, alias allowed): ';
show_handler ($mod_handler);
echo "</div>\n";

/* **** (2) **** */
echo "<div class=\"floater\">\n";
$mod_handler = clone $handler;
$fields_to_remove = $mod_handler->findSelectFields ('', 'Column', FIND_SELECT_TYPE_ANY, false);
foreach ($fields_to_remove as $rem_field) $mod_handler->removeSelectField ($rem_field);
echo 'Remove column (no table defn, no alias): ';
show_handler ($mod_handler);
echo "</div>\n";

/* **** (3) **** */
echo "<div class=\"floater\">\n";
$mod_handler = clone $handler;
$fields_to_remove = $mod_handler->findSelectFields ('Table', 'Column', FIND_SELECT_TYPE_ANY);
foreach ($fields_to_remove as $rem_field) {
    $mod_handler->removeSelectField ($rem_field);
}
echo 'Remove column (table string, alias allowed): ';
show_handler ($mod_handler);
echo "</div>\n";

/* **** (4) **** */
echo "<div class=\"floater\">\n";
$mod_handler = clone $handler;
$fields_to_remove = $mod_handler->findSelectFields ('Table', 'Column', FIND_SELECT_TYPE_ANY, false);
foreach ($fields_to_remove as $rem_field) {
    $mod_handler->removeSelectField ($rem_field);
}
echo 'Remove column (table string, no alias): ';
show_handler ($mod_handler);
echo "</div>\n";

/* **** (5) **** */
echo "<div class=\"floater\">\n";
$mod_handler = clone $handler;
$fields_to_remove = $mod_handler->findSelectFields ($real_table, 'Column', FIND_SELECT_TYPE_ANY);
foreach ($fields_to_remove as $rem_field) {
    $mod_handler->removeSelectField ($rem_field);
}
echo 'Remove column (table obj, alias allowed): ';
show_handler ($mod_handler);
echo "</div>\n";

/* **** (6) **** */
echo "<div class=\"floater\">\n";
$mod_handler = clone $handler;
$fields_to_remove = $mod_handler->findSelectFields ($real_table, 'Column', FIND_SELECT_TYPE_ANY, false);
foreach ($fields_to_remove as $rem_field) {
    $mod_handler->removeSelectField ($rem_field);
}
echo 'Remove column (table obj, no alias): ';
show_handler ($mod_handler);
echo "</div>\n";

/* **** (7) **** */
echo "<div class=\"floater\">\n";
$mod_handler = clone $handler;
$fields_to_remove = $mod_handler->findSelectFields ($real_table, 'Column', FIND_SELECT_TYPE_COLUMN);
foreach ($fields_to_remove as $rem_field) {
    $mod_handler->removeSelectField ($rem_field);
}
echo 'Remove column (no table defn, alias allowed, ONLY COLUMN): ';
show_handler ($mod_handler);
echo "</div>\n";

/* **** (8) **** */
echo "<div class=\"floater\">\n";
$mod_handler = clone $handler;
$fields_to_remove = $mod_handler->findSelectFields ($real_table, 'Column', FIND_SELECT_TYPE_LITERAL);
foreach ($fields_to_remove as $rem_field) {
    $mod_handler->removeSelectField ($rem_field);
}
echo 'Remove column (no table defn, alias allowed, ONLY LITERAL): ';
show_handler ($mod_handler);
echo "</div>\n";

/* **** (9) **** */
echo "<div class=\"floater\">\n";
$mod_handler = clone $handler;
$fields_to_remove = $mod_handler->findSelectFields ($real_table, 'Column', FIND_SELECT_TYPE_FUNCTION);
foreach ($fields_to_remove as $rem_field) {
    $mod_handler->removeSelectField ($rem_field);
}
echo 'Remove column (no table defn, alias allowed, ONLY FUNCTION): ';
show_handler ($mod_handler);
echo "</div>\n";

/* **** (10) **** */
echo "<div class=\"floater\">\n";
$mod_handler = clone $handler;
$fields_to_remove = $mod_handler->findSelectFields ($real_table, 'Column', FIND_SELECT_TYPE_LITERAL | FIND_SELECT_TYPE_FUNCTION);
foreach ($fields_to_remove as $rem_field) {
    $mod_handler->removeSelectField ($rem_field);
}
echo 'Remove column (no table defn, alias allowed, FUNCTION or LITERAL): ';
show_handler ($mod_handler);
echo "</div>\n";
?>

</body>
</html>
