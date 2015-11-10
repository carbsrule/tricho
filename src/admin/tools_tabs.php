<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

echo "<ul class=\"main_edit_tabs\">\n";
echo '<li'. ($_GET['section'] == 'gen' ? ' class="on"' : ''). "><a href=\"tools.php?section=gen\">",
    "General</a></li>\n";
echo '<li'. ($_GET['section'] == 'db' ? ' class="on"' : ''). "><a href=\"tools.php?section=db\">",
    "Database</a></li>\n";
echo '<li'. ($_GET['section'] == 'err' ? ' class="on"' : ''). "><a href=\"tools.php?section=err\">",
    "Error detection/correction</a></li>\n";

echo "</ul>\n";
?>
