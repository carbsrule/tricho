<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Runtime;
use Tricho\DataUi\MainTable;
use Tricho\Meta\Database;

header ('Content-Type: text/html; charset=UTF-8');
require '../tricho.php';
test_admin_login ();

$db = Database::parseXML();
$table = $db->getTable ($_GET['t']); // use table name
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <title><?= Runtime::get('site_name'); ?> Administration</title>
        <link rel="stylesheet" type="text/css" href="../css/messages.css">
        <link rel="stylesheet" type="text/css" href="css/tools.css">
        <link rel="stylesheet" type="text/css" href="css/layout.css">
        <link rel="stylesheet" type="text/css" href="css/style.css">
        <script language="JavaScript" type="text/javascript" src="functions.js"></script>
        <script language="JavaScript" type="text/javascript" src="search_functions.js"></script>
        <script language="JavaScript" type="text/javascript" src="../tricho/functions.js"></script>
        <script language="JavaScript" type="text/javascript" src="tree.js"></script>
        <script language="JavaScript" type="text/javascript" src="../tricho/ajax/config.js"></script>
        <script language="JavaScript" type="text/javascript" src="../tricho/ajax/queue.js"></script>
        <script language="JavaScript" type="text/javascript" src="../tricho/ajax/base_handlers.js"></script>
        
        <script>
        function init() {
            if (document.getElementById('search_container') != null) {
                display_search (true);
            }
        }
        </script>
        
    </head>
    <body onload="init();">
<?php
require 'main_functions.php';

$start = (int) $_GET['start'];

$table = $db->getTable ($_GET['t']); // use table name

if ($table == null) {
    echo "<div id=\"main_data\">\n";
    report_error ("Table does not exist: {$_GET['t']}");
    echo "</div>\n";
    require "foot.php";
    unset ($_SESSION[ADMIN_KEY]['err']);
    unset ($_SESSION[ADMIN_KEY]['msg']);
    die ();
}

if (isset($_SESSION[ADMIN_KEY]['num_per_page'][$table->getName ()])) {
    $num_per_page = $_SESSION[ADMIN_KEY]['num_per_page'][$table->getName ()];
} else {
    $num_per_page = RECORDS_PER_PAGE;
}

?>

<div id="main_data">

<p>Select item:</p>
<?php
// Handle normal (rows) table view
if ($table->getDisplayStyle () == TABLE_DISPLAY_STYLE_ROWS) {
    
    // import our columns
    $main = new MainTable ($table);
    $main->setOption (MAIN_OPTION_ALLOW_ADD, false);
    $main->setOption (MAIN_OPTION_ALLOW_DEL, false);
    $main->setOption (MAIN_OPTION_CSV, false);
    $main->setPageUrls (MAIN_PAGE_EDIT, 'inline_search_action.php?f='. urlencode ($_GET['f']));
    $main->setPageUrls (MAIN_PAGE_MAIN, 'inline_search.php?f='. urlencode ($_GET['f']));
    $main->setInlineSearch (true);
    
    // determine if there are any records at all in this table
    // this currently does the whole table. one day we may narrow it to only this tab if we are on a tab
    $q = "SELECT Count(*) as Count FROM `{$_GET['t']}`";
    $res = execq($q);
    $row = fetch_assoc($res);
    if ($row['Count'] < 3 and @count($_SESSION[ADMIN_KEY]['search_params']) == 0) {
        $main->clearSearchCols ();
    }
    
    // apply the filters
    if (@count($_SESSION[ADMIN_KEY]['inline_search'][$table->getName ()]) > 0) {
        $main->applyFilters ($_SESSION[ADMIN_KEY]['inline_search'][$table->getName ()]);
    }
    
    // show the table to the user
    echo $main->getHtml (null, $num_per_page);
    
}
?>

</div>
</body>
</html>
