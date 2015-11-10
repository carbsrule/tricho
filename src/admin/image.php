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
        <title>Image</title>
        <style type="text/css">html, body {padding: 0 0 0 0; margin: 0 0 0 0;}</style>
    </head>
    <body><?php
    if ($_GET['f'] != '') {
        echo '<img src="../file.php?f=', htmlspecialchars ($_GET['f']), '" />';
    }
?></body>
</html>
