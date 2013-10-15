<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);

header ('Content-type: text/javascript');

require 'column_definition.php';
echo "var column_classes = {\n";
$class_num = 0;
foreach (tricho\Runtime::get_column_classes () as $class) {
    $types = $class::getAllowedSqlTypes ();
    if (count ($types) == 0) continue;
    if (++$class_num != 1) echo ",\n";
    $default = $class::getDefaultSqlType ();
    echo "    '{$class}': {'default': '{$default}', 'types': ['", implode ("', '", $types), "']}";
}
echo "\n}\n";
?>
