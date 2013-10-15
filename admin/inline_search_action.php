<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

header ('Content-Type: text/html; charset=UTF-8');
require '../tricho.php';
test_admin_login ();

$db = Database::parseXML ('tables.xml');
$table = $db->getTable ($_GET['t']); // use table name

if ($table == null) die ('Invalid table');
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title><?= tricho\Runtime::get('site_name'); ?> Administration</title>
        <link rel="stylesheet" type="text/css" href="../css/messages.css">
        <link rel="stylesheet" type="text/css" href="css/tools.css">
        <link rel="stylesheet" type="text/css" href="css/layout.css">
        <link rel="stylesheet" type="text/css" href="css/style.css">
<?php
$pk_cols = $table->getPKnames ();
$pk_values = array ($pk_cols[0] => $_GET['id']);
$identifier = $table->buildIdentifier ($pk_values);
?>
        <script type="text/javascript">
            function handle () {
                var id = window.opener.document.getElementById ('<?= addslashes ($_GET['f']); ?>_search_val');
                var key = window.opener.document.forms.main_form.elements['<?= addslashes ($_GET['f']); ?>'];
                if (id && key) {
                    id.innerHTML = '<?= addslashes ($identifier); ?>';
                    key.value = '<?= addslashes ($_GET['id']); ?>';
                    document.getElementById ('status').innerHTML = 'Value has been set, please close this window';
                    window.close ();
                }
            }
        </script>
    </head>
    <body onload="handle ();">
    <div id="main_data">
        <p id="status">Please wait while your selection is updated</p>
    </div>
</body>
</html>
