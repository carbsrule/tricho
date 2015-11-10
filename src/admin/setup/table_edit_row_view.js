/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

String.prototype.trim = function() { return this.replace(/^\s+|\s+$/g, ''); };


var nodes = [];
var initing = false;

/* base class */
function OrderedItem (checkboxes) {
    
    // node properties
    this.parentList = null;
    this.parentListRedraw = null;
    
    this.getValue = function () { }
    this.getHiddenValue = function () { }
    this.hiddenName = 'desc';
    this.getExtraHiddenValues = function () { }
    
    // draw this node
    this.draw = function (parent_node, index) {
        index = this.getIndex();
        var item = this;
        
        // create the node
        node = document.createElement('tr');
        
        // delete button
        child = document.createElement('input');
        td = document.createElement('td');
        child.value = '-';
        child.type = 'button';
        child.onclick = function() { item.destroy(); };
        td.appendChild(child);
        td.style.width = '30px'
        node.appendChild(td);
        
        // hidden field
        child = document.createElement('input');
        td = document.createElement('td');
        child.name = this.hiddenName + '[' + index + ']';
        child.value = this.getHiddenValue();
        child.type = 'hidden'
        td.appendChild(child);
        
        var extra_hidden = this.getExtraHiddenValues();
        for (var key in extra_hidden) {
            child = document.createElement('input');
            child.name = this.hiddenName + '_' + key + '[' + index + ']';
            child.value = extra_hidden[key];
            child.type = 'hidden';
            td.appendChild(child);
        }
        
        // text
        td.appendChild(this.getValue ());
        td.className = 'desc-column-name';
        node.appendChild(td);
        
        
        // add view checkbox
        check = document.createElement('input');
        td = document.createElement('td');
        td.className = 'desc-add-check';
        check.type = 'checkbox';
        check.value = '1';
        check.checked = checkboxes[0];
        check.name = this.hiddenName + '_add[' + index + ']';
        td.appendChild(check);
        node.appendChild(td);
        
        // edit view checkbox
        check = document.createElement('input');
        td = document.createElement('td');
        td.className = 'desc-edit-check';
        check.type = 'checkbox';
        check.value = '1';
        check.checked = checkboxes[1];
        check.name = this.hiddenName + '_edit_view[' + index + ']';
        td.appendChild(check);
        node.appendChild(td);
        
        // edit change checkbox
        td = document.createElement('td');
        td.className = 'desc-edit-check';
        if (checkboxes[2] != 'dontshow') {
            check = document.createElement('input');
            check.type = 'checkbox';
            check.value = '1';
            check.checked = checkboxes[2];
            check.name = this.hiddenName + '_edit_change[' + index + ']';
            td.appendChild(check);
        }
        node.appendChild(td);
        
        // up & down button td
        td = document.createElement('td');
        td.className = 'desc-up-down';
        node.appendChild(td);
        
        // up button
        if (index > 0) {
            child = document.createElement('img');
            child.src = up_image;
            //child.className = 'float-right';
            child.onclick = function() { item.moveUp(); };
            td.appendChild(child);
        }
        
        // down button
        if (index < (this.parentList.length - 1)) {
            child = document.createElement('img');
            child.src = down_image;
            child.onclick = function() { item.moveDown() };
        } else {
            child = document.createElement('img');
            child.src = '../images/blank.gif';
            child.width = 16;
            child.height = 16;
        }
        td.appendChild(child);
        
        
        // show the node
        parent_node.appendChild(node);
    }
    
    // assign us a parent
    this.assignParent = function (list, redrawCallback) {
        this.parentList = list;
        this.parentListRedraw = redrawCallback;
        if (this.getIndex() == -1) {
            this.parentList.push(this);
        }
    }
    
    // move this node up in the list
    this.moveUp = function () {
        var index = this.getIndex();
        
        temp = this.parentList[index - 1];
        this.parentList[index - 1] = this;
        this.parentList[index] = temp;
        
        this.parentListRedraw();
    }
    
    // move this node down in the list
    this.moveDown = function () {
        index = this.getIndex();
        
        temp = this.parentList[index + 1];
        this.parentList[index + 1] = this;
        this.parentList[index] = temp;
        
        this.parentListRedraw();
    }
    
    // get the index of this item in the parent list
    this.getIndex = function () {
        if (this.parentList == null) return 0;
        for (var x = 0; x < this.parentList.length; x++) {
            if (this === this.parentList[x]) {
                return x;
            }
        }
        return -1;
    }
    
    // destroy this node
    this.destroy = function () {
        this.parentList.splice(this.getIndex(), 1);
        
        this.parentListRedraw();
    }
}





/*** column descriptor ***/
function ColumnDescriptor (value, checkboxes) {
    // inerhit
    this.inheritFrom = OrderedItem;
    this.inheritFrom(checkboxes);
    
    // assign us to the list
    this.assignParent(nodes, draw_nodes);
    
    // overwrite some stuff
    this.getHiddenValue = function () {
        return 'c!!!' + this.value;
    }
    this.getValue = function () {
        var span = document.createElement ('span');
        span.appendChild (document.createTextNode (' ' + this.value));
        
        if (link_info[this.value] != null) {
            var em = document.createElement ('em');
            em.appendChild (document.createTextNode (link_info[this.value]));
            span.appendChild (em);
        }
        
        return span;
    }
    
    // set our value
    this.value = value;
}


/*** heading descriptor ***/
function HeadingDescriptor (value, checkboxes) {
    // inerhit
    checkboxes[2] = 'dontshow';
    this.inheritFrom = OrderedItem;
    this.inheritFrom(checkboxes);
    
    // assign us to the list
    this.assignParent(nodes, draw_nodes);
    
    // overwrite some stuff
    this.getHiddenValue = function () {
        return 'h!!!' + this.value;
    }
    this.getValue = function () {
        var strong = document.createElement ('strong');
        strong.appendChild (document.createTextNode (' ' + this.value));
        return strong;
    }
    
    // set our value
    this.value = value;
}


/*** function descriptor ***/
function FunctionDescriptor (value, checkboxes) {
    // inerhit
    checkboxes[2] = 'dontshow';
    this.inheritFrom = OrderedItem;
    this.inheritFrom(checkboxes);
    
    // assign us to the list
    this.assignParent(nodes, draw_nodes);
    
    // overwrite some stuff
    this.getHiddenValue = function () {
        return 'f!!!' + this.value[0];
    }
    this.getExtraHiddenValues = function () {
        return { 'code' : this.value[1] };
    }
    this.getValue = function () {
        var em = document.createElement ('i');
        em.appendChild (document.createTextNode (' ' + this.value[0] + ': ' + this.value[1]));
        return em;
    }
    
    // set our value
    this.value = value;
}


/*** include descriptor ***/
function IncludeDescriptor (value, checkboxes) {
    // inerhit
    checkboxes[2] = 'dontshow';
    this.inheritFrom = OrderedItem;
    this.inheritFrom(checkboxes);
    
    // assign us to the list
    this.assignParent(nodes, draw_nodes);
    
    // overwrite some stuff
    this.getHiddenValue = function () {
        return 'i!!!' + this.value[0];
    }
    this.getExtraHiddenValues = function () {
        return { 'file' : this.value[1], 'pass' : this.value[2] };
    }
    this.getValue = function () {
        var span = document.createElement ('span');
        span.style.color = "#999";
        if (this.value[2] == '') {
            span.appendChild (document.createTextNode (' ' + this.value[0] + ': include "' + this.value[1] + '"'));
        } else {
            span.appendChild (document.createTextNode (' ' + this.value[0] + ': include "' + this.value[1] + '" and pass "' + this.value[2] + '"'));
        }
        return span;
    }
    
    // set our value
    this.value = value;
}


/* add a desc */
function add_desc(node_type, node_value, checkboxes) {
    switch (node_type) {
        case 'c':
            node = new ColumnDescriptor(node_value, checkboxes);
            break;
            
        case 'h':
            if (node_value.trim() == '') { alert ('Invalid heading text'); return; }
            node = new HeadingDescriptor(node_value, checkboxes);
            break;
            
        case 'f':
            if (node_value[0].trim() == '') { alert ('Invalid function name'); return; }
            if (node_value[1].trim() == '') { alert ('Invalid function code'); return; }
            node = new FunctionDescriptor(node_value, checkboxes);
            break;
            
        case 'i':
            if (node_value[1].trim() == '') { alert ('Invalid include filename'); return; }
            node = new IncludeDescriptor(node_value, checkboxes);
            break;
    }
    if (! initing) {
        draw_nodes();
    }
}


/* draw the nodes */
function draw_nodes() {
    var desc_with = document.getElementById('describe_with');
    
    // if there are no nodes, show a cute message
    desc = document.getElementById('describe_none');
    if (nodes.length == 0) {
        desc.style.display = '';
        desc_with.parentNode.removeChild (desc_with);
        
    } else {
        // hide message and show table
        var new_desc = document.createElement ('table');
        
        desc.style.display = 'none';
        
        
        create_header(new_desc);
        
        // draw the nodes
        for (var i = 0; i < nodes.length; i++) {
            nodes[i].draw(new_desc, i);
        }
        
        if (desc_with == null) {
            desc.parentNode.insertBefore (new_desc, desc_with);
        } else {
            desc_with.parentNode.replaceChild (new_desc, desc_with);
        }
        new_desc.id = 'describe_with';
    }
}

function parent_status () {
    var parent = document.getElementById ('is_parent');
    var alt_eng_name = document.getElementById ('alt');
    
    if (parent.checked) {
        alt_eng_name.disabled = false;
    } else {
        alt_eng_name.disabled = true;
    }
    
}

function create_header (table_node) {
    // ROW 1
    tr = document.createElement ('tr');
    tr.className = 'desc-header';
    tr.appendChild (document.createElement ('td')); //[-] button
    
    td = document.createElement ('td');
    td.appendChild (document.createTextNode ('Field name and details'));
    td.style.textAlign='left';
    td.setAttribute('rowspan', 2);
    tr.appendChild (td);
    
    td = document.createElement ('td');
    td.appendChild (document.createTextNode ('Add'));
    td.setAttribute('rowspan', 2);
    td.className = 'desc-add-check';
    tr.appendChild (td);

    td = document.createElement ('td');
    td.appendChild (document.createTextNode ('Edit'));
    td.setAttribute('colspan', 2);
    tr.appendChild (td);
    
    td = document.createElement ('td'); //up & down buttons
    td.setAttribute('rowspan', 2);
    td.className = 'desc-up-down';
    tr.appendChild (td);
    table_node.appendChild (tr);
    
    
    // ROW 2
    tr = document.createElement ('tr');
    tr.className = 'desc-header';
    tr.style.borderBottom = '1px #CCC solid;';
    tr.appendChild (document.createElement ('td')); //[-] button
    
    
    td = document.createElement ('td');
    td.appendChild (document.createTextNode ('view'));
    tr.appendChild (td);
    
    td = document.createElement ('td');
    td.appendChild (document.createTextNode ('change'));
    tr.appendChild (td);
    table_node.appendChild (tr);
}
