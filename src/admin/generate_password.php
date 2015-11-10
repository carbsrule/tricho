<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);
$_GET['t'] = '__tools';
require 'head.php';
?>

<div id="main_data">
<h2>Generate Password</h2>

<?php
$_GET['section'] = 'gen';
require_once 'tools_tabs.php';

if (defined ('PASSWORD_MIN_LENGTH')) {
    
    if (@$_GET['length'] == '') {
        $_GET['length'] = PASSWORD_MIN_LENGTH;
    } else {
        settype ($_GET['length'], 'int');
        
        if ($_GET['length'] < PASSWORD_MIN_LENGTH) {
            $_GET['length'] = '';
        }
    }
} else {
    $_GET['length'] = '';
}
?>

<form method="get" action="generate_password.php">
<p>
<label for="gen_pass_len">Length</label>
<input id="gen_pass_len" type="text" name="length" value="<?= $_GET['length']; ?>" size="2" maxlength="3">
<input type="submit" value="Generate">
</p>
</form>


<?php
// generate a random password if specified
$_GET['length'] = (int) $_GET['length'];
if ($_GET['length'] > 0) {
    $pass = generate_password($_GET['length']);
    
    echo "<p>Your new password is:</p>";
    echo "<pre style='border: 1px black solid; padding: 0.5em; margin: 0.5em 0em; max-width: {$_GET['length']}em;'>{$pass}</pre>";
}
?>

</div>

<?php
require_once 'foot.php';
?>
