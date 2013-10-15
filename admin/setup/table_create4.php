<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require 'head.php';

$table = $_SESSION['setup']['create_table']['table'];
// echo "<pre>"; print_r ($table); echo "</pre><br>\n";

if (@count($_SESSION['setup']['create_table']['columns']) > 0) {
?>

<h2>Create table <?= $_SESSION['setup']['create_table']['table_name']; ?></h2>

<?php report_session_info ('err', 'setup'); ?>

<h3>Order options</h3>
<iframe src="order_iframe.php?list=order" width="600"></iframe>

<h3>Search options</h3>
<iframe src="search_iframe.php" width="600"></iframe>

<br>&nbsp;
<form method="post" action="table_create4_action.php">
    <input type="submit" value="Finish">
</form>

<?php
} else {
    report_error ("Lost session");
}

require 'foot.php';
?>
