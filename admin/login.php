<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../tricho.php';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
<title>Login</title>
    <link rel="stylesheet" type="text/css" href="<?= ROOT_PATH_WEB. ADMIN_DIR; ?>css/style.css">
    <link rel="stylesheet" type="text/css" href="<?= ROOT_PATH_WEB; ?>css/messages.css">
</head>
<body>
<h2>Login for Administration</h2>

<?php report_session_error (ADMIN_KEY); ?>

<form method="post" action="login_action.php">
<table>
    <tr>
        <td>Username:</td>
        <td><input type="text" class="login_field" value="<?= $_GET['u']; ?>" name="kaudhm"></td>
    </tr>
    <tr>
        <td>Password:</td>
        <td><input type="password" class="login_field" name="askhd"></td>
    </tr>
    <tr>
        <td>&nbsp;</td>
        <td align="right"><input type="submit" value="Login"></td>
    </tr>
</table>

<?php
$redir = trim ($_GET['redirect']);
if ($redir != '') {
    echo "<input type=\"hidden\" name=\"redirect\" value=\"", htmlspecialchars ($redir), "\">";
}
?>

</form>
</body>
</html>