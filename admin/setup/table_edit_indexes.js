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
    this.hiddenName = 'columns';
    
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
                
        // text
        td.appendChild(this.getValue ());
        td.className = 'desc-column-name';
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
function ColumnDescriptor (col_name, is_text, prefix) {
    
    this.inheritFrom = OrderedItem;
    this.inheritFrom();
    
    // if indexing a text column, use the prefix provided
    this.is_text = is_text;
    this.value = col_name;
    if (prefix != null) {
        this.value += '(' + prefix + ')';
    }
    
    // assign us to the list
    this.assignParent(nodes, draw_nodes);
    
    // overwrite some stuff
    this.hiddenName = 'columns';
    this.getHiddenValue = function () {
        return this.value;
    }
    this.getValue = function () {
        return document.createTextNode (this.value);
    }
    
    this.destroy = function () {
        this.parentList.splice(this.getIndex(), 1);
        
        this.parentListRedraw();
        
        // once destroyed, check to see if there are any non-text columns left in the index
        // if there aren't, the FULLTEXT index type can be re-allowed
        var found_text = false;
        if (this.parentList.length == 0) {
            found_text = true;
        } else {
            var column;
            for (var i in this.parentList) {
                column = this.parentList[i];
                if (column.is_text) {
                    found_text = true;
                    break;
                }
            }
        }
        if (found_text) {
            document.getElementById ('index_type')[2].disabled = false;
        }
    }
}

/*
this is called whenever someone clicks the add button - it sets up the new column for the index
if the new column isn't a text column type, then the FULLTEXT index type needs to be disabled
*/
function add_index_col () {
    
    var col_details = document.getElementById ('new_col_name').value.split (':');
    var is_text_col = (col_details[0] == 'text'? true: false);
    var col_name = col_details[1];
    
    var prefix = document.getElementById ('new_col_prefix').value.trim ();
    if (!is_text_col || prefix == '') {
        prefix = null;
    }
    
    var node = new ColumnDescriptor (col_name, is_text_col, prefix);
    
    if (! initing) {
        draw_nodes();
    }
    
    if (!is_text_col) {
        var index_type = document.getElementById ('index_type');
        index_type[2].disabled = true;
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
    
    tr = document.createElement ('tr');
    tr.className = 'desc-header';
    tr.appendChild (document.createElement ('td')); //[-] button

    td = document.createElement ('td');
    td.appendChild (document.createTextNode ('Column'));
    td.style.textAlign = 'left';
    tr.appendChild (td);
    
    td = document.createElement ('td'); //up & down buttons
    td.appendChild (document.createTextNode ('Order'));
    td.className = 'desc-up-down';
    tr.appendChild (td);
    table_node.appendChild (tr);
    
}

function update_index_col_options () {
    // disable prefix for non-text columns
    
    var col_details = document.getElementById ('new_col_name').value.split (':');
    
    if (col_details[0] == 'text') {
        document.getElementById ('new_col_prefix').disabled = false;
    } else {
        document.getElementById ('new_col_prefix').disabled = true;
    }
    
}
