/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

var retain_col_size = false;

function show_tree_options () {
    if (document.forms.table_edit.display_style.value == 2) {
        show_row ('tree_top_level');
        show_row ('tree_width');
        show_row ('tree_partition');
    } else {
        hide_row ('tree_top_level');
        hide_row ('tree_width');
        hide_row ('tree_partition');
    }
}


var last_selected_date_format = '';
/**
* Calls set_custom() when a custom format is entered in the text box
* This needs to be delayed since the key pressed isn't incorporated into the form field's value
* until after the onkeypress action has been handled
**/
function set_custom_init () {
    window.setTimeout ("set_custom ();", 10);
}

/**
* Selects 'custom' in the date format drop-down,
* or selects the previously selected format if the custom field is empty
**/
function set_custom () {
    
    current_form = document.forms.coldata;
    
    if (current_form) {
        
        var custom_val = current_form.custom_date_format.value;
        if (custom_val != '') {
            if (current_form.date_format.value != 'custom') {
                last_selected_date_format = current_form.date_format.value;
            }
            current_form.date_format.value = 'custom';
        } else if (last_selected_date_format != '') {
            current_form.date_format.value = last_selected_date_format;
            last_selected_date_format = '';
        }
    }
}

function default_keypress () {
    window.setTimeout ('default_ticker ();', 10);
}

function default_ticker () {
    var col_form = document.forms.coldata;
    if (!col_form) return;
    var default_input = col_form.sql_default;
    var default_box = col_form.set_default;
    if (!default_input || !default_box) return;
    if (default_input.value != '') {
        default_box.checked = true;
    } else {
        default_box.checked = false;
    }
}

function set_col_class (class_name) {
    var form = document.forms.coldata;
    if (!form) return;
    form.elements['class'].value = class_name;
    col_class_presets (class_name);
    sql_type_options ();
}

/**
* Automatically sets a table or column's english name based on the SQL name for the table or column
**/
function set_english_name () {
    
    var sql_name;
    var eng_name;
    var use_form = document.forms.coldata;
    var col_form = true;
    
    // determine which fields to use (different fields and form used for table and column)
    if (use_form) {
        sql_name = use_form.name.value;
        eng_name = use_form.engname;
    } else {
        col_form = false;
        use_form = document.forms.tbldata;
        if (!use_form) {
            return false;
        }
        sql_name = use_form.table_name.value;
        eng_name = use_form.table_name_eng;
    }
    
    var new_eng_name = '';
    
    if (sql_name && eng_name) {
        
        // ids
        if (sql_name == 'ID') {
            
            // If name is 'ID', copy directly
            new_eng_name = 'ID';
            
            // set up ID defaults when creating a new ID column
            if (col_form) {
                set_col_class ('IntColumn');
                use_form.sqltype.value = 'INT';
                document.getElementById ('attrib_unsigned').checked = true;
                document.getElementById ('attrib_auto_inc').checked = true;
                document.getElementById ('attrib_not_null').checked = true;
                document.getElementById ('mandatory').checked = true;
            }
            
            
        // urls
        } else if (sql_name == 'URL') {
            
            // If name is 'URL', copy directly
            new_eng_name = 'URL';
            if (col_form) {
                //TODO: add UrlColumn class
                set_col_class('CharColumn');
                use_form.sqltype.value = 'VARCHAR';
                use_form.sql_size.value = '255';
                col_main ();
                col_editable ();
            }
            
            
        // If name is 'OrderNum', Make it something useful and set an option
        } else if (sql_name == 'OrderNum') {
            
            new_eng_name = 'Order';
            if (col_form) {
                set_col_class('IntColumn');
                use_form.sqltype.value = 'TINYINT';
                use_form.sql_size.value = '1';
                document.getElementById ('attrib_unsigned').checked = true;
                document.getElementById ('attrib_not_null').checked = true;
                col_main ();
            }
            
            
        // files
        } else if (sql_name == 'File') {
            new_eng_name = sql_name;
            set_col_class ('FileColumn');
            if (col_form) {
                use_form.sqltype.value = 'VARCHAR';
                use_form.sql_size.value = '80';
                use_form.storeloc.value = 'dbfiles';
                col_editable ();
            }
            
            
        // images, pictures, photos
        } else if (sql_name == 'Image' || sql_name == 'Picture' || sql_name == 'Photo') {
            new_eng_name = sql_name;
            set_col_class ('ImageColumn');
            if (col_form) {
                use_form.sqltype.value = 'VARCHAR';
                use_form.sql_size.value = '80';
                use_form.storeloc.value = 'dbfiles';
                col_editable ();
            }
            
            
        // email addresses
        } else if (sql_name == 'Email') {
            new_eng_name = 'E-mail';
            if (col_form) {
                set_col_class('EmailColumn');
                use_form.sqltype.value = 'VARCHAR';
                if (use_form.sql_size.value == '') {
                    use_form.sql_size.value = '40';
                }
                col_editable ();
            }
            
            
        // names of things - title, main view, editable, mandatory
        } else if (sql_name == 'Name') {
            new_eng_name = 'Name';
            if (col_form) {
                set_col_class ('CharColumn');
                var sql_size = use_form.elements['sql_size'];
                if (sql_size && sql_size.value == '') {
                    sql_size.value = 30;
                }
                document.getElementById ('mandatory').checked = true;
                col_main ();
                col_editable ();
            }
            
            
        // people names - first name
        } else if (sql_name == 'FirstName' || sql_name == 'NameFirst') {
            new_eng_name = 'First name';
            if (col_form) {
                //TODO: implement HumanNameColumn
                set_col_class('CharColumn');
                col_main ();
                col_editable ();
            }
            
            
        // people names - last name
        } else if (sql_name == 'LastName' || sql_name == 'NameLast') {
            new_eng_name = 'Last name';
            if (col_form) {
                //TODO: implement HumanNameColumn
                set_col_class('CharColumn');
                col_main ();
                col_editable ();
            }
            
            
        // everything else
        } else {
            
            // If the sql_name ends in ID, trim it off, so that ProductID will be become Product
            // also make it an integer field.
            if (col_form) {
                if (sql_name.substr(sql_name.length - 2, 2) == 'ID') {
                    sql_name = sql_name.substr(0, sql_name.length - 2);
                    
                    set_col_class('IntColumn');
                    use_form.sqltype.value = 'INT';
                    document.getElementById ('attrib_unsigned').checked = true;
                    document.getElementById ('attrib_not_null').checked = true;
                    document.getElementById ('mandatory').checked = true;
                }
            }
            
            
            // In all other cases, try to convert to a sensible english name
            var chr;
            var chr_lower;
            
            for (var i = 0; i <= sql_name.length; i++) {
                
                chr = sql_name.charAt (i);
                chr_lower = chr.toLowerCase ();
                if (i == 0) chr = chr.toUpperCase();
                
                if (chr_lower != chr && i > 0) {
                    // replace uppercase letters with space and appropriate lowercase letter
                    new_eng_name += ' ' + chr_lower;
                } else if (chr == '_') {
                    new_eng_name += ' ';
                } else {
                    // copy lowercase letters normally
                    new_eng_name += chr;
                }
                
            }
            
            if (col_form) {
                col_editable ();
            }
            
        }
        
        eng_name.value = new_eng_name;
        
        set_single_name ();
        
    } else {
        alert ('Unable to find name and engname fields');
    }
    
    on_autoinc_click();
}

/**
* Automatically sets a table or column's singular name based on the english name for the table or column
**/
function set_single_name () {
    var use_form = document.forms.tbldata;
    if (!use_form) {
        return false;
    }
    
    var multi = use_form.table_name_eng.value;
    var single;
    
    switch (multi) {
        case 'People': single = 'Person'; break;
        case 'Businesses': single = 'Business'; break;
        case 'Countries': single = 'Country'; break;
            
        default:
            var match = multi.search (/statuses$/i);
            if (match != -1) {
                single = multi.substr (0, multi.length - 2);
            } else {
                single = multi.replace (/s$/, '');
            }
    }
    
    use_form.table_name_single.value = single;
    
}


/* Checks boxes at the bottom of the column add/edit */
function col_main () {
    node = document.getElementById ('main_display');
    if (node != null) {
        node.checked = true;
    } else {
        document.getElementById ('list_view').checked = true;
        document.getElementById ('export_view').checked = true;
    }
}

function col_visible () {
    node = document.getElementById ('edit_visible');
    if (node != null) {
        node.checked = true;
    } else {
        document.getElementById ('add_view').checked = true;
        document.getElementById ('edit_view_show').checked = true;
    }
}

function col_editable () {
    node = document.getElementById ('editable');
    if (node != null) {
        node.checked = true;
        document.getElementById ('edit_visible').checked = true;
    } else {
        document.getElementById ('add_view').checked = true;
        document.getElementById ('edit_view_show').checked = true;
        document.getElementById ('edit_view_edit').checked = true;
    }
}



// when a column is editable, it must also be visible
function update_editable_status () {
    editable = document.getElementById ('edit_view_edit');
    visible = document.getElementById ('edit_view_show');
    
    if (editable && editable.checked) {
        visible.checked = true;
    }
}



// when a column isn't visible, it can't be editable
function update_visible_status () {
    editable = document.getElementById ('edit_view_edit');
    visible = document.getElementById ('edit_view_show');
    
    if (visible && !visible.checked) {
        editable.checked = false;
    }
}



function creation_editable_visible_status (pressed_button) {
    visible = document.getElementById ('edit_visible');
    editable = document.getElementById ('editable');
    
    if (pressed_button == 'visible') {
        if (!visible.checked) {
            editable.checked = false;
        }
    } else if (pressed_button == 'editable') {
        if (editable.checked) {
            visible.checked = true;
        }
    }
}


function on_autoinc_click () {
    var val, autoinc_node, node;
    
    autoinc_node = document.getElementById('attrib_auto_inc');
    if (autoinc_node == null) return;
    
    val = autoinc_node.checked;
    if (autoinc_node.disabled) val = false;
    
    node = document.getElementById('attrib_default');
    if (node == null) return;
    node.disabled = val;
    
    node = document.getElementById('attrib_default_value');
    if (node == null) return;
    node.disabled = val;
}


/**
* Updates the UI to reflect the capabilities of the chosen SQL column type.
* For example, the UNSIGNED attribute only applies to numeric columns.
**/
function sql_type_options () {
    
    var sql_select = document.getElementById ('sql_type');
    var chosen_sql_type = sql_select.options[sql_select.selectedIndex].innerHTML;
    var include_seconds = document.getElementById ('inc_secs');
    var sql_unsigned = document.getElementById ('attrib_unsigned');
    var auto_inc = document.getElementById ('attrib_auto_inc');
    var type = document.forms.coldata.type;
    var sql_default = document.forms.coldata.sql_default;
    var options = document.forms.coldata.options;
    var option_selected = get_radio_value (options);
    var number_pattern, replace_pattern;
    
    // disable or enable unsigned attribute, based on type
    switch (chosen_sql_type) {
        case 'INT':
        case 'TINYINT':
        case 'SMALLINT':
        case 'MEDIUMINT':
        case 'BIGINT':
            sql_unsigned.disabled = false;
            auto_inc.disabled = false;
            
            // make sure that if default exists, it is an integer
            if (sql_unsigned.checked) {
                number_pattern = /^[0-9]+$/;
                replace_pattern = /[^0-9]/g;
            } else {
                number_pattern = /^\-?[0-9]+$/;
                replace_pattern = /[^\-0-9]/g;
            }
            if (!sql_default.value.match(number_pattern)) {
                sql_default.value = sql_default.value.replace(replace_pattern, '');
                if (sql_default.value == '') {
                    document.getElementById ('attrib_default').checked = false;
                }
            }
            break;
            
        case 'DECIMAL':
        case 'FLOAT':
        case 'DOUBLE':
            sql_unsigned.disabled = false;
            include_seconds.disabled = true;
            auto_inc.disabled = true;
            
            // make sure that if default exists, it is a decimal number
            if (!is_decimal (sql_default.value)) {
                sql_default.value = sql_default.value.replace (/[^\.0-9]/g, '');
                if (!is_decimal (sql_default.value)) {
                    sql_default.value = '';
                    document.getElementById ('attrib_default').checked = false;
                }
            }
            
            if (is_decimal (sql_default.value) && type.options[type.selectedIndex].innerHTML == 'currency') {
                sql_default.value = make_currency (sql_default.value);
            }
            break;
        
        case 'DATE':
            sql_unsigned.disabled = true;
            include_seconds.disabled = true;
            auto_inc.disabled = true;
            if (!is_valid_date (sql_default.value)) {
                sql_default.value = '';
                document.getElementById ('attrib_default').checked = false;
            }
            enable_date_formats (['Date']);
            break;
        
        case 'DATETIME':
            sql_unsigned.disabled = true;
            include_seconds.disabled = false;
            auto_inc.disabled = true;
            if (!is_valid_date_time (sql_default.value)) {
                sql_default.value = '';
                document.getElementById ('attrib_default').checked = false;
            }
            enable_date_formats (['Date', 'Date and time']);
            break;
        
        case 'TIME':
            sql_unsigned.disabled = true;
            include_seconds.disabled = false;
            include_seconds.checked = true;
            auto_inc.disabled = true;
            if (!is_valid_time (sql_default.value)) {
                sql_default.value = '';
                document.getElementById ('attrib_default').checked = false;
            }
            enable_date_formats (['Time']);
            break;
        
        case 'CHAR':
        case 'VARCHAR':
        case 'TEXT':
        case 'TINYTEXT':
        case 'MEDIUMTEXT':
        case 'LONGTEXT':
            sql_unsigned.disabled = true;
            auto_inc.disabled = true;
            break;
            
        default:
            sql_unsigned.disabled = true;
            auto_inc.disabled = true;
            break;
    }
    
    var defn = document.getElementById('sql_defn');
    var defn_opts = document.getElementById('sql_defn_opts');
    if (chosen_sql_type == 'LINK') {
        add_class(defn, 'display-none');
    } else {
        rem_class(defn, 'display-none');
    }
    
    on_autoinc_click();
}


function update_select (id, url) {
    id = String (id);
    
    var node = document.getElementById (id);
    if (!node) {
        window.alert ("Called update_select with invalid node id: " + id);
        return;
    } else if (!node.nodeName) {
        window.alert ("Called update_select on invalid node type");
        return;
    } else if (node.nodeName != 'SELECT') {
        window.alert ("Called update_select with invalid node type: " + node.nodeName);
        return;
    }
    
    // remove all children and add "Please wait..."
    var child;
    if (node.childNodes) {
        for (var i = node.childNodes.length - 1; i >= 0; i --) {
            child = node.childNodes.item (i);
            node.removeChild (child);
        }
    }
    child = document.createElement ('option');
    child.appendChild (document.createTextNode ('Please wait...'));
    node.appendChild (child);
    
    var node = document.getElementById(id);
    var ajax_handler = new NodeReplacementHandler(node);
    queue.request ('get', url, ajax_handler, null, false);
}

/**
* Initialises the JavaScript data consistency functions when editing an existing column
**/
function column_edit_init () {
    retain_col_size = true;
    update_editable_status ();
    sql_type_options ();
    //option_changed ();
}

/**
* Updates the UI to reflect the capabilities of the chosen column class.
* For example, DecimalClass can only use a DECIMAL SQL field.
**/
function col_class_presets (class_name) {
    
    var options = document.getElementById ('options');
    var divs = options.getElementsByTagName ('div');
    var i, div;
    var show_div = 'options-' + class_name;
    for (i = 0; i < divs.length; ++i) {
        div = divs.item (i);
        if (div.id == show_div) {
            rem_class (div, 'display-none');
        } else {
            add_class (div, 'display-none');
        }
    }
    
    var col_class = column_classes[class_name];
    if (!col_class) return;
    redraw_sql_types (col_class);
    document.forms.coldata.sql_size.disabled = false;
    
    var strip_tags = document.getElementById ('tags');
    var sql_size = document.forms.coldata.sql_size;
    var size_contains_comma = sql_size.value.search (/,/);
    var size_empty = false;
    if (size_contains_comma == -1) {
        size_contains_comma = false;
    } else {
        size_contains_comma = true;
    }
    if (sql_size.value == '') {
        size_empty = true;
    }
    
    if (class_name == 'LinkColumn') {
        var sql_type = document.forms.coldata.sqltype;
        sql_type.value = 'LINK';
    }
}


/**
* Enables (or disables) specific SQL type groups
* @param groups_to_enable an array of group names (the labels used on the optgroup HTML elements)
**/
function enable_sql_groups (groups_to_enable) {
    
    var groups = document.forms.coldata.sqltype.childNodes;
    var group_num;
    var group;
    var group_id;
    var group_enabled;
    
    var debug_txt = '';
    
    for (group_num = 0; group_num < groups.length; group_num++) {
        group = groups.item (group_num);
        
        // skip whitespace
        if (group.nodeType && group.nodeType == 1) {
            
            debug_txt += group.nodeName + ': ' + group.getAttribute ('label') + "\n";
            group_enabled = false;
            
            for (group_id in groups_to_enable) {
                if (groups_to_enable[group_id] == group.label) {
                    group.disabled = false;
                    group_enabled = true;
                }
            }
            if (!group_enabled) {
                group.disabled = true;
            }
        }
    }
    
    // alert (debug_txt);
    
}

/**
* Redraws the SQL type list to match the column type
* @param col_class a column class definition (see column_classes.js.php)
**/
function redraw_sql_types (col_class) {
    
    var sql_type_select_node = document.forms.coldata.sqltype;
    
    var selected = sql_type_select_node.value;
    
    var options = sql_type_select_node.getElementsByTagName ('option');
    
    while (options.length > 1) {
        sql_type_select_node.removeChild (sql_type_select_node.lastChild);
    }
    
    var type_id, type, option;
    var selected_found = false;
    for (type_id in col_class['types']) {
        type = col_class['types'][type_id];
        if (type == selected) selected_found = true;
        option = create_element ('option', {'value': type});
        option.appendChild (document.createTextNode (type));
        sql_type_select_node.appendChild (option);
    }
    if (!selected_found) selected = col_class['default'];
    
    sql_type_select_node.value = selected;
    
}

/**
* Enables a specific date format group or groups, and disables the others
* @param groups_to_enable an array of group names
**/
function enable_date_formats (groups_to_enable) {
    
    var groups = document.forms.coldata.date_format.childNodes;
    var group_num;
    var group;
    var group_id;
    var group_enabled;
    
    var debug_txt = '';
    
    for (group_num = 0; group_num < groups.length; group_num++) {
        group = groups.item (group_num);
        
        // skip whitespace
        if (group.nodeType && group.nodeType == 1 && group.nodeName == 'OPTGROUP') {
            
            group_enabled = false;
            
            for (group_id in groups_to_enable) {
                if (groups_to_enable[group_id] == group.label) {
                    group.disabled = false;
                    group_enabled = true;
                }
            }
            if (!group_enabled) {
                group.disabled = true;
            }
        }
    }
    
    // choose a new format if the current one belongs to an invalid (and thus disabled) group
    var format_select = document.forms.coldata.date_format;
    var child_id;
    var child;
    var default_found = false;
    
    if (format_select.options[format_select.selectedIndex].parentNode.disabled) {
        for (group_num = 0; group_num < groups.length; group_num++) {
            group = groups.item (group_num);
            if (group.nodeType && group.nodeType == 1 && group.nodeName == 'OPTGROUP' && !group.disabled) {
                
                for (child_id = 0; child_id < group.childNodes.length; child_id++) {
                    child = group.childNodes.item (child_id);
                    if (child.nodeType && child.nodeType == 1) {
                        
                        format_select.value = child.value;
                        default_found = true;
                        break;
                    }
                }
                
            }
            if (default_found) break;
        }
    }
    
}

function set_var (the_var, new_val) {
    if (the_var.value == '') {
        the_var.value = new_val;
    }
}


/**
* Updates any UI components depending on the current selected option
**/
function option_changed () {
    
    var current_option = get_radio_value (document.forms.coldata.options);
    
    switch (current_option) {
        case '':
            document.getElementById ('trim').disabled = false;
            document.getElementById ('tabs').disabled = false;
            document.getElementById ('multispace').disabled = false;
            document.getElementById ('nl').disabled = false;
            document.getElementById ('tags').disabled = false;
            document.getElementById ('br').disabled = false;
            document.getElementById ('storage_location_tr').style.display = 'none';
            document.getElementById ('max_file_size_tr').style.display = 'none';
            break;
            
        case 'image':
        case 'file':
            document.getElementById ('trim').disabled = true;
            document.getElementById ('tabs').disabled = true;
            document.getElementById ('multispace').disabled = true;
            document.getElementById ('nl').disabled = true;
            document.getElementById ('tags').disabled = true;
            document.getElementById ('br').disabled = true;
            document.getElementById ('storage_location_tr').style.display = '';
            document.getElementById ('max_file_size_tr').style.display = '';
            break;
            
        default:
            document.getElementById ('trim').disabled = true;
            document.getElementById ('tabs').disabled = true;
            document.getElementById ('multispace').disabled = true;
            document.getElementById ('nl').disabled = true;
            document.getElementById ('tags').disabled = true;
            document.getElementById ('br').disabled = true;
            document.getElementById ('storage_location_tr').style.display = 'none';
            document.getElementById ('max_file_size_tr').style.display = 'none';
    }
    
}

/**
* Opens the specified help text page in a separate window
**/
function help (page) {
    var help_win = window.open (
        'help/' + page + '.php',
        'help_' + page,
        'menubar=0,status=0,toolbar=0,width=750,height=600,scrollbars=1,directories=0'
    );
    if (help_win) {
        help_win.focus ();
    } else {
        alert ("You have pop-ups blocked, try again once you have unblocked this site");
    }
}

/**
* Determines if a string is a valid decimal number or not.
* 
* @param string
* @return bool
**/
function is_decimal (num) {
    if (num.match (/^(([0-9]+(\.[0-9]+)?)|(\.[0-9]+))$/)) {
        return true;
    } else {
        return false;
    }
}

/**
* Forces a decimal number to be a currency value
* 
* This will round a decimal number to two decimal points
**/
function make_currency (num) {
    var parts = String (num).split ('.');
    if (parts[1].length <= 2) {
        while (parts[1].length < 2) {
            parts[1] += '0';
        }
    } else {
        parts[1] = String (Math.round (Number (parts[1].substr (0, 2) + '.' + parts[1].substr (2))));
    }
    return parts[0] + '.' + parts[1];
}

/**
* Automatically tick the NOT NULL box if mandatory is ticked.
*/
function tick_not_null () {
    var mandatory_node = document.getElementById ('mandatory');
    var not_null_node = document.getElementById ('attrib_not_null');
    
    if (mandatory_node.checked) {
        not_null_node.checked = true;
    }
}

/**
* Updates the list of collations available to match the selected charset
**/
function on_charset_change () {
    var charset_node = document.getElementById ('charset');
    var collation_node = document.getElementById ('collation');
    
    if (!charset_node || !collation_node) return;
    if (!collations[charset_node.value]) return;
    
    var new_collations = collations[charset_node.value];
    var children = collation_node.childNodes;
    while (children.length > 0) {
        collation_node.removeChild (children.item (0));
    }
    
    var i, option_node;
    for (i in new_collations) {
        option_node = create_element ('option', {'value': new_collations[i]});
        option_node.appendChild (document.createTextNode (new_collations[i]));
        collation_node.appendChild (option_node);
    }
    
}


function image_exact_no_minimum(select, num) {
    var min_size = document.getElementById('image_min_size' + num);
    if (!min_size) return;
    if (select.value == 'exact') {
        min_size.className += ' display-none';
    } else {
        min_size.className = min_size.className.replace(' display-none', '');
    }
}
