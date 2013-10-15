<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../../tricho.php';
require_once '../../tricho/db/sql_parser.php';

$queries = array (
    "SELECT MD5('abc') FROM NoTable",
    "SELECT MD5('It\\'s a stupid query, isn''t it?') FROM NoTable",
    'SELECT MD5("It\\"s a stupid query, isn""t it?") FROM NoTable',
    "UPDATE User SET Pass = MD5(CONCAT('PostedPassword', 'AnotherVariable', LastName)) WHERE UserID = 1",
    "UPDATE User SET Pass = MD5(CONCAT('PostedPassword', 'AnotherVariable', LastName))".
        ", OtherField = 'some ''string' WHERE UserID = 1",
    "SELECT Md5('a'), SHA1('b'), SHA('c')",
    "SELECT Md5(concat('a', MD5('b')))",
    'SELECT Email, Name FROM NoTable WHERE Password = mD5("asdasdg asdhgashk")',
    'SELECT Email, CONCAT("Fred", " ", "Bloggs") FROM NoTable WHERE Password = MD5("asdasdg asdhgashk")',
    "SELECT CONCAT('Fred', ' ', 'Bloggs'), MD5('aS?DasD?S!44\\wererwe') FROM NoTable"
);


foreach ($queries as $query) {
    
    echo "<pre style=\"border: 1px solid #3333FF; padding: 2px 2px 2px 2px;\">";
    
    echo "Original query: ". $query. "\n";
    
    $parser = new SQLParser ();
    $parser->parse ($query);
    
    echo "Tokens:\n";
    foreach ($parser->getTokens () as $token) {
        echo str_pad(SQLParser::$state_names[$token['type']]. ':', 4, ' ', STR_PAD_RIGHT),
            htmlspecialchars ($token['value']), "\n";
    }
    
    $censored_query = sql_remove_private ($query);
    echo "Censored query: ", htmlspecialchars ($censored_query), "\n";
    echo "</pre>\n";
}


?>
