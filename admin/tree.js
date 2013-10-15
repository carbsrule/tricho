/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

var levelWidth = 24;
var roots = new Array ();
var currentNodes;

var depth = 0;
var max_depth = 0;

/**
* @param string name: a name to give the tree, used in all the IDs of its nodes, e.g. 'Parent'
* @param array    roots: the treeNode objects that sit at the root of the tree
* @param string type: determines the action when you click a node:
*     'select' saves the node's ID in a hidden field
*     'url' takes the user to a URL that incorporates the node's ID
* @param string option: an additional parameter, depending on type
*     for 'select': an eval string that can be used to fetch the form element in which to store the chosen
*         node's ID, e.g. 'document.forms.main_form.Tree1'
*     for 'url': a partial URL onto which the ID can be appended, e.g. 'main_edit.php?t=TestTree&id='
**/
function treeDisplay (name) {
    
    this.name = name;
    this.roots = [];
    this.orderable = false;
    
    this.display = function (type, option) {
        
        var root, currentElement, old_tree, new_tree, display_response, row_num;
        new_tree = document.createElement ('div');
        new_tree.className = 'tree_display';
        row_num = 0;
        for (var i = 0; i < this.roots.length; i++) {
            display_response = this.roots[i].display (row_num, type, option);
            root = display_response[0];
            row_num = display_response[1];
            for (var j = 0; j < root.childNodes.length; j++) {
                new_tree.appendChild (root.childNodes[j]);
                new_tree.appendChild (document.createTextNode ("\n"));
            }
        }
        old_tree = document.getElementById ('tree_' + name);
        old_style = old_tree.getAttribute ('style');
        old_tree.parentNode.replaceChild (new_tree, old_tree);
        new_tree.setAttribute ('style', old_style);
        new_tree.id = 'tree_' + name;
    }
    
    this.selectElement = function (id) {
        var node;
        for (var i = 0; i < this.roots.length; i++) {
            this.unselectRecurse (this.roots[i], id);
        }
        for (var i = 0; i < this.roots.length; i++) {
            node = this.findNode (this.roots[i], id);
            if (node != null) {
                node.makeSelected ();
            }
        }
        if (typeof (this.onchange) == 'function') {
            this.onchange ();
        }
    }
    
    this.unselectRecurse = function (node, id) {
        node.selected = false;
        for (var i = 0; i < node.children.length; i++) {
            this.unselectRecurse (node.children[i], id);
        }
    }
    
    this.findNode = function (node, id) {
        returnNode = null;
        if (node.id == id) {
            returnNode = node;
        } else {
            for (var i = 0; i < node.children.length; i++) {
                returnNode = this.findNode (node.children[i], id);
                if (returnNode != null) break;
            }
        }
        return returnNode;
    }
    
    
    
    
    this.updateTreeDisplay = function () {
        var row_num = 0;
        
        for (var i = 0; i < this.roots.length; i++) {
            row_num = this.roots[i].updateDisplay (row_num);
        }
    }
    
    this.openOrClose = function (id, type, option) {
        var root;
        // alert ("Open/close " + id + ', ' + type + ', ' + option);
        for (var i = 0; i < this.roots.length; i++) {
            this.openOrCloseRecurse (id, this.roots[i]);
        }
        // update display after closing nodes
        this.updateTreeDisplay ();
    }
    
    this.openOrCloseRecurse = function (id, currentNode) {
        var found = false;
        
        if (currentNode.id == id) {
            found = true;
            if (currentNode.open) {
                currentNode.open = false;
                // need to close children of this node as we close it ??
                for (var i = 0; i < currentNode.children.length; i++) {
                    
                }
            } else {
                currentNode.open = true;
            }
        } else {
            for (var i = 0; i < currentNode.children.length; i++) {
                found = this.openOrCloseRecurse (id, currentNode.children[i]);
                if (found) break;
            }
        }
        
        return found;
    }
    
    this.check_delete = function (id, is_checked) {
        var node_to_del;
        for (var i = 0; i < this.roots.length; i++) {
            node_to_del = this.findNode (this.roots[i], id);
            if (node_to_del != null) {
                break;
            }
        }
        if (node_to_del != null) {
            if (is_checked) {
                node_to_del.delete_tick = true;
                // alert ('check: ' + is_checked);
            } else {
                node_to_del.delete_tick = false;
                // alert ('clear');
            }
        }
    }
    
}

function treeNode (tree, id, name, disable, no_width) {
    
    this.children = new Array ();
    this.id = id;
    this.name = name;
    this.open = false;
    this.selected = false;
    this.parent = null;
    this.delete_tick = false;
    this.disabled = false;
    this.level = 0;
    this.nowidth = false;
    this.tree = tree;
    
    if (disable == true) {
        this.disabled = true;
    }
    if (no_width == true) {
        this.nowidth = true;
    }
        
    this.addChild = function (child) {
        child.parent = this;
        child.level = this.level + 1;
        this.children[this.children.length] = child;
    }
    
    this.makeSelected = function () {
        this.selected = true;
        this.makeOpen ();
        /*
        var parent;
        parent = this.parent;
        while (parent != null) {
            parent.open = true;
            parent = parent.parent;
        }
        */
    }
    
    this.makeOpen = function () {
        var parent;
        parent = this.parent;
        while (parent != null) {
            parent.open = true;
            parent = parent.parent;
        }
    }
    
    this.updateDisplay = function (row_num) {
        var new_class, div, img;
        
        if (this.parent == null || this.parent.open) {
            row_num++;
            if (row_num == 3) row_num = 1;
            if (this.selected) {
                new_class = 'selected_row';
            } else {
                new_class = 'tree_node tree_level' + (this.level + 1) + '_altrow' + row_num;
            }
        } else {
            this.open = false;
            new_class = 'tree_row_invisible';
        }
        
        div = document.getElementById ('tree_node_' + tree.name + '_' + this.id);
        if (div != null) {
            div.className = new_class;
            img = document.getElementById ('tree_node_plus_minus_' + tree.name + '_' + this.id);
            if (img != null) {
                if (this.open) {
                    img.src = minus_image;
                } else {
                    img.src = plus_image;
                }
            }
            
            // process kids
            for (var i = 0; i < this.children.length; i++) {
                row_num = this.children[i].updateDisplay (row_num);
            }
        } else {
            //alert ('No such element: tree_node_' + this.id);
        }
        
        return row_num;
        
    }
    
    this.display = function (row_num, type, option) {
        var response_outer = document.createElement ('div');
        var innerElements = new Array ();
        var child;
        var child_response;
        var response_arr;
        var response;
        var link_action;
        var inner_id = this.id;
        var form_name;
        var first_child, last_child;
        
        depth++;
        if (depth > max_depth) max_depth = depth;
        
        
        // window.alert ('called display (' + level + ',' + row_num + ',' + type + ',' + option + ')');
        
        response = document.createElement ('div');
        response.setAttribute ('id', 'tree_node_' + tree.name + '_' + this.id);
        
        if (this.parent == null || this.parent.open) {
            
            row_num++;
            if (row_num == 3) row_num = 1;
            
            if (this.selected) {
                response.className = 'selected_row';
            } else {
                response.className = 'tree_node tree_level' + (this.level + 1) + '_altrow' + row_num;
            }
        } else {
            response.className = 'tree_row_invisible';
        }
        // response_outer.appendChild (response);
        
        // left space
        if (this.level > 0) {
            innerElements[0] = document.createElement ('div');
            innerElements[0].setAttribute ('class', 'tree_intro');
            innerElements[0].setAttribute ('className', 'tree_intro');
            innerElements[0].setAttribute ('style', 'width: '+ (this.level * levelWidth) + 'px;');
            // innerElements[0].appendChild (document.createTextNode ('\u00a0'));
            innerElements[1] = document.createElement ('img');
            innerElements[1].setAttribute ('src', 'images/blank.gif');
            innerElements[1].setAttribute ('width', this.level * levelWidth);
            innerElements[1].setAttribute ('height', '16');
            innerElements[0].appendChild (innerElements[1]);
            response.appendChild (innerElements[0]);
            // response.appendChild (document.createTextNode ("\n"));
        }
        
        // opener/closer
        innerElements[0] = document.createElement ('div');
        innerElements[0].className = 'tree_plus_minus';
        
        if (this.children.length > 0) {
            innerElements[1] = document.createElement ('span');
            
            innerElements[1].onclick = function () { tree.openOrClose (inner_id, type, option); };
            innerElements[1].setAttribute ('class', 'clickable');
            innerElements[1].setAttribute ('className', 'clickable');
            
            innerElements[2] = document.createElement ('img');
            innerElements[2].setAttribute ('id', 'tree_node_plus_minus_' + tree.name + '_' + this.id);
            if (this.open) {
                innerElements[2].setAttribute ('src', minus_image);
            } else {
                innerElements[2].setAttribute ('src', plus_image);
            }
            innerElements[1].appendChild (innerElements[2]);
            
            innerElements[0].appendChild (innerElements[1]);
        } else {
            if (type == 'url' && !this.disabled) {
                // innerElements[0].appendChild (document.createTextNode ("\u00a0"));
                innerElements[1] = document.createElement ('input');
                innerElements[1].setAttribute ('type', 'checkbox');
                innerElements[1].setAttribute ('name', 'del[' + inner_id + ']');
                innerElements[1].setAttribute ('value', 1);
                if (this.delete_tick) {
                    innerElements[1].setAttribute ('checked', 'checked');
                }
                innerElements[1].onchange = function () { tree.check_delete (inner_id, this.checked); }
                innerElements[0].appendChild (innerElements[1]);
            } else {
                innerElements[0].appendChild (document.createTextNode ("\u00a0"));
            }
        }
        
        response.appendChild (innerElements[0]);
        // response.appendChild (document.createTextNode ("\n"));
        
        // actual element
        innerElements[0] = document.createElement ('div');
        if (this.nowidth == true) {
            innerElements[0].setAttribute ('class', 'tree_element_nowidth');
            innerElements[0].setAttribute ('className', 'tree_element_nowidth');
        } else {
            innerElements[0].setAttribute ('class', 'tree_element');
            innerElements[0].setAttribute ('className', 'tree_element');
        }
        
        var self = this;
        
        if (type == 'url') {
            innerElements[0].onclick = function () {
                window.location = option + self.id;
                tree.updateTreeDisplay ();
            };
            
        } else {
            // link_action = option.lastIndexOf ('.');
            // form_name = option.substr (0, link_action);
            
            innerElements[0].onclick = function () {
                eval (option + ".value = '" + String (self.id).replace (/'/, "\\'") + "';");
                tree.selectElement (self.id);
                tree.updateTreeDisplay ();
            };
        }
        
        innerElements[0].appendChild (document.createTextNode (this.name));
        // window.alert ('set element name to ' + this.name);
        response.appendChild (innerElements[0]);
        
        // order buttons, e.g. main_order.php?t=PagesList&d=u&id=2, main_order.php?t=PagesList&d=d&id=2
        if (type == 'url' && tree.orderable) {
            if (this.parent == null) {
                first_child = tree.roots[0];
                last_child = tree.roots[tree.roots.length - 1];
            } else {
                first_child = this.parent.children[0];
                last_child = this.parent.children[this.parent.children.length - 1];
            }
            innerElements[0] = document.createElement ('div');
            if (first_child != this && !this.disabled) {
                // up ok
                innerElements[0].setAttribute ('class', 'tree_orderer');
                innerElements[0].setAttribute ('className', 'tree_orderer');
                innerElements[1] = document.createElement ('a');
                innerElements[1].setAttribute ('href', 'main_order.php?t=' + db_table + '&d=u&id='+ this.id);
                innerElements[2] = document.createElement ('img');
                innerElements[2].setAttribute ('height', '16');
                innerElements[2].setAttribute ('width', '16');
                innerElements[2].setAttribute ('src', up_image);
                innerElements[2].setAttribute ('border', '0');
                innerElements[1].appendChild (innerElements[2]);
                /*
                link_action = "window.location = 'main_order.php?t=" + db_table + '&d=u&id='+ this.id + "';";
                innerElements[1].onclick = function () { eval (link_action); }
                */
            } else {
                innerElements[0].setAttribute ('class', 'tree_empty_orderer');
                innerElements[0].setAttribute ('className', 'tree_empty_orderer');
                innerElements[1] = document.createElement ('img');
                innerElements[1].setAttribute ('height', '16');
                innerElements[1].setAttribute ('width', '16');
                innerElements[1].setAttribute ('src', 'images/blank.gif');
            }
            innerElements[0].appendChild (innerElements[1]);
            response.appendChild (innerElements[0]);
            
            innerElements[0] = document.createElement ('div');
            if (last_child != this && !this.disabled) {
                // down ok
                innerElements[0].setAttribute ('class', 'tree_orderer');
                innerElements[0].setAttribute ('className', 'tree_orderer');
                innerElements[1] = document.createElement ('a');
                innerElements[1].setAttribute ('href', 'main_order.php?t=' + db_table + '&d=d&id='+ this.id);
                innerElements[2] = document.createElement ('img');
                innerElements[2].setAttribute ('height', '16');
                innerElements[2].setAttribute ('width', '16');
                innerElements[2].setAttribute ('src', down_image);
                innerElements[2].setAttribute ('border', '0');
                innerElements[1].appendChild (innerElements[2]);
                /*
                link_action = "window.location = 'main_order.php?t=" + db_table + '&d=u&id='+ this.id + "';";
                innerElements[1].onclick = function () { eval (link_action); }
                */
            } else {
                innerElements[0].setAttribute ('class', 'tree_empty_orderer');
                innerElements[0].setAttribute ('className', 'tree_empty_orderer');
                innerElements[1] = document.createElement ('img');
                innerElements[1].setAttribute ('height', '16');
                innerElements[1].setAttribute ('width', '16');
                innerElements[1].setAttribute ('src', 'images/blank.gif');
            }
            innerElements[0].appendChild (innerElements[1]);
            response.appendChild (innerElements[0]);
        }
        
        // response.appendChild (document.createTextNode ("\n"));
        
        // add self
        response_outer.appendChild (response);
        // add all the children
        //if (this.open) {
            // window.alert ("Adding children!");
            
            for (var i = 0; i < this.children.length; i++) {
                child = this.children[i];
                child_response = child.display (row_num, type, option);
                innerElements[0] = child_response [0];
                row_num = child_response [1];
                // window.alert ('Adding element ' + innerElements[0]);
                
                for (var j = 0; j < innerElements[0].childNodes.length; j++) {
                    innerElements[1] = innerElements[0].childNodes[j];
                    // window.alert ('child node: ' + innerElements[1].getAttribute ('name') + ":\n" + innerElements[1].toString ());
                    /*
                    for (k = 0; k < innerElements[0].childNodes[j].length; k++) {
                        innerElements[1].appendChild (innerElements[0].childNodes[j].childNodes[k]);
                    }
                    */
                    // window.alert ('appending child '+ j + ' (' + innerElements[1] + ') to ' + this.name);
                    response_outer.appendChild (document.createTextNode ("\n"));
                    response_outer.appendChild (innerElements[1]);
                }
            }
        //}
        response_arr = new Array ();
        response_arr[0] = response_outer;
        response_arr[1] = row_num;
        
        depth--;
        
        return response_arr;
    }
}
