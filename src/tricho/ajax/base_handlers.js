/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

function ExampleHandler () {
    
    /**
    * Processes the returned XML DOM nodes
    **/
    this.process = function (top_node) {
        
    }
    
    /**
    * Processes an error. Optional. If omitted, errors are outputted as an alert
    **/
    this.error = function (message) {
        
    }
    
    /**
    * Is fired when a request is put onto the queue. Optional
    **/
    this.onQueue = function () {
        
    }
    
    /**
    * Private functions should be named with a preceding underscore
    * To prevent name conflicts if the API changes
    **/
    this._privateFunction = function () {
        
    }
    
}



/**
* This handler populates the specified select list
*
* This handler expects the XML data in the following format:
* <(anything)>
*     <item id="(select option value)">(select option name)</item>
*     <item id="(select option value)">(select option name)</item>
*     <group name="An optgroup">
*         <item id="(select option value)">(select option name)</item>
*         <item id="(select option value)">(select option name)</item>
*     </group>
* </(anything)>
*
* Errors should be returned in the standard AJAX error format, <error>(message)</error>
*
* After you have initialised this handler, you can set some options by changing its parameters.
*     'top_option' - the top item to put in the list. Defaults to '-- Select below --'
*     'empty_option' - when the list is empty, this item will pe put in the list. Default: 'Nothing available'
*     'loading_node' - if set, this node will contain a loading message, otherwise the message will be put
*                into the select list
**/
function SelectReplacementHandler (select_node) {
    if (select_node == null) {
        alert ('You must specify a select node for a SelectReplacementHandler');
        return;
    }
    
    this.select_node = select_node;
    this.top_option = '-- Select below --';
    this.empty_option = 'Nothing available';
    this.prev_selection = null;
    this.loading_node = null;
    
    
    /**
    * Processes the returned XML DOM nodes
    **/
    this.process = function (top_node) {
        var nodes = top_node.getElementsByTagName ('item');
        var child_id, child, children;
        
        if (nodes.length == 0) {
            
            // TODO: functionise
            // remove existing options and groups
            this.select_node.options.length = 0;
            children = this.select_node.childNodes;
            for (child_id    = children.length - 1; child_id >= 0; child_id--) {
                child = children.item (child_id);
                if (child.tagName && child.tagName == 'OPTGROUP') {
                    this.select_node.removeChild (child);
                }
            }
            
            this.select_node.options[0] = new Option (this.empty_option, '');
            
        } else {
            
            // TODO: functionise
            // remove existing options and groups
            this.select_node.options.length = 0;
            children = this.select_node.childNodes;
            for (child_id    = children.length - 1; child_id >= 0; child_id--) {
                child = children.item (child_id);
                if (child.tagName && child.tagName == 'OPTGROUP') {
                    this.select_node.removeChild (child);
                }
            }
            
            var node_id, node, group, option;
            nodes = top_node.childNodes;
            
            if (this.top_option != '') {
                option = create_element ('option', {'value': ''});
                option.appendChild (document.createTextNode (this.top_option));
                this.select_node.appendChild (option);
            }
            
            for (node_id = 0; node_id < nodes.length; node_id++) {
                
                node = nodes.item (node_id);
                
                if (node.nodeName) {
                    if (node.nodeName == 'item') {
                        option = create_element ('option', {'value': node.getAttribute ('id')});
                        option.appendChild (document.createTextNode (node.firstChild.nodeValue));
                        this.select_node.appendChild (option);
                        if (node.getAttribute ('id') == this.prev_selection) {
                            this.select_node.value = this.prev_selection;
                        }
                        
                    } else if (node.nodeName == 'group') {
                        
                        group = create_element ('optgroup', {'label': node.getAttribute ('name')});
                        this.select_node.appendChild (group);
                        
                        children = node.childNodes;
                        for (child_id = 0; child_id < children.length; child_id++) {
                            child = children.item (child_id);
                            if (child.nodeName && child.nodeName == 'item') {
                                option = create_element ('option', {'value': child.getAttribute ('id')});
                                option.appendChild (document.createTextNode (child.firstChild.nodeValue));
                                group.appendChild (option);
                                if (child.getAttribute ('id') == this.prev_selection) {
                                    this.select_node.value = this.prev_selection;
                                }
                            }
                        }
                    }
                }
            }
            
        }
        
        this._setLoadingMessage ('');
    }
    
    
    /**
    * Processes an error. Optional. If omitted, errors are outputted as an alert
    **/
    this.error = function (message) {
        this.select_node.options.length = 0;
        this.select_node.options[0] = new Option (this.empty_option, '');
        
        this._setLoadingMessage ('ERROR: ' + message);
    }
    
    /**
    * Is fired when a request is put onto the queue. Optional
    **/
    this.onQueue = function () {
        this.prev_selection = this.select_node.value;
        this._setLoadingMessage ('Loading...');
    }
    
    /**
    * Sets the loading message
    **/
    this._setLoadingMessage = function (message) {
        if (this.loading_node == null) {
            if (message == '') return;
            
            this.select_node.options.length = 0;
            this.select_node.options[0] = new Option (message, '');
        
        } else {
            if (this.loading_node.firstChild == null) {
                this.loading_node.appendChild (document.createTextNode (message));
            } else {
                this.loading_node.firstChild.data = message;
            }
        }
    }
}



/**
* This handler replaces a HTML node in the document with an XML node
**/
function NodeReplacementHandler (node_to_replace) {
    if (node_to_replace == null) {
        alert ('You must specify a node to replace for a NodeReplacementHandler');
        return;
    }
    
    this.node_to_replace = node_to_replace;
    
    
    /**
    * Processes the returned XML DOM nodes
    **/
    this.process = function (top_node) {
        
        var alert_text = '';
        
        // makes a copy of the top_node and all of its children
        if (is_msie) {
            var new_node = create_element ('div', {});
            new_node.innerHTML = top_node.xml;
            new_node = new_node.firstChild;
            
        } else {
            var new_node = this._nodeToDom (top_node, null, null);
        }
        
        // copy all the attributes from the to-be-replaced node to the new node
        var attrs = this.node_to_replace.attributes;
        for (var i = attrs.length - 1; i >= 0; i--) {
            if ((attrs[i].value != '') && (attrs[i].value != 'null') && (attrs[i].value != 'false') && (attrs[i].value != '0') && (attrs[i].value != 'inherit')) {
                
                alert_text += 'Set attribute ' + attrs[i].name + ' = ' + attrs[i].value + "\n";
                new_node.setAttribute (attrs[i].name, attrs[i].value);
            }
        }
        
        // events are not passed properly in MSIE so pass them all manually.
        // this is only an issue with events passed from the original node. New node events seem to be fine,
        // must be because of innerHTML or something
        if (is_msie) {
            for (var i in this.node_to_replace) {
                if (i.substr (0, 2) == 'on' && this.node_to_replace[i] != null) {
                    alert_text += 'Set attribute ' + i + ' = ' + this.node_to_replace[i] + "\n";
                    new_node[i] = this.node_to_replace[i];
                }
            }
        }
        
        // copy the attributes from the replacement node to the new node
        // except if the new attribute is empty, in that case remove the attribute
        attrs = top_node.attributes;
        for (var i = attrs.length - 1; i >= 0; i--) {
            if (attrs[i].value == '') {
                new_node.removeAttribute (attrs[i].name);
                alert_text += 'Unset attribute ' + attrs[i].name + " (" + new_node[i] + ")\n";
            } else {
                // don't set attributes for what should be handled as events in IE
                if (is_msie && attrs[i].name.substr (0, 2) == 'on') {
                    continue;
                }
                new_node.setAttribute (attrs[i].name, attrs[i].value);
                alert_text += 'Set attribute ' + attrs[i].name + ' = ' + attrs[i].value + "\n";
            }
        }
        
        if (AJAX_DEBUG) {
            window.alert (alert_text);
        }
        
        // do the replacement
        this.node_to_replace.parentNode.replaceChild (new_node, this.node_to_replace);
        this.node_to_replace = new_node;
        
        this._setLoadingMessage ('');
    }
    
    /**
    * Processes an error. Optional. If omitted, errors are outputted as an alert
    **/
    this.error = function (message) {
        this._setLoadingMessage ('ERROR: ' + message);
    }
    
    /**
    * Is fired when a request is put onto the queue.
    **/
    this.onQueue = function () {
        this._setLoadingMessage ('Loading...');
    }
    
    /**
    * Sets the loading message
    **/
    this._setLoadingMessage = function (message) {
        if (this.loading_node != null) {
            if (this.loading_node.firstChild == null) {
                this.loading_node.appendChild (document.createTextNode(message));
            } else {
                this.loading_node.firstChild.data = message;
            }
        }
    }
    
    /**
    * Loads an XML DOM node into a HTML DOM node
    **/
    this._nodeToDom = function (node, so_far, current_node) {
        var attr;
        var new_node;
        
        if (node.tagName) {
            // trace ('Creating node ' + node.tagName);
            new_node = document.createElement (node.tagName);
            if (so_far == null) {
                so_far = new_node;
                current_node = new_node;
                
            } else {
                current_node.appendChild (new_node);
                
                // attributes
                for (var i = 0; i < node.attributes.length; i++) {
                    attr = node.attributes.item (i);
                    // trace ('new_node.setAttribute ('+attr.name+', '+attr.value+');');
                    if (attr.name == 'class') {
                        set_class (new_node, attr.value);
                    } else {
                        new_node.setAttribute (attr.name, attr.value);
                    }
                }
                
                // for select lists, set the <select> value to the <option> that is selected
                if (new_node.tagName.toUpperCase() == 'OPTION') {
                    if (new_node.hasAttribute ('selected')) {
                        current_node.value = new_node.getAttribute ('value');
                    }
                }
            }
            
            // children (recursive)
            for (var i = 0; i < node.childNodes.length; i++) {
                attr = node.childNodes.item (i);
                if (attr.nodeType == 3) {
                    new_node.appendChild (document.createTextNode (attr.data));
                } else {
                    so_far = this._nodeToDom (attr, so_far, new_node);
                }
            }
        }
        
        return so_far;
    }

}
