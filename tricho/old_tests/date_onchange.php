<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
?>
<html>
<head>
    <title>Test onchange</title>
    <script type="text/javascript">
        var holder = null;
        function change_date () {
            if (holder == null) holder = document.getElementById ('num').firstChild
            holder.data = String (Number (holder.data) + 1);
        }
    </script>
    <script type="text/javascript" src="../functions.js"></script>
</head>
<body>
    <form name="check">
<?php
$params = array ('OnChange' => 'change_date();');
echo tricho_date_select ($params);
?>
    Changes: <span id="num">0</span>
    </form>
</body>
</html>
