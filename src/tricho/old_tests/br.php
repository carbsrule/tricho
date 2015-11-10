<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';

header ('Content-type: text/plain');

$strings = array (
    "a <br>\r\n b",
    "a <br/>\n b",
    "a <br />\n b",
    "a <br><br>\n b",
    "a <br/>\n<br/>\n b",
    "a <br />\r<br />\n b",
    "a <br>\n <br>\r b",
    "a <br/>\n <br/>\n b",
    "a <br /><br />\r\n b"
);

function clean ($str) {
    return str_replace (
        array ("\n", "\r"),
        array ("\\n", "\\r"),
        $str
    );
}

$longest = 0;
foreach ($strings as $str) {
    $longest = max ($longest, strlen (clean ($str)));
}

echo "rem:\n";
foreach ($strings as $str) {
    $clean = clean ($str);
    $no_br = rem_br ($str);
    echo $clean, str_repeat (' ', $longest + 1 - strlen ($clean)), ' -> ', clean ($no_br), "\n";
}


$no_br_strings = array (
    "a\nb",
    "a\n\nb",
    "a\r\nb",
    "a\r\n\r\nb",
    "a\rb",
    "a\r\rb"
);

$longest = 0;
foreach ($no_br_strings as $str) {
    $longest = max ($longest, strlen (clean ($str)));
}

echo "\nadd (HTML):\n";
foreach ($no_br_strings as $str) {
    $clean = clean ($str);
    echo $clean, str_repeat (' ', $longest + 1 - strlen ($clean)), ' -> ', clean (add_br ($str, 0)), "\n";
}

echo "\nadd (XHTML):\n";
foreach ($no_br_strings as $str) {
    $clean = clean ($str);
    echo $clean, str_repeat (' ', $longest + 1 - strlen ($clean)), ' -> ', clean (add_br ($str, 1)), "\n";
}

echo "\nadd (config):\n";
$string_num = 0;
$xhtml = tricho\Runtime::get('xhtml');
foreach ($no_br_strings as $str) {
    $clean = clean ($str);
    if ($xhtml) {
        echo '*';
    } else {
        echo ' ';
    }
    echo $clean, str_repeat (' ', $longest + 1 - strlen ($clean)), ' -> ', clean (add_br ($str)), "\n";
}

echo "\nMulti:\n";
$data = array (
    "a\r\nb",
    "a\n\nb",
    "a\r\rb",
    "a\n\nb"
);
$longest = 0;
foreach ($data as $str) {
    $longest = max ($longest, strlen (clean ($str)));
}

foreach ($data as $str) {
    $clean = clean ($str);
    echo $clean, str_repeat (' ', $longest + 1 - strlen ($clean)), ' -> ',
        clean (add_br (rem_br (add_br (rem_br (add_br ($str)))))), "\n";
}
?>
