<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

if (@$_GET['t'] == '') {
    echo '<p class="error">Invalid table specified.</p>';
    require 'foot.php';
    exit;
}

$db = Database::parseXML('../tables.xml');
$table = $db->getTable($_GET['t']);

if ($table == null) redirect ('./');
$tables = $db->getTables ();

echo "<h2>Edit table {$_GET['t']} ({$table->getEngName()})</h2>";

// Get total columns
$columns = $table->getColumns ();
$total_columns = count ($columns);

// choices list thingo
$choices = array(
    new EditTableTab('detail',    'table_edit.php',             'Table details'),
    new EditTableTab('cols',      'table_edit_cols.php',        'Columns ('. $total_columns. ')'),
    new EditTableTab('indexes',   'table_edit_indexes.php',     'Indexes'),
    new EditTableTab('ident',     'table_edit_identifier.php',  'Row identifier'),
    new EditTableTab('list_view', 'table_edit_main_view.php',   'List'),
    new EditTableTab('row',       'table_edit_row_view.php',    'Add/edit'),
    new EditTableTab('csv',       'table_edit_csv.php',         'Export'),
    new EditTableTab('pages',     'table_edit_alt_pages.php',   'Pages used'),
    new EditTableTab('buttons',   'table_edit_alt_buttons.php', 'Button/alert text'),
);

// draw choices list thingo
echo '<div id="area_choices"><ul>';
foreach ($choices as $choice) {
    $choice->draw();
}
echo '</ul></div>';


// row identifer, order and main view check
$warnings = array();

$pk_fields = $table->getIndex ('PRIMARY KEY');
foreach ($pk_fields as $pk_field) {
    $pk_sql_type = $pk_field->getSqlType ();
    if ($pk_sql_type == SQL_TYPE_FLOAT or $pk_sql_type == SQL_TYPE_DOUBLE) {
        $warnings[] = 'This table is using a FLOAT or DOUBLE foating-point type '.
            'for one of its primary key columns. This is very likely to cause problems. Try using a fixed-point '.
            'type such as DECIMAL instead.';
        break;
    }
}

// check the ordernum column is in the table order, and is the last col
//$columns = $table->getColumns ();
foreach ($columns as $column) {
    if ($column->getOption() == 'ordernum') {
        // check if the column is in the order
        $order = $table->getOrder ('view');
        $orderitem = array_pop ($order);
        if ($orderitem[0] !== $column) {
            $warnings[] = "The order number column <i>{$column->getName()}</i> is not in the table order, or is not the last column in the table order";
        }
        break;
    }
}

if (count ($table->getRowIdentifier ()) == 0) {
    $warnings[] = "This table does not have a row identifier";
}
if (count ($table->getOrder('view')) == 0) {
    $warnings[] = "This table does not have any order columns";
}
if (count ($table->getView('list')) == 0) {
    $warnings[] = "This table does not have any main view columns or functions";
}
if (count ($warnings) == 1) {
    echo "<p class=\"warning\">WARNING: {$warnings[0]}</p>";
} else if (count($warnings) > 1) {
    echo "<div class=\"warning\">WARNINGS: <ul>\n<li>" . implode ("</li>\n<li>", $warnings),
        "</li></ul></div>\n";
}

check_session_response ('setup');
echo "<h3>{$page_opts['cn']}</h3>";
unset ($page_opts);

/**
 * Choice class, used for choices list
 */
class EditTableTab {
    private $name;
    private $url;
    private $title;
    
    function __construct($name,$url,$title) {
        $this->name = $name;
        $this->url = $url;
        $this->title = $title;
    }
    
    function draw() {
        global $page_opts;
        $url = $this->url;
        if (strpos($url, '?') !== false) {
            $url .= '&amp;';
        } else {
            $url .= '?';
        }
        $url .= 't=' . urlencode($_GET['t']);
        if ($page_opts['tab'] == $this->name) {
            echo "<li class=\"on\"><a href=\"{$url}\">{$this->title}</a></li>\n";
            if (preg_match ('/Columns/', $this->title) > 0) {
                $page_opts['cn'] = 'Columns';
            } else {
                $page_opts['cn'] = $this->title;
            }
        } else {
            echo "<li><a href=\"{$url}\">{$this->title}</a></li>\n";
        }
    }
    
    public function __toString () {
        return __CLASS__;
    }
}
?>
