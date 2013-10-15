<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once 'head.php';
?>

<h2>Rich text field filtering</h2>

<p>
    The allow, replace, and deny text fields allow you to specify what
    HTML is allowed to be submitted via a (TinyMCE) rich text field. The
    appropriate transformations take place before TinyMCE posts its data. The
    transformations are applied via the backend as well to prevent someone
    circumventing this mechanism by posting data directly.
</p>

<h3>Deny rules</h3>
Deny rules are specified as a comma separated list of HTML elements. HTML
elements that appear in the list will be removed, along with their children.
<br /><br />
For example, the following deny list with remove script tags and div tags.
<pre>
    script,div
</pre>
Thus, an input string such as
<pre>
    &lt;p&gt;Text region A&lt;/p&gt;&lt;div&gt;&lt;p&gt;Text region B&lt;/p&gt;&lt;/div&gt;&lt;p&gt;Text region C&lt;/p&gt;
</pre>
will result in the following:
<pre>
    &lt;p&gt;Text region A&lt;/p&gt;&lt;p&gt;Text region C&lt;/p&gt;
</pre>
<i>Note that any script tags would also be removed.</i>
<br />

<h3>Replace rules</h3>
Replacement rules are specified as a comma separated list of HTML tag pairs separated
by a '=' character. Any tag that occurs on the right hand side (target) of a '=' character will be replaced by the tag on the left hand side (source) of the same '=' character.
<br /><br />
For example, the replace list below, would replace all occurrences of &lt;b&gt; and &lt;i&gt; elements with &lt;strong&gt; and &lt;em&gt; elements, respectively. Any attributes that do not apply to the target element will be discarded.
<pre>
    b=strong,i=em
</pre>

<h3>Allow rules</h3>
Allow rules are specified as a comma separated list of tag/attribute-list pairs. Attribute lists are separated from their corresponding tags with colons, and attribute list elements are separated with semi-colons. Tags that do not appear in this list are removed, however their children are retained (unless, of course, the tag appears in the deny or replace lists). Tags that do appear in this list then have their attributes filtered - any attributes that are not in the tags attribute list are removed.
<br /><br />
As an example, the following allow line will only allow &lt;a&gt; tags with href, src, target attributes and &lt;img&gt; tags with src and alt attributes.

<pre>
    a:href;src;target,img:src;alt
</pre>

<div id="notes">
    <p>Notes</p>
    <ul>
        <li>Rules are to be specified on <strong>one</strong> line</li>
        <li>Rules must not contain whitespace</li>
    </ul>
</div>

<?php
require_once 'foot.php';
?>
