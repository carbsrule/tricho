<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../../tricho.php';
echo "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n";

?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
<head>
    <title>cast_to_string() test suite</title
</head>
<body>

<?php

class ObjectWithNoToString {
    
}

class ObjectWithToString {
    public function __toString() {
        return "ObjectWithToString::__toString()";
    }
}

$obj_with_no_ts = new ObjectWithNoToString;
$obj_with_ts = new ObjectWithToString;
$php_string = "regular PHP string";
$php_integer = 42;

echo 'Class definitions:<br /><blockquote><pre>';
echo 'class ObjectWithNoToString {

}

class ObjectWithToString {
    public function __toString() {
        return "ObjectWithToString::__toString()";
    }
}';
echo '</pre></blockquote>';

echo 'Creating new variables of various types:<br /><blockquote><pre>';
echo '$obj_with_no_ts = new ObjectWithNoToString;
$obj_with_ts = new ObjectWithToString;
$php_string = "regular PHP string";
$php_integer = 42;';
echo '</pre></blockquote>';

echo 'Calling cast_to_string on variables:<br /><blockquote><pre>';

echo 'cast_to_string ($obj_with_no_ts);
    --> ';
echo cast_to_string ($obj_with_no_ts) ."\n\n";

echo 'cast_to_string ($object_with_ts);
    --> ';
echo cast_to_string ($obj_with_ts) ."\n\n";

echo 'cast_to_string ($php_string);
    --> ';
echo cast_to_string ($php_string) ."\n\n";

echo 'cast_to_string ($php_integer)
    --> ';
echo cast_to_string ($php_integer) ."\n\n";
echo '</pre></blockquote>';

?>

</body>
</html>
