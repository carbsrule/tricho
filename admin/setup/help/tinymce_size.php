<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';
?>


<h1>TinyMCE size constraints</h1>

<p>TinyMCE fields, by default, cannot be any smaller than 100px wide and 100px high.</p>

<p>This can be changed by editing the TinyMCE source code, located in <code>tiny_mce_src.js</code>.</p>

<p>The lines you need are:</p>
<table id="data_types">
    <tr>
        <td>Width</td>
        <td><code>w = Math.max(parseInt(w) + (o.deltaWidth || 0), 100);</code></td>
    </tr>
    <tr>
        <td>Height</td>
        <td><code>h = Math.max(parseInt(h) + (o.deltaHeight || 0), 100);</code></td>
    </tr>
</table>

<p>As of version 3.0.7, they are located on line 6681 for width, and 6684 for height (line numbers will likely be different for different versions of TinyMCE).</p>

<p>After making the change, you will need to re-compress the source file into <code>tiny_mce.js</code>. The easiest way to do JS compression is by using <a href="http://javascriptcompressor.com/">javascript compressor.com</a>.</p>


<?php
require 'foot.php';
?>
