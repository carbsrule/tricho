<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';
?>


<div id="main_data">
<?php
$_GET['section'] = 'db';
require_once 'tools_tabs.php';
?>

<h2>Import SQL table and column definitions from XML</h2>

<?php
if ($_SESSION['import_output'] != '') {
    echo "<p><strong>Results:</strong></p>";
    echo $_SESSION['import_output'];
    unset ($_SESSION['import_output']);
    
} else {
    echo '<p><a href="tables_import.php">Tables import tool</a></p>';
}
?>

</div>

</body>
</html>
