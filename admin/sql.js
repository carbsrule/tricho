/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

var table_columns = [];
var ajax_handler = new TableCacheHandler();

function update_side_list() {
    var table = document.getElementById('table').value;
    if (document.getElementById('type').checked) { type = 1; } else { type = 0; }
    
    if (table == '') {
        clear_div();
        
    } else {
        
        // Look in cache first
        if (table_in_cache()) { return; }
        
        // load nodes through ajax
        var url = 'sql_ajax_get_columns.php?table=' + table;
        queue.request ('get', url, ajax_handler, null, false);
    }
}

function table_in_cache() {
    // find and load the required node
    var table = document.getElementById('table').value;
    var type = document.getElementById('type').checked;
    
    var x = 0;
    for (var key in table_columns) {
        if (table_columns[key][0] == table) {
            show_size_list(x, type);
            return true;
        }
        x++;
    }
    
    return false;
}

function clear_div() {
    var node = document.getElementById('sql_collist');
    while (node.firstChild) {
        node.removeChild(node.firstChild);
    };
}

function show_size_list(table_cache_index, type) {
    var table = table_columns[table_cache_index];
    
    clear_div();
    var div = document.getElementById('sql_collist');
    
    // table name
    var text = document.createTextNode(table[0]);
    var p = document.createElement('p');
    p.style.fontWeight = 'bold';
    p.appendChild(text);
    div.appendChild(p);
    
    
    var tbl = document.createElement('table');
    var tbody = document.createElement('tbody');
    tbl.appendChild(tbody);
    
    // table columns
    var span;
    var max = table.length;
    for (var x = 1; x < max; x++) {
        var item = table[x];
        
        // name
        var td = document.createElement('td');
        var text = item.name;
        if (! type) {
            text = table[0] + '.' + text
            if (x + 1 < max) { text += ','; }
        }
        text = document.createTextNode(text);
        
        if (item.index == 'PRI') {
            var span = document.createElement('span');
            span.className = 'struct_pk';
            span.appendChild (text);
            td.appendChild (span);
            
        } else if (item.index != '') {
            var span = document.createElement('span');
            span.className = 'struct_index';
            span.appendChild (text);
            td.appendChild (span);
            
        } else {
            td.appendChild (text);
        }
        
        var row = document.createElement('tr');
        row.appendChild(td);
        
        // type
        if (type) {
            td = document.createElement('td');
            text = document.createTextNode(item.type);
            td.appendChild(text);
            row.appendChild(td);
        }
        
        tbody.appendChild(row);
    }
    
    div.appendChild(tbl);
}

function open_expando (num) {
    var node = document.getElementById('expando' + num);
    node.style.display = '';
    
    var node = document.getElementById('opener' + num);
    node.style.display = 'none';
    
    // Remove empty space between 'more' and the text before
    x = node.innerHTML;
    x = x.substr(1, x.length);
    node.innerHTML = x;
    
    // Clearing the innerHTML of the "more" span when the "more" link is clicked
    txt = node.lastChild;
    txt.innerHTML = '';
}

function close_expando (num) {
    var node = document.getElementById('expando' + num);
    node.style.display = 'none';
    
    var node = document.getElementById('opener' + num);
    node.style.display = '';
    
    // Add an empty space between 'more' and the text before
    x = node.innerHTML;
    node.innerHTML = ' ' + x;
    
    // Reinstate the removed 'more' text
    txt = node.lastChild;
    txt.innerHTML = 'More&nbsp;&raquo;';
}


function TableCacheHandler () {
    this.process = function (top_node) {
        var table = [];
        
        // table name is in position 0
        var tablename = top_node.getAttribute('name');
        table.push(tablename);
        
        // columns
        if (top_node.hasChildNodes()) {
            var columns = top_node.getElementsByTagName('column');
            for (var i = 0; i < columns.length; i++) {
                var item = new Object();
                item.name = columns[i].getAttribute('name');
                item.type = columns[i].getAttribute('type');
                item.index = columns[i].getAttribute('index');
                table.push(item);
            };
        };
        
        // add to cache
        table_columns.push(table);
        
        // get sql.php to re-read the cache
        table_in_cache();
    }
}
