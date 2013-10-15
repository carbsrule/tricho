<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../../tricho.php';


define ('TABLE_SIZE', 1000);
$start_time = microtime (true);

echo "<pre>";


// From global scope
echo '[', right_now (), "] Regular call to email_error ().\n";
flush ();
email_error ('Something went wrong - regular call');


// From a function (single call on stack)
function something_risky ($aaa, $bbb) {
    echo '[', right_now (), "] Function call to email_error ().\n";
    flush ();
    email_error ('Something went wrong - function call');
}
something_risky ('whee', 42);


// From a function (multiple calls on stack)
function something_else_risky ($arg1) {
    something_risky ('woo', $arg1);
}
something_else_risky (99.7);


// From a static method
class Dangerous {
    static function doRiskyThing ($ccc, $ddd) {
        echo '[', right_now (), "] Static method call to email_error ().\n";
        flush ();
        email_error ('Something went wrong - static method call');
    }
}
Dangerous::doRiskyThing ('blah', 123.45);


// From a regular method
class MoreDangerous {
    function doRiskyThing ($arg1, $arg2, $arg3) {
        echo '[', right_now (), "] Regular method call to email_error ().\n";
        flush ();
        email_error ('Something went wrong - regular method call');
    }
}
$obj = new MoreDangerous ();
$table = new Table ('TempFakeTable');
$obj->doRiskyThing (array (123, 456.0, '789', $table), null, array (true, -45.0 => 'stupid', 'gumby' => false));


function right_now () {
    global $start_time;
    
    return sprintf ('%11.8f', microtime (true) - $start_time);
}
?>
