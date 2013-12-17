<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../../tricho.php';
require_once 'setup_functions.php';

// Force autoloading of all Column classes
//TODO: load user-defined classes as well
$class_files = glob(ROOT_PATH_FILE . 'tricho/meta_xml/*_column.php');
foreach ($class_files as $file) {
    $class = file_name_to_class_name (basename ($file));
    $reflection = new ReflectionClass ($class);
}
unset($class_files, $file, $class);

$date_formatting_options = array (
    'Date and time' => array (
        "%h:%i%p, %d/%m/%Y" => 'Australian long time, long date',
        "%h:%i%p, %d/%m/%y" => 'Australian long time, short date',
        "%l:%i%p, %d/%m/%Y" => 'Australian short time, long date',
        "%l:%i%p, %d/%m/%y" => 'Australian short time, short date',
        
        "%d/%m/%Y, %h:%i%p" => 'Australian long date, long time',
        "%d/%m/%Y, %l:%i%p" => 'Australian long date, short time',
        "%d/%m/%y, %h:%i%p" => 'Australian short date, long time',
        "%d/%m/%y, %l:%i%p" => 'Australian short date, short time',
        
        "%Y-%m-%d %T" => 'ISO 8601 date + time'
    ),
    
    'Date' => array (
        "%d/%m/%Y" => 'Long Australian date',
        "%d/%m/%y" => 'Short Australian date',
        "%Y-%m-%d" => 'ISO 8601 date'
    ),
    
    'Time' => array (
        "%h:%i%p" => 'Australian long time',
        "%l:%i%p" => 'Australian short time',
        "%H:%i" => '24 hour long time'
    )
);


/**
 * Prints a form with the options used to create a Column definition.
 * 
 * @param mixed $context The table that the new Column should belong to, or the Column to edit
 * @param string $action Whether to 'add' or 'edit' a column
 * @param string $form_action_url Form action URL (e.g. create_column_action.php)
 * @param array $config Configuration options
 * @return void
 * @author alex 2010-01-29, benno 2010-11-11
 */
function column_def_form ($context, $action, $form_action_url, array $config, array $hidden_fields = array ()) {
    global $db;
    
    $extant_column = null;
    if ($context instanceof Table) {
        $table = $context;
    } else if ($context instanceof Column) {
        $extant_column = $context;
        $table = $extant_column->getTable ();
    } else {
        throw new Exception ('Invalid context, first parameter must be a Table or Column');
    }
    
    // fake constants
    global $enforceable_data_types;
    global $recognised_SQL_types;
    global $image_cache_scales;
    global $date_formatting_options;
    $col_classes = tricho\Runtime::get_column_classes();
    
    $image_icon_options = array (
        MAIN_PIC_NONE => 'None',
        MAIN_PIC_LEFT => 'Left of text',
        MAIN_PIC_RIGHT => 'Right of text',
        MAIN_PIC_ONLY_IMAGE => 'Only show image'
    );
    
    // ensure the in_array calls for sql attributes don't fail
    if ($config['sql_attribs'] == null) {
        $config['sql_attribs'] = array ();
    }
    
    if (!isset ($config['name'])) {
        $config['sql_attribs'][] = 'NOT NULL';
    }
    
    $init_fields = array('insert_after', 'name', 'engname', 'sql_size',
        'sql_default', 'class', 'mandatory', 'list_view', 'add_view',
        'edit_view', 'edit_view_show', 'edit_view_edit', 'export_view',
        'comments');
    foreach ($init_fields as $field) {
        if (!isset($config[$field])) $config[$field] = '';
    }
    
    
    /* open form */
    echo "<form method=\"post\" action=\"{$form_action_url}\" name=\"coldata\">\n";
    
    
    if ($action == 'add') {
        $insert = 'Insert';
    } else {
        $insert = 'Reposition';
    }
    
    $columns = $table->getColumns ();
    if (count ($columns) > 0) {
        echo "<table>\n";
        echo "    <tr>\n";
        echo "        <td>{$insert} after</td>\n";
        echo "        <td>\n";
        echo "            <select name=\"insert_after\">\n";
        if ($action == 'edit') {
            echo "                <option value=\"retain\"";
            if ($config['insert_after'] == 'retain') echo ' selected="selected"';
            echo ">- Retain current position -</option>\n";
        }
        echo "                <option value=\"-1\"";
        if ($config['insert_after'] == '-1') echo ' selected="selected"';
        echo ">- {$insert} at beginning -</option>\n";
        foreach ($columns as $index => $column) {
            echo "                <option value=\"{$index}\"";
            if ((string) $index == $config['insert_after']) echo ' selected="selected"';
            if ($column === $extant_column) echo ' disabled="disabled"';
            echo ">{$column->getName ()}</option>\n";
        }
        
        echo "                <option value=\"\"";
        if ($config['insert_after'] == '') echo ' selected="selected"';
        echo ">- {$insert} at end -</option>\n";
        echo "            </select>\n";
        echo "        </td>\n";
        echo "    </tr>\n";
        echo "</table>\n";
    }
    
    echo "<table>\n";
    echo "    <tr>\n";
    echo "        <th>Column name</th>\n";
    echo "        <th>English name</th>\n";
    echo "        <th>Class</th>\n";
    echo "    </tr>\n";
    echo "    <tr>\n";
    echo "        <td><input type=\"text\" name=\"name\" value=\"{$config['name']}\"";
    if ($action == 'add') echo ' onchange="set_english_name ();"';
    echo "></td>\n";
    echo "        <td><input type=\"text\" name=\"engname\" value=\"{$config['engname']}\"></td>\n";
    echo "        <td>\n";
    echo "            <select name=\"class\" onchange=\"set_col_class (this.value);\">\n";
    echo "                <option value=\"\">- Select below -</option>\n";
    
    foreach ($col_classes as $class_name) {
        echo "                <option value=\"{$class_name}\"";
        if ($class_name === $config['class']) echo ' selected="selected"';
        echo ">{$class_name}</option>\n";
    }
    
    echo "            </select>\n";
    echo "        </td>\n";
    echo "    </tr>\n";
    echo "</table>\n";
    
    // SQL data type and size
    echo "<table>\n";
    echo "    <tr id=\"sql_defn\">\n";
    echo "        <td>SQL definition <a class=\"help_popup\" href=\"http://dev.mysql.com/doc/refman/5.0/en/data-types.html\" target=\"_blank\">[?]</a></td>\n";
    echo "        <td>\n";
    echo "            <select name=\"sqltype\" onchange=\"sql_type_options ();\" id=\"sql_type\">\n";
    echo "                <option value=\"\">- Select below -</option>\n";
    
    if ($config['class'] != '') {
        $sql_types = $config['class']::getAllowedSqlTypes ();
        $desired_type = $config['sqltype'];
        if (!in_array ($desired_type, $sql_types)) {
            $desired_type = $config['class']::getDefaultSqlType ();
        }
        foreach ($sql_types as $type) {
            echo "                    <option value=\"{$type}\"";
            if ($type == $desired_type) echo ' selected="selected"';
            echo ">{$type}</option>\n";
        }
    }
    echo "            </select>\n";
    if (mysql_version_at_least (5, 0, 3)) {
        $max = '65535';
        $size = '5';
    } else {
        $max = '255';
        $size = '3';
    }
    echo "<input type=\"text\" name=\"sql_size\" size=\"{$size}\" maxlength=\"{$size}\" value=\"{$config['sql_size']}\">";
    
    // SQL attribute: unsigned
    echo "            <label for=\"attrib_unsigned\"><input id=\"attrib_unsigned\" type=\"checkbox\" name=\"sql_attribs[]\" value=\"UNSIGNED\"";
    if (in_array ('UNSIGNED', $config['sql_attribs'])) echo ' checked';
    echo ">Unsigned</label>";
    
    // SQL attribute: auto increment
    echo "            <label for=\"attrib_auto_inc\"><input id=\"attrib_auto_inc\" type=\"checkbox\" name=\"sql_attribs[]\" value=\"AUTO_INCREMENT\"";
    if (in_array ('AUTO_INCREMENT', $config['sql_attribs'])) echo ' checked';
    echo " onclick=\"on_autoinc_click();\">Auto increment</label>";
    
    if (@$config['collation'] == '') {
        if ($context instanceof Column) {
            $q = "SHOW FULL COLUMNS
                FROM `{$context->getTable()->getName()}`
                WHERE Field LIKE " . sql_enclose($context->getName());
            $res = execq($q);
            if ($row = fetch_assoc($res) and $row['Collation'] != '') {
                $config['collation'] = $row['Collation'];
            }
        }
    }
    echo " <select name=\"collation\">\n",
        "<option value=\"\">- Specific collation -</option>\n";
    foreach (get_available_collation_mappings() as $charset => $collations) {
        echo '<optgroup label="', hsc($charset), "\">\n";
        foreach ($collations as $collation) {
            if ($collation == $config['collation']) {
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }
            echo '<option value="', hsc($collation), "\"{$selected}>",
                hsc($collation), "</option>\n";
        }
        echo "</optgroup>\n";
    }
    echo "</select>\n";
    
    echo "        </td>\n";
    echo "    </tr>\n";
    
    echo "    <tr id=\"sql_defn_opts\">\n";
    echo "    <td>&nbsp;</td>\n";
    echo "    <td>\n";
    
    // SQL attribute: not null
    echo "            <label for=\"attrib_not_null\"><input id=\"attrib_not_null\" type=\"checkbox\" name=\"sql_attribs[]\" value=\"NOT NULL\"";
    if (in_array ('NOT NULL', $config['sql_attribs'])) echo ' checked';
    echo ">Not null</label>";
    
    // SQL attribute: default
    echo "            <label for=\"attrib_default\"><input id=\"attrib_default\" type=\"checkbox\" name=\"set_default\" value=\"1\"";
    if (isset ($config['sql_default']) and $config['sql_default'] !== null) echo ' checked';
    echo ">Default</label>\n";
    echo "            <input type=\"text\" name=\"sql_default\" id=\"attrib_default_value\" value=\"",
        hsc ($config['sql_default']), "\" onkeypress=\"default_keypress ();\">\n";
    echo "\n";
    
    echo "            <script type=\"text/javascript\">\n";
    echo "                on_autoinc_click();\n";
    echo "            </script>\n";
    echo "        </td>\n";
    echo "    </tr>\n";
    
    // Mandatory
    echo "    <tr>\n";
    echo "        <td>Importance</td>\n";
    echo "        <td><label for=\"mandatory\"><input type=\"checkbox\" id=\"mandatory\" name=\"mandatory\" value=\"1\"";
    if ($config['mandatory'] == 1) echo ' checked';
    echo " onclick=\"tick_not_null();\">Mandatory</label></td>\n";
    echo "    </tr>\n";
    
    echo "</table>\n";
    
    echo "<h4>Options</h4>\n";
    echo "<div id=\"options\">\n";
    
    $css_class = 'display-none';
    if ($config['class'] == '') $css_class = '';
    echo "<div id=\"options-\" class=\"$css_class\">\n";
    echo "<p>Please select a class</p>";
    echo "</div>\n";
    
    foreach ($col_classes as $class) {
        $css_class = 'display-none';
        if ($config['class'] == $class) $css_class = '';
        echo "<div id=\"options-{$class}\" class=\"{$css_class}\">\n";
        $config_fields = '';
        $config_fields .= $class::getConfigFormFields ($config, $class);
        if ($config_fields != '') {
            echo $config_fields;
        } else {
            echo "<p>No configuration options for this class</p>\n";
        }
        echo "</div>\n";
    }
    echo "</div>\n";
    
    
    // Views
    echo "<h4>Views</h4>\n";
    echo "<table>\n";
    echo "    <tr>\n";
    echo "        <td>Main</td>\n";
    echo "        <td>\n";
    echo "            <label for=\"list_view\"><input type=\"checkbox\" id=\"list_view\" name=\"list_view\"";
    if ($config['list_view']) echo ' checked="checked"';
    echo " value=\"1\">Visible</label>\n";
    echo "        </td>\n";
    echo "    </tr>\n";
    
    echo "    <tr>\n";
    echo "        <td>Add</td>\n";
    echo "        <td colspan=\"2\">\n";
    echo "            <label for=\"add_view\"><input type=\"checkbox\" id=\"add_view\" name=\"add_view\"";
    if ($config['add_view']) echo ' checked="checked"';
    echo " value=\"1\">Visible/Editable</label>\n";
    echo "        </td>\n";
    echo "    </tr>\n";
    
    echo "    <tr>\n";
    echo "        <td>Edit</td>\n";
    echo "        <td>\n";
    echo "            <label for=\"edit_view_show\"><input type=\"checkbox\" id=\"edit_view_show\" onclick=\"update_visible_status();\" name=\"edit_view_show\"";
    if ($config['edit_view_show']) echo ' checked="checked"';
    echo " value=\"1\">Visible</label>\n";
    echo "        </td>\n";
    echo "        <td>\n";
    echo "            <label for=\"edit_view_edit\"><input type=\"checkbox\" id=\"edit_view_edit\" onclick=\"update_editable_status();\" name=\"edit_view_edit\"";
    if ($config['edit_view_edit']) echo ' checked="checked"';
    echo " value=\"1\">Editable</label>\n";
    echo "        </td>\n";
    echo "    </tr>\n";
    
    echo "    <tr>\n";
    echo "        <td>Export</td>\n";
    echo "        <td>\n";
    echo "            <label for=\"export_view\"><input type=\"checkbox\" id=\"export_view\" name=\"export_view\"";
    if ($config['export_view']) echo ' checked="checked"';
    echo " value=\"1\">Export</label>\n";
    echo "        </td>\n";
    echo "    </tr>\n";
    echo "</table>\n";
    
    echo "<h4>Comments</h4>\n";
    echo "<table>\n";
    echo "    <tr>\n";
    echo "        <td>\n";
    echo "            <textarea name=\"comments\" rows=\"5\" cols=\"50\">",
        hsc (rem_br ($config['comments'])), "</textarea>\n";
    echo "        </td>\n";
    echo "    </tr>\n";
    
    echo "    <tr>\n";
    echo "        <td align=\"right\">\n";
    echo "            <input type=\"hidden\" name=\"action\" value=\"save\">\n";
    echo "            <input type=\"button\" value=\"Cancel\" onclick=\"this.form.elements['action'].value = 'cancel'; this.form.submit();\">\n";
    echo "            <input type=\"submit\" value=\"Continue &gt;&gt;\">\n";
    echo "        </td>\n";
    echo "    </tr>\n";
    echo "</table>\n";
    foreach ($hidden_fields as $field => $value) {
        echo '<input type="hidden" name="', hsc ($field), '" value="', hsc ($value), "\">\n";
    }
    echo "</form>\n";

}


/**
 * Creates or updates a Column object and sets all its parameters from config
 * options.
 * N.B. any options that relate directly to the SQL definition should be handled
 * by this function, but config options that are specific to the column class
 * should be handled by the applyConfig method of that class - which is called
 * by this function after the SQL config options have been dealt with.
 * 
 * @param Table $table The Table to which the column will belong
 * @param string $action 'add' or 'edit'
 * @param string $form_url The form URL to redirect to in case of validation failure
 * @param array $config Config options (e.g. $_POST data)
 * @return Column the column created/updated
 * @author alex 2010-01-29
 * @author benno 2010-11-11, 2011-08-04
 */
function column_config_to_meta (Table $table, $action, $form_url, array $config) {
    $errors = array();
    if ($config['class'] == '') {
        $errors[] = 'You must specify a class';
    }
    $col = null;
    $config['name'] = trim($config['name']);
    if ($config['name'] and @$config['class']) {
        $col = new $config['class'] ($config['name']);
        if (!($col instanceof Column)) {
            $err = $config['class'] . ' is not a Column';
            throw new InvalidParameterException($err);
        }
        $col->setTable($table);
    }
    
    $sized_types = array(SQL_TYPE_VARCHAR, SQL_TYPE_CHAR, SQL_TYPE_DECIMAL,
        SQL_TYPE_BINARY, SQL_TYPE_VARBINARY);
    
    // get sql definition from params
    $sql_type = 0;
    if ($config['sqltype'] == '') {
        $errors[] = "You must specify an SQL type";
    } else if ($config['class'] != 'LinkColumn') {
        $sql_type = sql_type_string_to_defined_constant($config['sqltype']);
        if ($sql_type == 0) {
            $errors[] = "Unknown SQL type: {$config['sqltype']}";
        }
        if (in_array($sql_type, $sized_types) and $config['sql_size'] == '') {
            $errors[] = $config['sqltype'] . ' columns need a defined SQL size';
        }
    }
    
    if ($config['sql_size'] != '') {
        
        $max = '255';
        if (mysql_version_at_least (5, 0, 3)) $max = '65535';
        
        if ((int) $config['sql_size'] > $max) {
            $errors[] = "Specified sql size is too big. Maximum sql size is {$max}";
        }
        
        if ((int) $config['sql_size'] < 1) {
            $errors[] = "Specified sql size is too small. Minimum sql size is 1";
        }
    }
    
    // handle sql attributes
    $sql_attributes = array ();
    $known_attributes = array ('UNSIGNED', 'AUTO_INCREMENT', 'NOT NULL');
    if (!@is_array ($config['sql_attribs'])) $config['sql_attribs'] = array ();
    foreach ($config['sql_attribs'] as $attrib) {
        if (in_array ($attrib, $known_attributes)) $sql_attributes[] = $attrib;
    }
    
    // auto-increment
    if (in_array ('AUTO_INCREMENT', $sql_attributes)) {
        if (!in_array ($sql_type, array (SQL_TYPE_INT, SQL_TYPE_TINYINT, SQL_TYPE_SMALLINT, SQL_TYPE_MEDIUMINT, SQL_TYPE_BIGINT))) {
            $errors[] = "AUTO_INCREMENT can only be used for integer type fields";
        }
        $auto_increment = true;
    }
    
    // unsigned
    if (in_array ('UNSIGNED', $sql_attributes)) {
        if (!in_array ($sql_type, array (SQL_TYPE_INT, SQL_TYPE_TINYINT, SQL_TYPE_SMALLINT, SQL_TYPE_MEDIUMINT, SQL_TYPE_BIGINT, SQL_TYPE_DECIMAL, SQL_TYPE_FLOAT, SQL_TYPE_DOUBLE))) {
            $errors[] = "UNSIGNED can only be used for number type fields";
        }
    }
    
    
    // check sql name
    if ($config['name'] == '' or !table_name_valid($config['name'])) {
        $errors[] = 'You must specify a valid SQL column name';
        
    // check for duplicate name
    } else {
        if ($action != 'edit' or $config['name'] != @$config['old_name']) {
            foreach ($table->getColumns() as $other_col) {
                if ($other_col->getName() == $config['name']) {
                    $errors[] = 'A column with that name already exists';
                    break;
                }
            }
        }
    }
    
    // check english name
    if ($config['engname'] == '') {
        $errors[] = 'You must specify an English name';
    }
    
    if ($col) {
        if (!($col instanceof LinkColumn) and $sql_type > 0) {
            $col->setSqlType($sql_type);
        }
        $col->applyConfig($config, $errors);
        
        if ($col instanceof LinkColumn) {
            $target = $col->getTarget();
            $sql_type = $target->getSqlType();
            $col->setSqlType($sql_type);
            $config['sql_size'] = $target->getSqlSize();
            $sql_attributes = $target->getSqlAttributes();
            $key = array_search('AUTO_INCREMENT', $sql_attributes);
            if ($key !== false) {
                unset($sql_attributes[$key]);
                $sql_attributes = array_merge($sql_attributes);
            }
            
            // TODO: Allow setting default value
            $config['set_default'] = false;
        }
    }
    
    // check date range is valid
    if (($config['sqltype'] == SQL_TYPE_DATE) || ($config['sqltype'] == SQL_TYPE_DATETIME)) {
        
        $config['year_max'] = trim ($config['year_max']);
        $config['year_min'] = trim ($config['year_min']);
        
        if ($config['year_max'] == '' or $config['year_min'] == '') {
            $errors[] = 'You must specify a year range for date columns';
        } else if (preg_match ('/^[-+]?\d+$/', $config['year_max']) == 1
                             and preg_match ('/^\d+$/',    $config['year_min']) == 1) {
            
            $year_max = (int) $config['year_max'];
            $year_min = (int) $config['year_min'];
            
            if ($config['year_max']{0} == '-' or $config['year_max']{0} == '+') {
                $year_max += (int) date ('Y');
            }
            
            if ($year_min > $year_max) {
                $errors[] = 'Invalid date range specified';
            }
        } else {
            $errors[] = 'Invalid date range specified';
        }
    }
    
    // If there were errors, redirect to the form
    if (count ($errors) > 0) {
        $_SESSION['setup']['err'] = $errors;
        redirect ($form_url);
    }
    
    // set the type
    $config['sqltype'] = (int) $config['sqltype'];
    $col->setName($config['name']);
    $col->setEngName($config['engname']);
    $col->setSqlSize($config['sql_size']);
    $col->setSqlAttributes(implode(' ', $sql_attributes));
    $col->setMandatory(@$config['mandatory']);
    $col->setComment($config['comments']);
    
    // check that the default value is valid, given the column configuration
    $col->setDefault (null);
    if (@$config['set_default'] and !($col instanceOf PasswordColumn)) {
        try {
            $dummy = null;
            $values = $col->collateInput($config['sql_default'], $dummy);
            $default = reset($values);
            $col->setDefault($default);
        } catch (DataValidationException $ex) {
            $errors[] = 'Default value failed to validate: '. $ex->getMessage ();
        }
    }
    
    // Check for errors again (since default value might not validate)
    if (count ($errors) > 0) {
        $_SESSION['setup']['err'] = $errors;
        redirect ($form_url);
    }
    
    // If there is a non-existent storage location, try to add it
    if (isset ($try_create_dir)) {
        if (defined ('FILE_PERMISSIONS_DIR')) {
            $result = @mkdir ($try_create_dir, FILE_PERMISSIONS_DIR, true);
        } else {
            $result = @mkdir ($try_create_dir, 0777, true);
        }
        
        // report to user
        if ($result) {
            $_SESSION['setup']['msg'][] = "The directory <em>{$try_create_dir}</em> has been automatically created";
        } else {
            $_SESSION['setup']['warn'][] = "The directory <em>{$try_create_dir}</em> failed to be automatically created";
        }
    }
    
    return $col;
}


/**
 * Adds a real column to a DB table, from its meta-descriptive Column object
 * @param Table $table the table to which the column will belong
 * @param Column $col the column
 * @param array $config Config options (e.g. $_POST data)
 * @return string the query used to update the database
 * @author benno 2010-11-11, 2011-08-04
 */
function column_def_add (Table $table, Column $col, $form_url, $config) {
    settype ($position_after, 'int');
    
    $sql_defn = $col->getSqlDefn ();
    if ($col instanceof IntColumn and $col->isAutoIncrement ()) {
        $auto_increment = true;
        $sql_defn = preg_replace ('/ +AUTO_INCREMENT/i', '', $sql_defn);
    } else {
        $auto_increment = false;
    }
    
    // insert position
    if ($config['insert_after'] == -1) {
        $sql_defn .= ' FIRST';
    } else if ($config['insert_after'] != '') {
        $columns = $table->getColumns ();
        $sql_defn .= ' AFTER `'. $columns[$config['insert_after']]->getName (). '`';
    }
    $q = "ALTER TABLE `{$table->getName()}` ADD COLUMN `{$config['name']}` {$sql_defn}";
    if ($auto_increment) {
        $q .= ", ADD UNIQUE INDEX (`{$config['name']}`)";
    }
    $res = execq($q, false, false);
    if (!$res) {
        $conn = ConnManager::get_active();
        $_SESSION['setup']['err'] = 'Column definition was not ' .
            'added due to a database error:<br>' . $conn->last_error() .
            "<br>Query was:<br>{$q}";
        redirect ($form_url);
    }
    if ($auto_increment) {
    }
    $table->addColumn ($col, $config['insert_after']);
    return $q;
}


/**
 * Alters an existing DB column, from its meta-descriptive Column object
 * @param Column $col the column
 * @param array $old_col Array containing the following keys:
 *                - 'name' (the former name for this column)
 *                - 'defn' (the former definition e.g. TINYINT(1) UNSIGNED NOT NULL)
 * @param array $config Config options (e.g. $_POST data)
 * @return string the query used to update the database
 * @author benno 2010-11-11
 */
function column_def_edit (Column $col, $old_col, $form_url, $config) {
    $table = $col->getTable ();
    
    // new position
    $sql_position = '';
    if ($config['insert_after'] == -1) {
        $sql_position = ' FIRST';
        $previous_col = null;
    } else if ($config['insert_after'] != 'retain') {
        $columns = $table->getColumns ();
        if ($config['insert_after'] == '') $config['insert_after'] = count ($columns) - 1;
        $previous_col = $columns[$config['insert_after']];
        $sql_position = ' AFTER `'. $previous_col->getName (). '`';
    }
    
    // change column definition in DB, as long as it's not an ENUM or TIMESTAMP column
    $is_enum = false;
    $is_timestamp = false;
    $q = "SHOW COLUMNS FROM `{$table->getName ()}`";
    $res = execq($q);
    $col_name = $col->getName ();
    while ($row = fetch_assoc($res)) {
        if ($row['Field'] != $col_name) continue;
        if (strcasecmp (substr ($row['Type'], 0, 4), 'ENUM') == 0) {
            $is_enum = true;
            break;
        } else if (strcasecmp ($row['Type'], 'TIMESTAMP') == 0) {
            $is_timestamp = true;
            break;
        }
    }
    
    $q = '';
    if ($is_enum) {
        $_SESSION['setup']['msg'][] = 'ENUM column definition unchanged';
    } else if ($is_timestamp) {
        $_SESSION['setup']['msg'][] = 'TIMESTAMP column definition unchanged';
    } else {
        
        $name_changed = $old_col['name'] != $col->getName ();
        $defn_changed = $old_col['defn'] != $col->getSqlDefn ();
        if ($name_changed or $defn_changed or $sql_position != '') {
            
            // For an AUTO_INCREMENT column, add a unique index if there isn't one already
            if ($col->isAutoIncrement ()) {
                $q = "SHOW INDEX FROM `{$table->getName()}`";
                $res = execq($q);
                
                // See if there's a UNIQUE or PK index
                $has_index = false;
                while ($row = fetch_assoc($res)) {
                    if ($row['Column_name'] == $old_col['name'] and $row['Non_unique'] == 0) {
                        $has_index = true;
                        break;
                    }
                }
                
                // Add index if missing
                if (!$has_index) {
                    $q = "ALTER TABLE `{$table->getName ()}` ".
                        "ADD UNIQUE INDEX `{$_POST['name']}` (`{$old_col['name']}`)";
                    $res = execq($q, false, false);
                    
                    if (!$res) {
                        $conn = ConnManager::get_active();
                        $err = 'Automatic index creation failed due to a ' .
                            'database error:<br>' . $conn->last_error();
                        $_SESSION['setup']['err'] = $err;
                        redirect ($form_url);
                    }
                }
            }
            
            $q = "ALTER TABLE `{$table->getName()}` CHANGE COLUMN ".
                "`{$old_col['name']}` `{$config['name']}` {$col->getSqlDefn ()}";
            if ($sql_position != '') $q .= ' '. $sql_position;
            $res = execq($q, false, false);
            
            if ($res) {
                $session['chosen_column'] = $config['name'];
            
            } else {
                $conn = ConnManager::get_active();
                $err = 'Column definition was not changed due to a ' .
                    'database error:<br>' . $conn->last_error() .
                    "<br>Query was:<br>{$q}";
                $_SESSION['setup']['err'] = $err;
                redirect ($form_url);
            }
        }
        
        if ($config['insert_after'] != 'retain') {
            $table->repositionColumn ($col, $previous_col);
        }
    }
    return    $q;
}


/**
 * Adds a column to the various views in a table
 * @param Column $col The column to add to the various views
 * @param array $config Config options (e.g. $_POST data)
 * @return void
 * @author benno 2010-11-11
 */
function column_def_update_views (Column $col, $config) {
    
    $table = $col->getTable ();
    
    // Determine position
    if ($config['insert_after'] == 'retain') {
        $index = $table->getColumnPosition ($col);
        $prev_index = $index - 1;
        $next_index = $index + 1;
    } else {
        $prev_index = $config['insert_after'];
        $next_index = $config['insert_after'] + 1;
    }
    $previous_col = $table->getColumnByPosition ($prev_index);
    if ($previous_col === $col) $previous_col = $table->getColumnByPosition ($prev_index - 1);
    $next_col = $table->getColumnByPosition ($next_index + 1);
    if ($next_col === $col) $next_col = $table->getColumnByPosition ($next_index + 2);
    
    $col_view_item = new ColumnViewItem ();
    $col_view_item->setDetails ($col, true);
    
    // Main & export view
    $views = array (
        'list' => 'list_view',
        'export' => 'export_view'
    );
    foreach ($views as $view => $attrib) {
        if (@$config[$attrib]) {
            if ($config['insert_after'] == 'retain') {
                if ($table->getColumnInView ($view, $col->getName ()) !== null) continue;
                column_relative_view_insert ($table, $col_view_item, $view, $previous_col, $next_col);
            } else {
                $table->removeColumnFromView ($view, $col);
                if ($config['insert_after'] === '') {
                    $table->appendView ($view, $col_view_item);
                } else if ($config['insert_after'] == -1) {
                    $table->insertView ($view, -1, $col_view_item);
                } else {
                    column_relative_view_insert ($table, $col_view_item, $view, $previous_col, $next_col);
                }
            }
        } else {
            $table->removeColumnFromView ($view, $col);
        }
    }
    column_def_update_add_edit_view ($col_view_item, $config, $previous_col, $next_col);
}

/**
 * Updates the add/edit view for a Table by inserting or repositioning a view item
 * @param ColumnViewItem $col_view_item the view item to insert/reposition
 * @param array $config Config options, i.e. POST data from col add/edit form
 * @param mixed $previous_col the Column after which to position $col_view_item, or null
 * @param mixed $next_col the Column before which to position $col_view_item, or null
 * @return void
 * @author benno 2010-11-11
 */
function column_def_update_add_edit_view (ColumnViewItem $col_view_item, $config, $previous_col, $next_col) {
    $col = $col_view_item->getColumn ();
    $table = $col->getTable ();
    
    // Search for:
    // existing view item to be updated (if retaining position)
    // existing view item to be removed (if changing position)
    // previous and next view items (if adding new item or changing position)
    $view = $table->getAddEditView ();
    $action = 'add';
    $add_edit_item = false;
    $extant_index = -1;
    
    // remove all references to this column if it is never to be displayed
    // on the add or edit forms
    if (@$config['add_view'] != 1 and @$config['edit_view_show'] != 1) {
        $action = 'remove';
        $config['insert_after'] = 'remove';
    }
    foreach ($view as $item_index => $search_item) {
        $item = $search_item['item'];
        if ($item instanceof ColumnViewItem and $item->getColumn () === $col) {
            if ($config['insert_after'] == 'retain') {
                $add_edit_item = $search_item;
                $extant_index = $item_index;
                $action = 'update';
            } else {
                unset ($view[$item_index]);
            }
        }
    }
    
    // item was not found - create a new one
    if ($add_edit_item === false) {
        $add_edit_item = array ();
        $add_edit_item['item'] = $col_view_item;
        $add_edit_item['add'] = false;
        $add_edit_item['edit_view'] = false;
        $add_edit_item['edit_change'] = false;
    }
    
    // set which actions are allowed
    $add_edit_item['add'] = false;
    if (@$config['add_view']) $add_edit_item['add'] = true;
    
    $add_edit_item['edit_view'] = false;
    $add_edit_item['edit_change'] = false;
    if (@$config['edit_view_show']) {
        $add_edit_item['edit_view'] = true;
        if (@$config['edit_view_edit']) $add_edit_item['edit_change'] = true;
    }
    
    if ($action != 'add') {
        if ($action == 'update') $view[$extant_index] = $add_edit_item;
        $table->setAddEditView ($view);
        return;
    }
    
    // insert new record in the desired position
    if ($config['insert_after'] == -1) {
        $new_view = array_merge (array ($add_edit_item), $view);
    } else if ($config['insert_after'] == '') {
        $new_view = $view;
        $new_view[] = $add_edit_item;
    } else {
        $inserted = false;
        $new_view = array ();
        foreach ($view as $search_item) {
            if (!$inserted) {
                $item = $search_item['item'];
                if ($item instanceof ColumnViewItem) {
                    if ($item->getColumn () === $previous_col) {
                        $new_view[] = $search_item;
                        $new_view[] = $add_edit_item;
                        $inserted = true;
                    } else if ($item->getColumn () === $next_col) {
                        $new_view[] = $add_edit_item;
                        $new_view[] = $search_item;
                        $inserted = true;
                    } else {
                        $new_view[] = $search_item;
                    }
                } else {
                    $new_view[] = $search_item;
                }
            } else {
                $new_view[] = $search_item;
            }
        }
        if (!$inserted) {
            $new_view[] = $add_edit_item;
        }
    }
    
    // save
    $table->setAddEditView ($new_view);
}


/**
 * Inserts a column view item in position into a view, by attempting the following, in order of preference:
 * - inserting after the 'previous column'
 * - inserting before the 'next column'
 * - inserting at the end of the view
 * @param Table $table the table that contains the column and views
 * @param ColumnViewItem $col_view_item the view item for the column
 * @param int $view The view type: 'list', 'export'
 * @param mixed $previous_col The previous Column to search for, or null
 * @param mixed $next_col The next Column to search for, or null
 * @return void
 * @author benno 2010-11-11
 */
function column_relative_view_insert (Table $table, ColumnViewItem $col_view_item, $view, $previous_col, $next_col) {
    $col = $col_view_item->getColumn ();
    if ($table->getColumnInView ($view, $col->getName ()) !== null) {
        return;
    }
    if ($previous_col) {
        $previous_index = $table->getColumnInView ($view, $previous_col->getName (), true);
        if ($previous_index !== false) {
            $table->insertView ($view, $previous_index + 1, $col_view_item);
            return;
        }
    }
    if ($next_col and !$inserted) {
        $next_index = $table->getColumnInView ($view, $next_col->getName (), true);
        if ($next_index !== false) {
            $table->insertView ($view, $next_index, $col_view_item);
            return;
        }
    }
    if (!$inserted) {
        $table->appendView ($view, $col_view_item);
    }
}


/**
 * Sets up default values to be used for a new column
 * 
 * @return array
 * @author benno 2010-11-11
 */
function column_def_defaults () {
    return array (
        'sql_attribs' => array ('NOT NULL'),
        'trim' => 1,
        'tabs' => 1,
        'multispace' => 1,
        'nl' => 1,
        'tags' => 1,
        'br' => 1
    );
}

?>
