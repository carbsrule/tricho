<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
require 'setup_functions.php';
header ('Content-type: text/javascript');

$mappings = get_available_collation_mappings ();
$charset_num = 0;
echo "var collations = {\n";
foreach ($mappings as $charset => $collations) {
    if (++$charset_num != 1) echo ",\n";
    echo "    '{$charset}': [\n";
    $collation_num = 0;
    foreach ($collations as $collation) {
        if (++$collation_num != 1) echo ",\n";
        echo "        '{$collation}'";
    }
    echo "\n    ]";
}
echo "\n}\n";
?>
