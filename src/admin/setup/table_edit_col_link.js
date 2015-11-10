/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

var nodes = [];
var initing = false;

/* base class */
function OrderedItem () {
    
    // node properties
    this.parentList = null;
    this.parentListRedraw = null;
    
    this.getValue = function () { }
    this.getHiddenValue = function () { }
    this.hiddenName = 'list';
    
    // draw this node
    this.draw = function (parentId) {
        index = this.getIndex();
        var item = this;
        
        // create the node
        node = document.createElement('div');
        
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
        child.className = 'float-right';
        node.appendChild(child);
            
        // up button
        if (index > 0) {
            child = document.createElement('img');
            child.src = up_image;
            child.className = 'float-right';
            child.onclick = function() { item.moveUp(); };
            node.appendChild(child);
        }
        
        // delete button
        child = document.createElement('input');
        child.value = '-';
        child.type = 'button';
        child.onclick = function() { item.destroy(); };
        node.appendChild(child);
        
        // hidden field
        child = document.createElement('input');
        child.name = this.hiddenName + '[]';
        child.value = this.getHiddenValue();
        child.type = 'hidden'
        node.appendChild(child);
        
        // text
        child = document.createTextNode(' ' + this.getValue());
        node.appendChild(child);
        
        // show the node
        desc = document.getElementById(parentId);
        desc.appendChild(node);
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


/*** text descriptor ***/
function TextDescriptor (value) {
    // inerhit
    this.inheritFrom = OrderedItem;
    this.inheritFrom();
    
    // assign us to the list
    this.assignParent(nodes, draw_nodes);
    
    // overwrite some stuff
    this.hiddenName = 'desc';
    this.getHiddenValue = function () {
        return 't:' + this.value;
    }
    this.getValue = function () {
        return '"' + this.value + '"';
    }
    
    // set our value
    this.value = value;
}

/*** column descriptor ***/
function ColumnDescriptor (value) {
    // inerhit
    this.inheritFrom = OrderedItem;
    this.inheritFrom();
    
    // assign us to the list
    this.assignParent(nodes, draw_nodes);
    
    // overwrite some stuff
    this.hiddenName = 'desc';
    this.getHiddenValue = function () {
        return 'c:' + this.value;
    }
    this.getValue = function () {
        return this.value;
    }
    
    // set our value
    this.value = value;
}


/* add a desc */
function add_desc(node_type, node_value) {
    if (node_type == 'c') {
        node = new ColumnDescriptor(node_value);
    } else {
        node = new TextDescriptor(node_value);
    }
    if (! initing) {
        draw_nodes();
    }
}


/* draw the nodes */
function draw_nodes() {
    desc = document.getElementById('describe_with');
    desc.style.display = 'block';
    
    // remove nodes
    while (desc.firstChild) {
        desc.removeChild(desc.firstChild);
    };
    
    desc = document.getElementById('describe_none');
    if (nodes.length == 0) {
        desc.style.display = 'block';
        
    } else {
        // hide message
        desc.style.display = 'none';
        
        // draw the nodes
        //alert(nodes);
        for (var i = 0; i < nodes.length; i++) {
            //alert(nodes[i]);
            nodes[i].draw('describe_with');
        }
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
