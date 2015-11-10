/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

var http_response_code = new Array ();
http_response_code[404] = 'page not found';

if (DELAY == null) {
    var DELAY = 100;
}

var is_msie = false;
if (navigator.userAgent.match ('MSIE') !== null) is_msie = true;

var queue = new Queue;


function Queue () {
    this.request_queue = new Array ();
    this.requester_active = false;
    this.created = true;
    
    
    this.request = function (method, url, handler, post_data, async) {
        if (url == '') {
            alert ('Invalid URL specified for AJAX request');
            return;
        }
        
        if (typeof (handler) != 'object') {
            alert ('Invalid handler specified for AJAX request');
            return;
        }
        
        var req = new Request (method, url, handler, post_data);
        if (async === true) {
            req.send ();
        }
        this.request_queue.push (req);
        if (req.handler.onQueue != null) {
            req.handler.onQueue();
        }
        this.activate ();
    }
    
    
    this.activate = function () {
        if (this.requester_active != true && this.request_queue.length > 0) {
            this.requester_active = true;
            
            var req = this.request_queue[0];
            
            if (!req.sent) {
                req.send ();
            }
            
            window.setTimeout ('queue.process ();', DELAY);
        }
    }
    
    this.process = function () {
        if (this.request_queue.length > 0) {
            
            // process request from FIFO buffer
            var first_request = this.request_queue[0];
            if (first_request.ready_state () == 4) {
                
                if (first_request.status () == 200) {
                    first_request.process ();
                    
                } else if (first_request.status () != 0) {
                    var ajax_error = 'AJAX call to ' + first_request.url + ' returned server error:\n' +
                        first_request.status ();
                    if (http_response_code[first_request.status ()]) {
                        ajax_error += ' (' + http_response_code[first_request.status ()] + ')';
                    }
                    
                    first_request.handleError(ajax_error);
                }
                
                // remove item from queue
                this.request_queue.shift ();
                
                this.requester_active = false;
                this.activate ();
                
            } else {
                // keep waiting
                window.setTimeout ('queue.process ();', DELAY);
            }
        }
    }
}

function Request (method, url, handler, post_data) {
    this.url = url;
    this.handler = handler;
    this.method = method.toUpperCase ();
    this.requester = null;
    this.internet_explorer = false;
    this.node_id = null;
    this.post_data = post_data;
    this.sent = false;
    
    this.ready_state = function () {
        if (this.requester == null) {
            return -1;
        } else {
            return this.requester.readyState;
        }
    }
    
    this.status = function () {
        if (this.requester == null) {
            return -1;
        } else {
            return this.requester.status;
        }
    }
    
    this.send = function () {
        // try Moz/Safari method, then IE
        if (window.XMLHttpRequest) {
            this.requester = new XMLHttpRequest ();
        } else if (this.requester = new ActiveXObject("MSXML2.XMLHTTP.3.0")) {
            this.internet_explorer = true;
        }
        
        if (this.requester != null) {
            this.requester.open(method, url, true);
            if (this.method == 'POST') {
                this.requester.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            }
            this.requester.send(this.post_data);
            this.sent = true;
            
        } else {
            alert ('Your browser does not appear to support AJAX');
        }
    }
    
    this.process = function () {
        if (this.requester != null) {
            // check for errors
            var top_node = false;
            
            var top_children = this.requester.responseXML;
            if (top_children == null) {
                this.handleError ('Invalid XML returned via AJAX, please contact the web site administrator');
                return;
            }
            
            // skip any comments, whitespace, etc. and find the first real XML node
            top_children = top_children.childNodes;
            var curr_child_num = 0;
            var curr_child = null;
            while (!top_node) {
                if (top_children.length <= curr_child_num) {
                    break;
                }
                curr_child = top_children.item (curr_child_num);
                if (curr_child.nodeType == 1) {
                    top_node = curr_child;
                } else {
                    curr_child_num++;
                }
            }
            
            if (! top_node) {
                this.handleError ('Invalid XML returned via AJAX, please contact the web site administrator');
                
            } else if (top_node.nodeName.toLowerCase () == 'error') {
                this.handleError (top_node.firstChild.data);
                
            } else {
                this.handler.process (top_node);
                if (this.handler.aftermath) {
                    this.handler.aftermath (top_node);
                }
            }
            
        } else {
            alert ('Request.function called on null requester');
        }
    }
    
    this.handleError = function (message) {
        if (this.handler.error != null) {
            this.handler.error (message);
        } else {
            alert (message);
        }
    }
}
