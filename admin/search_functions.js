/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/*
Functions and variables to support the new filter system
*/
var TYPE_TEXT = 0; // this is the default type
var TYPE_NUMERIC = 1;
var TYPE_DATETIME = 2;
var TYPE_LINKED = 3;
var TYPE_BINARY = 4;
var TYPE_ENUM = 5;

var COND_LIKE = 1;
var COND_EQ = 2;
var COND_STARTS_WITH = 3;
var COND_ENDS_WITH = 4;
var COND_BETWEEN = 5;
var COND_LT = 6;
var COND_GT = 7;
var COND_LT_OR_EQ = 8;
var COND_GT_OR_EQ = 9;
var COND_NOT_LIKE = 10;
var COND_NOT_EQ = 11;
var COND_IS = 12;

var cond_types = new Array ();
cond_types[COND_LIKE] = 'Contains';
cond_types[COND_EQ] = 'Exact Match';
cond_types[COND_STARTS_WITH] = 'Starts with';
cond_types[COND_ENDS_WITH] = 'Ends with';
cond_types[COND_BETWEEN] = 'Between';
cond_types[COND_LT] = 'Less than';
cond_types[COND_GT] = 'Greater than';
cond_types[COND_LT_OR_EQ] = 'At most';
cond_types[COND_GT_OR_EQ] = 'At least';
cond_types[COND_NOT_LIKE] = "Doesn't contain";
cond_types[COND_NOT_EQ] = 'Is not exactly';
cond_types[COND_IS] = 'Is';

var search_cond_next = 0;

/**
 * Show or hide the search box
*/
function display_search (display) {
    var node;
    var search_image;
    if (display) {
        node = document.getElementById ('search');
        node.style.display = 'block';
        node = document.getElementById ('search_buttons');
        node.style.display = 'block';
        node = document.getElementById ('search_container');
        node.style.borderBottomStyle = 'groove';
        node.style.borderTopStyle = 'groove';
        node.style.borderLeftStyle = 'groove';
        node.style.borderRightStyle = 'groove';
    } else {
        node = document.getElementById ('search');
        node.style.display = 'none';
        node = document.getElementById ('search_buttons');
        node.style.display = 'none';
        node = document.getElementById ('search_container');
        node.style.borderBottomStyle = 'none';
        node.style.borderTopStyle = 'none';
        node.style.borderLeftStyle = 'none';
        node.style.borderRightStyle = 'none';
    }
    node = document.getElementById ('search_container').getElementsByTagName ('legend').item (0);
    search_image = node.getElementsByTagName ('img').item (0);
    if (display) {
        search_image.src = search_image.src.replace ('closed', 'open');
    } else {
        search_image.src = search_image.src.replace ('open', 'closed');
    }
    node.onclick = function () {display_search (!display)};
}

/**
 * Force a redraw of all search conditions
*/
function add_search_conditions (conditions) {
    search_cond_next = conditions.length;
    for (var i = 0; i < conditions.length; i++) {
        conditions[i].number = i;
        add_search_condition (conditions[i], i);
    }
}

/**
 * Updates the condition type list for a condition
*/
function update_cond_type_list (cond) {
    // determine possible array
    var possible = cond.valid_types ();
    var list = cond.type_list;
    
    // null and not null
    if (fields[cond.field][2]) {
        possible.push(COND_IS);
    }
    
    // update items
    list = cond.type_list;
    list.options.length = 0;
    var y = 0;
    for (var x = 0; x < possible.length; x++) {
        list.options[y++] = new Option (cond_types[possible[x]], possible[x]);
        if (cond.type == possible[x]) {
            list.value = possible[x];
        }
    }
}

/**
 * Add a search condition
*/
function add_search_condition (cond, cond_num) {
    var list;
    var opt;
    var match;
    var div;
    var button;
    
    var outer_div = document.getElementById ('search');
    if (outer_div) {
        match = false;
        div = document.createElement ('div');
        
        button = create_element('input', {'type':'button','value':'-'});
        button.onclick = function () {
            button.parentNode.parentNode.removeChild (button.parentNode);
            conditions.splice (cond_num, 1);
        }
        div.appendChild (button);
        
        div.appendChild (document.createTextNode (' '));
        
        list = create_select ({'name':'field['+ cond_num + ']'});
        div.appendChild (list);
        div.appendChild (document.createTextNode (' '));
        
        for (key in fields) {
            opt = document.createElement ('option');
            opt.setAttribute ('value', key);
            opt.appendChild (document.createTextNode (fields[key][0]));
            list.appendChild (opt);
            if (key == cond.field) {
                list.value = cond.field;
            }
        }
        
        // Table change
        list.onchange = function () {
            // if fieldtype is different, create a new condition
            if (cond.fieldType != fields[this.value][1]) {
                var node = cond.type_list; //document.getElementById('condition['+ cond_num + ']');
                var div = cond.div;
                var list = cond.type_list;
                var frm = cond.frm;
                var num = cond.number;
                cond = new Condition (this.value, node.value);
                cond.div = div;
                cond.type_list = list;
                cond.frm = frm;
                cond.number = num;
                
            } else {
                // or just update
                cond.field = this.value;
                cond.save();
            }
            
            // do the condition type list
            update_cond_type_list (cond);
            cond.type = cond.type_list.value;
                
            // draw
            cond.draw();
        }
        
        /*
        list = document.createElement ('select');
        list.setAttribute ('name', 'condition');
        div.appendChild (list);
        
        for (key in cond_types) {
            opt = document.createElement ('option');
            opt.setAttribute ('value', key);
            opt.appendChild (document.createTextNode (cond_types[key]));
            list.appendChild (opt);
            if (key == cond.type) {
                opt.setAttribute ('selected', 'selected');
            }
        }
        */
        list = create_select ({'name':'condition['+ cond_num + ']'}, cond_types, cond.type);
        list.onchange = function () {
            if (cond.type == COND_IS) {
                cond.values = ['',''];
            }
            cond.type = this.value;
            cond.draw();
        }
        cond.type_list = list;
        update_cond_type_list (cond);
        div.appendChild (list);
        div.appendChild (document.createTextNode (' '));


        div.appendChild(cond.draw());
        cond.frm = outer_div.parentNode.parentNode;
        
        outer_div.appendChild (div);
    }
}

/**
 * Add an empty search condition
*/
function add_empty_search_condition () {
    var c = new Condition (default_field, COND_LIKE);
        
    // push and add
    conditions.push (c);
    add_search_condition (c, search_cond_next);
    search_cond_next++;
}

/**
 * The condition class
*/
function Condition (field, type, value1, value2) {
    // common properties
    this.field = field;
    this.type = type;
    this.div = null;
    this.fieldType = fields[field][1];
    this.type_list = null;
    this.number = search_cond_next;
    this.frm = null;
    
    if (value1 == null) { value1 = ''; }
    if (value2 == null) { value2 = ''; }
    
    // the field type determines what is extended
    switch (this.fieldType) {
        case TYPE_TEXT:
            this.superClass = TextCondition; break;
        case TYPE_NUMERIC:
            this.superClass = NumericCondition; break;
        case TYPE_DATETIME:
            this.superClass = DateTimeCondition; break;
        case TYPE_LINKED:
            this.superClass = LinkedCondition; break;
        case TYPE_ENUM:
            this.superClass = EnumCondition; break;
        case TYPE_BINARY:
            this.superClass = BinaryCondition; break;
    }
    
    // do the extention
    this.superClass([value1,value2]);
    
    
    /* DRAW FUNCTION */
    this.draw = function() {
        // create or empty the div
        if (this.div == null) {
            this.div = document.createElement('span');
        } else {
            while (this.div.firstChild) {
                this.div.removeChild(this.div.firstChild);
            };
        }
        
        // dont draw for null or not null
        if (this.type == COND_IS) {
            this.buildIs();
            return this.div;
        }
        
        if (this.preDraw != null) { this.preDraw(); }
        
        // draw section 1
        this.div.appendChild(this.drawSection(0));
        
        // draw section 2 for BETWEEN
        if (this.type == COND_BETWEEN) {
            this.div.appendChild(document.createTextNode(' and '));
            this.div.appendChild(this.drawSection(1));
        }
        
        // return
        return this.div;
    }
    
    /* SAVE FUNCTION */
    this.save = function() {
        this.saveSection(0);
        
        if (this.type == COND_BETWEEN) {
            this.saveSection(1);
        }
    }

    /* NULL AND NOT NULL STUFF */
    this.buildIs = function() {
        span = document.createElement('span');
        if (this.values[0] == 'not null') {
            span.appendChild (create_element ('input', {'name':'val[' + this.number + '][0]', 'id':'val_null_' + this.number, 'type':'radio', 'value':'null'}));
            lab = create_element ('label', {'for':'val_null_' + this.number});
            span.appendChild (lab);
            lab.appendChild(document.createTextNode('Empty'));
            
            span.appendChild (create_element ('input', {'name':'val[' + this.number + '][0]', 'id':'val_notnull_' + this.number, 'type':'radio', 'value':'not null', 'checked':'checked'}));
            lab = create_element ('label', {'for':'val_notnull_' + this.number});
            span.appendChild (lab);
            lab.appendChild(document.createTextNode('Not Empty'));
            
        } else {
            span.appendChild (create_element ('input', {'name':'val[' + this.number + '][0]', 'id':'val_null_' + this.number, 'type':'radio', 'value':'null', 'checked':'checked'}));
            lab = create_element ('label', {'for':'val_null_' + this.number});
            span.appendChild (lab);
            lab.appendChild(document.createTextNode('Empty'));
            
            span.appendChild (create_element ('input', {'name':'val[' + this.number + '][0]', 'id':'val_notnull_' + this.number, 'type':'radio', 'value':'not null'}));
            lab = create_element ('label', {'for':'val_notnull_' + this.number});
            span.appendChild (lab);
            lab.appendChild(document.createTextNode('Not Empty'));
        }
        this.div.appendChild(span)
    }
}


/**
 * Text Conditions
*/
function TextCondition (values) {
    this.values = values;

    // Draw fields
    this.drawSection = function(sectionNum) {
        if (this.values[sectionNum] == null) { this.values[sectionNum] = ''; }
        
        span = document.createElement('span');
        span.appendChild (create_element ('input', {'name':'val[' + this.number + '][' + sectionNum + ']', 'type':'text', 'value':this.values[sectionNum]}));
        
        return span;
    }
    
    // Save values from fields
    this.saveSection = function(sectionNum) {
        var inputs = this.frm.elements;
        var node = inputs['val[' + this.number + '][' + sectionNum + ']'];
        this.values[sectionNum] = node.value;
    }
    
    // Condition types that are valid
    this.valid_types = function() {
        return [
            COND_LIKE,
            COND_EQ,
            COND_STARTS_WITH,
            COND_ENDS_WITH,
            COND_BETWEEN,
            COND_LT,
            COND_GT,
            COND_LT_OR_EQ,
            COND_GT_OR_EQ,
            COND_NOT_LIKE,
            COND_NOT_EQ
        ];
    }
}

/**
 * Date or Time Conditions
*/
function DateTimeCondition (values) {
    // crude converter
    this.parseValue = function(val) {
        if ((val == 'null') || (val == 'not null')) { return val; }
        var ret = [];
        if ((val == null) || (val == '')) { return ret; }
        var parts = val.split(' ');
        var index = 0;
        if (parts[0].charAt(4) == '-') {
            bits = parts[0].split('-');
            ret.push (parseInt(bits[2],10), parseInt(bits[1],10), parseInt(bits[0],10));
            index++;
        } else { ret.push ('','',''); }
        if (parts.length > index) {
            bits = parts[index].split(':');
            hour = parseInt(bits[0],10);
            if (isNaN(hour)) {
                ampm = 0;
            } else {
                if (hour == 0) {
                    hour = 12; ampm = 1;
                } else if (hour == 12) {
                    hour = 12; ampm = 2;
                } else if (hour > 12) {
                    hour -= 12; ampm = 2;
                } else {
                    ampm = 1;
                }
            }
            min = parseInt(bits[1],10);
            if (isNaN(min)) { min = ''; } else { if (min < 10) min = '0' + min; }
            ret.push(hour, min, ampm);
        }
        return ret;
    }
    
    // parse input values
    this.values = [this.parseValue(values[0]), this.parseValue(values[1])];
    
    // before a draw
    this.preDraw = function() {
        field = fields[this.field];
        
        this.showTime = field[3];
        this.showDate = field[4];
        if (this.drawDate) {
            this.yearMin = field[5];
            this.yearMax = field[6] + 1;
        }
    }
    
    // do the draw
    this.drawSection = function(sectionNum) {
        this.span = document.createElement('span');
        if (this.showDate) { this.drawDate (sectionNum); }
        if (this.showDate && this.showTime) {
            this.span.appendChild (document.createTextNode (' \u00A0 '));
        }
        if (this.showTime) { this.drawTime (sectionNum); }
        return this.span;
    }
    
    // draw the date component
    this.drawDate = function(sectionNum) {
        // arrays for day, month and year
        // TODO: move day and month to shared arrays
        days = ['D']; for (x = 1; x < 32; x++) { days.push (x); }
        months = ['M', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'June', 'July', 'Aug', 'Sept', 'Oct', 'Nov', 'Dec'];
        years = ['Y']; for (x = this.yearMin; x < this.yearMax; x++) { years[x] = x; }
        
        // day
        node = create_select ({'name':'val['+ this.number + '][' + sectionNum + '][d]'}, days, this.values[sectionNum][0]);
        this.span.appendChild (node);
        this.span.appendChild (document.createTextNode (' / '));

        // month
        node = create_select ({'name':'val['+ this.number + '][' + sectionNum + '][m]'}, months, this.values[sectionNum][1]);
        this.span.appendChild (node);
        this.span.appendChild (document.createTextNode (' / '));

        // year
        /*var sel_item = 0;
        for (var x = 0; x < years.length; x++) {
            if (this.values[sectionNum][2] == years[x]) {
                sel_item = x;
                break;
            }
        }*/
        node = create_select ({'name':'val['+ this.number + '][' + sectionNum + '][y]'}, years, this.values[sectionNum][2]);
        this.span.appendChild (node);
    }
    
    // draw the time component
    this.drawTime = function(sectionNum) {
        // arrays for hour and am/pm
        // TODO: move to shared arrays
        hours = ['H']; for (x = 1; x < 13; x++) { hours.push (x); }
        ampm = ['', 'AM', 'PM'];
        
        // hour
        node = create_select ({'name':'val['+ this.number + '][' + sectionNum + '][hr]'}, hours, this.values[sectionNum][3]);
        this.span.appendChild (node);
        this.span.appendChild (document.createTextNode (':'));
        
        // min
        if (this.values[sectionNum][4] == null) { this.values[sectionNum][4] = ''; }
        node = create_element ('input', {'name':'val[' + this.number + '][' + sectionNum + '][mn]', 'type':'text', 'size':2, 'value':this.values[sectionNum][4]})
        this.span.appendChild (node);
        this.span.appendChild (document.createTextNode (' '));
        
        // am or pm
        node = create_select ({'name':'val['+ this.number + '][' + sectionNum + '][AP]'}, ampm , this.values[sectionNum][5]);
        this.span.appendChild (node);
    }
    
    // save
    this.saveSection = function(sectionNum) {
        var inputs = this.frm.elements;
        var node = null;
        
        if (this.showDate) {
            // day
            node = inputs['val[' + this.number + '][' + sectionNum + '][d]'];
            this.values[sectionNum][0] = node.value;
            
            // month
            node = inputs['val[' + this.number + '][' + sectionNum + '][m]'];
            this.values[sectionNum][1] = node.value;
            
            // year
            node = inputs['val[' + this.number + '][' + sectionNum + '][y]'];
            this.values[sectionNum][2] = node.options[node.selectedIndex].text;
        }
        
        if (this.showTime) {
            // hour
            node = inputs['val[' + this.number + '][' + sectionNum + '][hr]'];
            this.values[sectionNum][3] = node.value;
            
            // min
            node = inputs['val[' + this.number + '][' + sectionNum + '][mn]'];
            this.values[sectionNum][4] = node.value;
            
            // am/pm
            node = inputs['val[' + this.number + '][' + sectionNum + '][AP]'];
            this.values[sectionNum][5] = node.value;
        }
    }
    
    // valid types
    this.valid_types = function() {
        this.preDraw ();
        if (this.showTime) {
            // if there is a time component (TIME, DATETIME), we can't do "exact match" 
            return [COND_LIKE,COND_BETWEEN,COND_LT,COND_GT,COND_LT_OR_EQ,COND_GT_OR_EQ,COND_NOT_LIKE];
        } else {
            // DATE. do it all
            return [COND_LIKE, COND_EQ, COND_BETWEEN, COND_LT, COND_GT, COND_LT_OR_EQ, COND_GT_OR_EQ, COND_NOT_LIKE, COND_NOT_EQ];
        }
    }
}


/**
 * Numeric Conditions
*/
function NumericCondition (values) {
    this.values = values;

    // Draw fields
    this.drawSection = function(sectionNum) {
        if (this.values[sectionNum] == null) { this.values[sectionNum] = ''; }
        
        span = document.createElement('span');
        span.appendChild (create_element ('input', {'name':'val[' + this.number + '][' + sectionNum + ']', 'type':'text', 'value':this.values[sectionNum]}));
        
        return span;
    }
    
    // Save values from fields
    this.saveSection = function(sectionNum) {
        var inputs = this.frm.elements;
        var node = inputs['val[' + this.number + '][' + sectionNum + ']'];
        this.values[sectionNum] = node.value;
    }
    
    // Condition types that are valid
    this.valid_types = function() { return [COND_EQ,COND_NOT_EQ,COND_BETWEEN,COND_LT,COND_LT_OR_EQ,COND_GT,COND_GT_OR_EQ]; }
}

/**
 * Binary Conditions
*/
function BinaryCondition (values) {
    this.values = values;
    
    this.drawSection = function(sectionNum) {
        span = document.createElement('span');
        if (this.values[0] == true) {
            // yes checked
            span.appendChild (create_element ('input', {'name':'val[' + this.number + '][' + sectionNum + ']', 'id':'val_y_' + this.number + '_' + sectionNum, 'type':'radio', 'value':'1', 'checked':'checked'}));
            lab = create_element ('label', {'for':'val_y_' + this.number + '_' + sectionNum});
            span.appendChild (lab);
            lab.appendChild(document.createTextNode('Yes'));
            
            span.appendChild (create_element ('input', {'name':'val[' + this.number + '][' + sectionNum + ']', 'id':'val_n_' + this.number + '_' + sectionNum, 'type':'radio', 'value':'0'}));
            lab = create_element ('label', {'for':'val_n_' + this.number + '_' + sectionNum});
            span.appendChild (lab);
            lab.appendChild(document.createTextNode('No'));
            
        } else {
            // no checked
            span.appendChild (create_element ('input', {'name':'val[' + this.number + '][' + sectionNum + ']', 'id':'val_y_' + this.number + '_' + sectionNum, 'type':'radio', 'value':'1'}));
            lab = create_element ('label', {'for':'val_y_' + this.number + '_' + sectionNum});
            span.appendChild (lab);
            lab.appendChild(document.createTextNode('Yes'));
            
            span.appendChild (create_element ('input', {'name':'val[' + this.number + '][' + sectionNum + ']', 'id':'val_n_' + this.number + '_' + sectionNum, 'type':'radio', 'value':'0', 'checked':'checked'}));
            lab = create_element ('label', {'for':'val_n_' + this.number + '_' + sectionNum});
            span.appendChild (lab);
            lab.appendChild(document.createTextNode('No'));
        }
        

        
        return span;
    }
    
    this.saveSection = function(sectionNum) {
        var inputs = this.frm.elements;
        var node = inputs['val[' + this.number + '][' + sectionNum + ']'];
        this.values[0] = node.checked;
    }
    
    this.valid_types = function() { return [COND_EQ]; }
}


/**
 * Linked Conditions
*/
function LinkedCondition (values) {
    this.values = values;
    
    this.drawSection = function(sectionNum) {
        var span = document.createElement('span');
        
        span.appendChild(create_select({
            'id':'val_' + this.number,
            'name':'val[' + this.number + '][' + sectionNum + ']'}, {0:'Loading...'}));
        
        var url = 'search_ajax_get_data.php?t=' + fields[this.field][3] + '&c=' + this.field
        var ajax_handler = new SearchSelectReplacementHandler('val_' + this.number);
        ajax_handler.selected_value = this.values[sectionNum];
        
        queue.request ('get', url, ajax_handler, null, false);
        
        return span;
    }
    
    this.saveSection = function(sectionNum) {
    }
    
    this.valid_types = function() { return [COND_EQ, COND_NOT_EQ]; }
}


function EnumCondition(values) {
    this.values = values;
    
    this.drawSection = function(sectionNum) {
        var span = document.createElement('span');
        var select = create_select(
            {'id':'val_' + this.number, 'name':'val[' + this.number + ']'},
            fields[this.field][3]
        );
        span.appendChild(select);
        select.value = this.values[0];
        return span;
    }
    
    this.valid_types = function() { return [COND_EQ, COND_NOT_EQ]; }
}

function SearchSelectReplacementHandler (select_node_id) {
    if (select_node_id == null) {
        alert ('You must specify a select node for a SelectReplacementHandler');
        return;
    }
    
    this.select_node_id = select_node_id;
    this.top_option = '-- Select below --';
    this.empty_option = 'Nothing available';
    this.loading_node = null;
    this.selected_value = null;
    
    /**
    * Processes the returned XML DOM nodes
    **/
    this.process = function (top_node) {
        var select_node = document.getElementById(this.select_node_id);
        if (select_node == null) return;
        
        var nodes = top_node.getElementsByTagName ('item');
        if (nodes.length == 0) {
            select_node.options.length = 0;
            select_node.options[0] = new Option (this.empty_option, '');
            
        } else {
            select_node.options.length = 0;
            select_node.options[0] = new Option (this.top_option, '');
            
            for (var x = 0; x < nodes.length; x++) {
                select_node.options[x+1] = new Option (nodes[x].firstChild.nodeValue, nodes[x].getAttribute ('id'));
                
                if (nodes[x].getAttribute ('id') == this.selected_value) {
                    select_node.value = this.selected_value;
                }
            }
            
        }
        
        this._setLoadingMessage('');
    }
    
    
    /**
    * Processes an error. Optional. If omitted, errors are outputted as an alert
    **/
    this.error = function (message) {
        var select_node = document.getElementById(this.select_node_id);
        
        select_node.options.length = 0;
        select_node.options[0] = new Option (this.empty_option, '');
        
        this._setLoadingMessage('ERROR: ' + message);
    }
    
    /**
    * Is fired when a request is put onto the queue. Optional
    **/
    this.onQueue = function () {
        this._setLoadingMessage('Loading...');
    }
    
    /**
    * Sets the loading message
    **/
    this._setLoadingMessage = function (message) {
        if (this.loading_node == null) {
            if (message == '') return;
            
            var select_node = document.getElementById(this.select_node_id);
            if (select_node == null) return;
            
            select_node.options.length = 0;
            select_node.options[0] = new Option (message, '');
        
        } else {
            if (this.loading_node.firstChild == null) {
                this.loading_node.appendChild (document.createTextNode(message));
            } else {
                this.loading_node.firstChild.data = message;
            }
        }
    }
}
