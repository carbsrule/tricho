<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

$_GET['t'] = '__home';
require 'head.php';
?>
<div id="main_data">
<?php
if ($db->getShowSectionHeadings()) {
    echo '    <h2>Home</h2>';
}

check_session_response (ADMIN_KEY);
?>
    <p>Welcome to Tricho! Please add text to this page describing the administration of this website.</p>
</div>
<?php
require 'foot.php';
?>
