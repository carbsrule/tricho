<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';

$ip =& $_SESSION['_tricho']['ip_addr'];

if ($_GET['ip'] != '') {
    $ip = $_GET['ip'];
    echo "<p>IP address changed to {$ip}. Please reload the page you wish to test</p>\n";
    die ();
}

?>
<form method="get" action="<?= $_SERVER['PHP_SELF']; ?>">
    <p>Current IP address stored in session is: <?= $ip; ?></p>
    <p>Change to: <input type="text" name="ip" value="<?= $ip; ?>"> <input type="submit">
</form>
