<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>System error</title>
    <style type="text/css">
        .error {
            border: 1px solid #FF0000;
            background-color: #FFF4F4; color: #900000;
            font-weight: bold; padding: 3px 5px 3px 5px;
        }
        .warning {
            border: 1px solid #DDDD00;
            background-color: #FFFFE9; color: #909000;
            font-weight: bold; padding: 3px 5px 3px 5px;
        }
        .confirmation {
            border: 1px solid #00DD00;
            background-color: #F4FFF4; color: #009000;
            font-weight: bold; padding: 3px 5px 3px 5px;
        }
    </style>
</head>
<body>
<?php
$errors = array (
    "db" => "Couldn't connect to the database; please wait a few moments and then try again",
    "glob" => "PHP is misconfigured; register_globals must be off",
    "mq" => "PHP is misconfigured; magic_quotes_* must be off",
    "q" => "Database query failed",
    "sys" => "Internal site error",
    "conf" => "Site configuration error"
);
$error = @$errors[$_GET['err']];
if (!$error) $error = "Unknown error";

echo "<p class='error'>{$error}</p>\n";

$_GET['redirect'] = @trim($_GET['redirect']);
if (@$_GET['err'] == 'db') {
    if ($_GET['redirect'] != '') {
        echo "<p><a href=\"{$_GET['redirect']}\">Try again</a></p>\n";
    }
}
?>
</body>
</html>
