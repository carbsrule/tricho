/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

var highlightColour = '#BBBBFF';
var regColour;

function chooseTable () {
    if (document.forms.table_select.table.value != '') {
        document.forms.table_select.submit (); return true;
    }
}

function highlight (row) {
    regColour = row.style.backgroundColor;
    row.style.backgroundColor = highlightColour;
}

function remove_highlight (row) {
    row.style.backgroundColor = regColour;
}

/**
 * Confirm the deletion of a row or rows
*/
function confirmDelete (table, message) {
    var result = false;
    if (message == '') message = 'Click OK to confirm deletion';
    if (confirm (message)) {
        var form = eval ("document.forms['rows_" + table + "']");
        form.rem.value = '-';
        form.submit ();
        result = true;
    }
    return result;
}

/**
* Show an A link in a popup window
* returns false so you can use    onClick="return popup_a(this);" 
* and not have the link actually be clicked
**/
var help_window
function popup_a (object) {
    if (help_window == null || help_window.closed) {
        help_window = window.open(object.href, 'help', 'width=400,height=400,menubar=no,toolbar=no,location=no,status=no,resizable=yes,scrollbars=yes');
    } else {
        help_window.focus ();
        help_window.location = object.href;
    }
    
    return false;
}

/**
* Check all elements on the specified form whose name begins with the specified string
**/
function checkall (form, beginswith) {
    var form = eval ('document.forms.' + form);
    
    var nodes = form.elements;
    var x = 0;
    var input;
    var part;
    
    for (x = 0; x < nodes.length; x++ ) {
        input = nodes[x];
        
        part = input.name.substring(0, beginswith.length)
        
        if (part == beginswith) {
            input.checked = true;
        }
    }
}

/**
 * Uncheck all elements on the specified form whose name begins with the specified string
*/
function uncheckall (form, beginswith) {
    var form = eval ('document.forms.' + form);
    
    var nodes = form.elements;
    var x = 0;
    var input;
    var part;
    
    for (x = 0; x < nodes.length; x++ ) {
        input = nodes[x];
        
        part = input.name.substring(0, beginswith.length)
        
        if (part == beginswith) {
            input.checked = false;
        }
    }
}

/**
 * Activate the main firm
*/
function activate_main_form (main_url, edit_url, page_num, form_id) {
    var main_form = document.getElementById (form_id);
    
    // set up row highlights
    var rows = main_form.getElementsByTagName ('tr');
    var current_row;
    var cols;
    var current_col;
    var inputs;
    var links;
    var input_name;
    var pk;
    // ignore the first row, since it's just the headings
    for (var i = 1; i < rows.length; i++) {
        current_row = rows.item (i);
        if (current_row.className == 'header-row') { continue; }
        current_row.onmouseover = function () { eval ('highlight (this);'); }
        current_row.onmouseout = function () { eval ('remove_highlight (this);'); }
        
        // set clickable class (hand on rollover), mouse click action (go to edit page),
        // have rollover and rollouts change status text to give url
        cols = current_row.getElementsByTagName ('td');
        for (var j = 0; j < cols.length; j++) {
            current_col = cols.item (j);
            // see if column has checkbox inside it - if so, extract primary key value but ignore it
            inputs = current_col.getElementsByTagName ('input');
            if (inputs.length != 0) {
                input_name = inputs.item (0).name;
                pk = input_name.substring (4, input_name.length - 1);
            } else {
                
                // see if there's already a link in the column
                // if so (e.g. for ordernum arrows), don't bother adding onclick action
                links = current_col.getElementsByTagName ('a');
                if (links.length == 0) {
                    bind_link_and_status (current_col, edit_url + '&id=' + pk);
                }
            }
        }
        
    }
    
    // set up pagination
    var rs_nav;
    var spans;
    var span_num;
    var curr_span;
    if (rs_nav = document.getElementById ('rs_nav')) {
        spans = rs_nav.getElementsByTagName ('td');
        for (span_num = 2; span_num < spans.length - 2; span_num++) {
            curr_span = spans.item (span_num);
            bind_link_and_status (curr_span, main_url + '&page=' + page_num);
            page_num++;
        }
    }
    
}

/**
* something to do with binding
**/
function bind_link_and_status (obj, url) {
    var url_base = window.location.href;
    
    var slash_pos = null;
    var curr_char;
    for (var i = 0; i < url_base.length; i++) {
        curr_char = url_base.charAt (i);
        if (curr_char == '/') {
            slash_pos = i;
        } else if (curr_char == '?' || curr_char == '#') {
            break;
        }
    }
    if (slash_pos != null) {
        url = url_base.substr (0, slash_pos + 1) + url;
    } else {
        url = 'FAIL' + slash_pos;
    }
    
    obj.style.cursor = 'pointer';
    
    obj.onmouseover = function () {
        window.status = url;
    }
    obj.onmouseout = function () {
        window.status = '';
    }
    obj.onclick = function () {
        window.location = url;
    }
}

/**
* Check a box but check a field first
**/
function check_if_used (checkbox_id, field_to_check) {
    checkbox = document.getElementById (checkbox_id);
    
    if (field_to_check.type == 'text') {
        if (field_to_check.value != '') {
            checkbox.checked = true;
            return;
        }
    } else if (field_to_check.type == 'checkbox') {
        if (field_to_check.checked) {
            checkbox.checked = true;
            return;
        }
    }
}

/**
* Clear a field
**/
function clearField (fieldID) {
    var field;
    if (field = document.getElementById (fieldID)) {
        field.value = '';
        return true;
    } else {
        return false;
    }
}


/**
* Detect IE
**/
var is_msie = false;
var msie_ver = 0;
if (navigator.userAgent.match ('MSIE') !== null) {
    is_msie = true;
    var matches = navigator.userAgent.match (/MSIE ([0-9]+)\.[0-9]+/);
    if (matches.length > 1) {
        msie_ver = Math.floor (Number (matches[1]));
    }
}


///
/// Functions to make IE play nice with DOM hacking
///

/**
* Create an element
**/
function create_element (tag_name, attribs) {
    
    var element = null;
    
    tag_name = String (tag_name);
    tag_name = tag_name.toLowerCase ();
    
    // IE is broken with many elements but its non-W3C methods seem to work
    // IE 9 updated its document.createElement syntax to match W3C and broke
    // compatibility with earlier IE versions
    if (is_msie && msie_ver < 9) {
        var tag_text;
        tag_text = '<' + tag_name;
        for (var key in attribs) {
            tag_text += ' ' + String (key) + '="' + String (attribs[key]) + '"';
        }
        tag_text += '>';
        element = document.createElement (tag_text);
    } else {
        element = document.createElement (tag_name);
        for (var key in attribs) {
            element.setAttribute (String (key), String (attribs[key]));
        }
    }
    return element;
    
}

/**
* Create a select list
**/
function create_select (attribs, options, selected_key) {
    var select_node = create_element ('select', attribs);
    
    var i = 0;
    
    for (var key in options) {
        select_node.options[i++] = new Option (options[key], String (key));
        if (key == selected_key) {
            select_node.value = selected_key;
        }
    }
    return select_node;
}

/**
* Select or deselect everything on a main list
**/
function main_select_all (form_id) {
    var main_form = document.getElementById (form_id);
    var inputs = main_form.getElementsByTagName ('input');
    var input;
    var all_selected = true;
    
    // see if all items are deselected
    for (var input_count = 0; input_count < inputs.length; input_count++) {
        input = inputs.item (input_count);
        if (input.type == 'checkbox' && !input.checked) {
            all_selected = false;
        }
    }
    
    for (var input_count = 0; input_count < inputs.length; input_count++) {
        input = inputs.item (input_count);
        if (input.type == 'checkbox') {
            if (!all_selected) {
                input.checked = true;
            } else {
                input.checked = false;
            }
        }
    }
}

function nice_labels () {
    var labels = document.getElementsByTagName ('label');
    
    for (var id in labels) {
        var current_label = labels[id];
        
        if (current_label && current_label.tagName) {
            
            var inputs = current_label.getElementsByTagName ('input');
            if (inputs.length > 0) {
                nice_label (current_label);
            } else {
                var corresponding_input;
                var el_id;
                var input_parent;
                // if label doesn't contain input, need to find matching input element
                el_id = current_label.getAttribute ('for');
                if (el_id != '') {
                    corresponding_input = document.getElementById (el_id);
                    if (corresponding_input) {
                        input_parent = corresponding_input.parentNode;
                        // we can't apply a border to an input, so it needs a parent span or div
                        // this will only be applied if the span or div only contains the input element
                        // this of course means that the div or span can't contain whitespace
                        if (input_parent.tagName == 'DIV' || input_parent.tagName == 'SPAN') {
                            if (input_parent.childNodes.length == 1) {
                                nice_label (current_label, input_parent);
                                input_parent.className = 'label_plain';
                            }
                        }
                    }
                }
                
            }
        }
        
    }
    
}

function nice_label (current_label, input_parent) {
    current_label.className = 'label_plain';
    current_label.onmouseover = function () {
        current_label.className = 'label_highlight';
        if (input_parent) {
            input_parent.className = 'label_highlight';
        }
    }
    current_label.onmouseout = function () {
        current_label.className = 'label_plain';
        if (input_parent) {
            input_parent.className = 'label_plain';
        }
    }
    
    if (input_parent) {
        input_parent.onmouseover = function () {
            current_label.className = 'label_highlight';
            input_parent.className = 'label_highlight';
        }
        input_parent.onmouseout = function () {
            current_label.className = 'label_plain';
            input_parent.className = 'label_plain';
        }
    }
}

/**
* Shows an image from an editable column in a pop-up, or a standalone window if the pop-up will be too huge
**/
function show_image (file_mask, width, height) {
    
    // allow the standard hyperlink to operate if the screen is too small to fit the image
    if (height > screen.availHeight || width > screen.availWidth) {
        return true;
    } else {
        var new_window = window.open (
            'image.php?f=' + file_mask,
            '_blank',
            'height=' + height + ',width=' + width + ',channelmode=0,directories=0,fullscreen=0,location=0' +
            ',menubar=0,resizable=0,scrollbars=0,status=0,toolbar=0'
        );
        
        // allow the standard hyperlink to operate if the pop-up failed
        if (new_window) {
            return false;
        } else {
            return true;
        }
    }
    
}

/**
* Provides the pop-up for inline searches
**/
function inline_search (field, table) {
    var url = 'inline_search.php?f=' + field + '&t=' + table;
    var inline_search_window = window.open (
        url,
        'inline_search_win',
        'width=520,height=640,channelmode=no,directories=no,fullscreen=no,' +
        'menubar=no,resizable=yes,scrollbars=yes,status=no,toolbar=no'
    );
    if (inline_search_window) {
        inline_search_window.focus ();
    } else {
        window.alert ('You must enable pop-ups to use this feature');
    }
}

/**
* Clears existing inline search data
**/
function inline_search_clear (field) {
    var id = document.getElementById (field + '_search_val');
    var key = document.forms.main_form.elements[field];
    if (id && key) {
        id.innerHTML = 'No value';
        key.value = '';
    }
}


/**
* Does a dump of an object to an alert
**/
function var_dump (operand, is_sub) {
    var alert_text = '';
    var op_type = typeof (operand)
    alert_text += op_type + ':' + (is_sub? ' ': '\n');
    
    if (op_type == 'object') {
        for (var id in operand) {
            if (typeof (operand[id]) == 'function') {
                alert_text += id + ' = function...\n';
            } else {
                alert_text += id + ' = ' + operand[id] + "\n";
            }
        }
    } else {
        alert_text += String (operand);
    }
    
    window.alert (alert_text);
    
}

function show_sublist (node) {
    if (node.getElementsByTagName) {
        var els = node.getElementsByTagName ('ul');
        if (els.length > 0) {
            var i;
            var new_class = 'display-none';
            for (i = 0; i < els.length; i++) {
                if (els.item (i).className == 'display-none') {
                    new_class = '';
                    break;
                }
            }
            for (i = 0; i < els.length; i++) {
                els.item (i).className = new_class;
            }
        }
    }
}
