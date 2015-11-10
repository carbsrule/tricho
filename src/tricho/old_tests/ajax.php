<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */
?>
<html>
<head>
<script src="../../admin/functions.js" language="javascript"></script>
<script src="../ajax/queue.js" language="javascript"></script>
<script src="../ajax/base_handlers.js" language="javascript"></script>
<script language="JavaScript" type="text/javascript" src="../ajax/config.js"></script>

<script>
function srh_test1() {
    var node = document.getElementById('srh_select1');
    
    var ajax_handler = new SelectReplacementHandler(node);
    ajax_handler.loading_node = document.getElementById('srh_loading1');
    
    queue.request('GET', 'ajax_srh_data.php', ajax_handler);
}

function srh_test2() {
    var node = document.getElementById('srh_select2');
    var ajax_handler = new SelectReplacementHandler(node);
    
    queue.request('GET', 'ajax_srh_data.php', ajax_handler);
}

function nrh_test(box, file) {
    var node = document.getElementById('nrh_node' + box);
    
    var ajax_handler = new NodeReplacementHandler(node);
    ajax_handler.loading_node = document.getElementById('nrh_loading' + box);
    
    queue.request('GET', 'ajax_nrh_data' + file + '.php', ajax_handler);
}
</script>

<style>
.hello {border: 3px red solid;}
table {border-spacing: 5px; border: 3px black dashed;}
tr,td {border: 1px #CCC solid; padding: 3px;}
p {font-size: xx-small;}
</style>

</head>
<body>

<h2>SelectReplacementHandler test</h2>
<p>Loading node: <select id="srh_select1">
    <option value="w">w</option>
    <option value="x">x</option>
    <option value="y">y</option>
    <option value="z">z</option>
    <option value="a">a</option>
</select></p>
<p><button onclick="srh_test1();">Click me!</button> <span id="srh_loading1"></span></p>

<p>No loading node: <select id="srh_select2">
    <option value="w">w</option>
    <option value="x">x</option>
    <option value="y">y</option>
    <option value="z">z</option>
    <option value="a">a</option>
</select></p>
<p><button onclick="srh_test2();">Click me!</button></p>


<h2>NodeReplacementHandler test</h2>
<p id="nrh_node1" class="hello">
Test 1. Should become green. All elements should work.
</p>
<p><button onclick="nrh_test(1,1);">Click me!</button> <span id="nrh_loading1"></span></p>

<div id="nrh_node2" style="border: 1px green solid;">
Test 2. Should become blue P element. Border should change. All elements should work. (check hidden) Form should submit.
</div>
<p><button onclick="nrh_test(2,2);">Click me!</button> <span id="nrh_loading2"></span></p>

<span id="nrh_node3" onclick="alert('hello');">
Test 3. Should become blue P element. Onclick should work before and after
</span>
<p><button onclick="nrh_test(3,2);">Click me!</button> <span id="nrh_loading3"></span></p>

<input id="nrh_node4" value="Test 4. Should become blue P element.">
<p><button onclick="nrh_test(4,2);">Click me!</button> <span id="nrh_loading4"></span></p>

<table id="nrh_node5" onclick="alert('hello');">
<tr><td>Test 5. Should become blue P element.</td><td>Onclick should work before and after</td></tr>
</table>
<p><button onclick="nrh_test(5,2);">Click me!</button> <span id="nrh_loading5"></span></p>

<em id="nrh_node6">Test 6. Should become a boring table</em>
<p><button onclick="nrh_test(6,3);">Click me!</button> <span id="nrh_loading6"></span></p>

<table>
<tr><td id="nrh_node7">Test 7. Tries to replace this cell with another table</td><td>Should not work and should not fail miserably</td></tr>
</table>
<p><button onclick="nrh_test(7,2);">Click me!</button> <span id="nrh_loading7"></span></p>

<p id="nrh_node8">
Test 1. Should become a working select list
</p>
<p><button onclick="nrh_test(8,4);">Click me!</button> <span id="nrh_loading8"></span></p>

<select id="nrh_node9">
<option value="3">Should become</option>
<option value="2">a working select list</option>
</select>
<p><button onclick="nrh_test(9,4);">Click me!</button> <span id="nrh_loading9"></span></p>

</body>
</html>
