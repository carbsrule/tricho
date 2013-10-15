<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

header('Content-type: text/xml; charset=UTF-8');
?>
<div style="border: 1px black solid; background-color: #6F6; padding: 1em;" class="" onclick="document.getElementById('argh').firstChild.data='whee';">
    <p>Whee</p>
    <ul>
        <li>Argh</li>
        <li>Pizza</li>
    </ul>
    
    <p><select id="stupid">
        <option value="w">w</option>
        <option value="x">x</option>
        <option value="y">y</option>
        <option value="z">z</option>
        <option value="a">a</option>
    </select></p>
    
    <p id="argh">When you click on the DIV this text should become 'whee'</p>
    
    <p><a onclick="document.getElementById('stupid').style.width = '500px';" href="#">make select big</a></p>
    
    <h3 onclick="alert('duck');">Heading</h3>
    
    <p>click on the heading above. should alert 'duck'</p>
    
    <h2 style="color: red;">Another heading</h2>
    
    <p><select>
        <option value="w">w</option>
        <option value="x">x</option>
        <option selected="selected" value="y">SELECTED</option>
        <option value="z">z</option>
        <option value="a">a</option>
    </select></p>
    
    <p><input type="text" value="text in a textbox" /></p>
    
    <p><input type="password" value="a password" /></p>
    
    <p><input type="radio" name="aaa" value="1" /> one
    <input type="radio" name="aaa" checked="checked" value="2" /> two
    <input type="radio" name="aaa" value="3" /> three</p>
</div>
