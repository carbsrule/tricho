<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../../tricho.php';
header ('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
                        "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <title>Rich text filtering test suite</title>
</head>
<body>
<h1>Rich text filter :: Manual test</h1>

<?php

check_session_response ('test');

if (isset ($_POST['submit'])) {
    if ($_POST['input'] == '') {
        $_SESSION['test']['err'] = "Please supply some input";
    } else {
        $_SESSION['test'] = array ();
        $allow     = $_SESSION['test']['allow']     = $_POST['allow'];
        $replace = $_SESSION['test']['replace'] = $_POST['replace'];
        $deny        = $_SESSION['test']['deny']        = $_POST['deny'];
        $input     = $_SESSION['test']['input']     = $_POST['input'];

        $output = clean_rich_text_input ($input, $allow, $replace, $deny);

        echo "Cleaned output:\n";
        echo "<pre style=\"border: 1px solid #3333FF; padding: 20px 20px 20px 20px;\">\n";
        echo htmlspecialchars ($output);
        echo "</pre>\n";

        unset ($_POST);
    }
} else if (isset ($_POST['reset'])) {
    unset ($_SESSION['test']);
}
?>

    <form name="rtf_test" method="post" action="<?= basename (__FILE__); ?>">
        <table>
            <tr>
        <td>Allow:</td>
        <td><textarea name="allow" cols="50" rows="3"><?= $_SESSION['test']['allow']; ?></textarea></td>
            </tr>
            <tr>
        <td>Replace:</td>
        <td><textarea name="replace" cols="50" rows="3"><?= $_SESSION['test']['replace']; ?></textarea></td>
            </tr>
            <tr>
        <td>Deny:</td>
        <td><textarea name="deny" cols="50" rows="3"><?= $_SESSION['test']['deny']; ?></textarea></td>
            </tr>
            <tr>
        <td>Input:</td>
        <td><textarea name="input" cols="50" rows="10"><?= $_SESSION['test']['input']; ?></textarea></td>
            </tr>
            <tr>
        <td colspan="2" align="right">
            <input type="submit" name="reset" value="Reset" />
            <input type="submit" name="submit" value="Clean!" />
        </td>
            </tr>
        </table>
    </form>
    
</body>
</html>
