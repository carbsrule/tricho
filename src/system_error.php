<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

@ob_end_clean();
@http_response_code(500);
@header('Content-type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
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
$errors = [
    "db" => "Couldn't connect to the database; please wait a few moments and then try again",
    "q" => "Database query failed",
    "sys" => "Internal site error",
    "conf" => "Site configuration error"
];
$error = @$errors[$err];
if (!$error) $error = "Unknown error";
?>

<p class="error"><?php
echo $error;
if (!empty($error_text)) echo ': ', hsc($error_text);
?></p>

</body>
</html>
<?php
exit(-1);
