<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
?>
<h1>Test e-mail validation</h1>
<style type="text/css">
table td {
    font-family: sans-serif;
}
</style>
<?php
$addresses = array (
    'test' => false,
    'test@test' => false,
    'test@example.org' => true,
    'test.test@example.org' => true,
    'test,test@example.test' => false,
    'test@example.test' => false,
    "bill.o'shanahan@oshanahan.ie" => true,
    "'@example.org" => true,
    "Isuck.399@hotmail.com" => true
);

$col = new EmailColumn('Email');
$col->setValidationType('basic');

echo "<table>";
echo "<tr><th>Address</td><td>Valid</td><td>As expected</td></tr>\n";
$junk = '';
foreach ($addresses as $email => $should_validate) {
    try {
        $col->collateInput($email, $junk);
        $valid = true;
        $valid_text = 'Y';
    } catch (DataValidationException $ex) {
        $valid = false;
        $valid_text = 'N: ' . $ex->getMessage();
    }
    
    $expected = ($should_validate == $valid? 'Y': 'N');
    
    echo "<tr><td>{$email}</td><td>{$valid_text}</td><td>{$expected}</td></tr>\n";
}
echo "</table>";
?>
