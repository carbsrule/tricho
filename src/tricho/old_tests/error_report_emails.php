<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';

$start_time = microtime (true);

// TEST data
$_POST = array (
    'username' => 'fred',
    'Pass' => '1234555asdas',
    'payment' => array (
        'payment_type' => 'credit',
        'credit_card_number' => 1233434,
        'total_amount' => '123.00'
    )
);

$_SESSION['user'] = array (
    'username' => 'test@example.com',
    'password' => '1234',
    'credit_card' => '123444',
    'payment' => array (
        'askhd' => '1234',
        'another_layer' => array (
            'type' => 1,
            'pass' => 123444
        ),
        'status' => 'process'
    )
);

$_SESSION['payment'] = array (
    'type' => 'credit card',
    'credit_card' => '123444',
    'price' => '1110'
);


// OUTPUT
echo "<p>Send error report e-mails (Included POST and SESSION data in error e-mails)</p>\n";

echo "<pre>";

echo "Print fields may contain sensitive information - private_field_names: \n\n";

print_r(tricho\Runtime::get('private_field_names')) . "\n\n";

// From global scope
echo '[', right_now (), "] call to email_error ().\n\n";

$q = "SELECT ID FROM Test WHERE ID = {$_SESSION['user']['id']}";
execq($q, false, true);

echo "</pre>";

unset ($temp);

function right_now () {
    global $start_time;
    
    return sprintf ('%11.8f', microtime (true) - $start_time);
}
?>
