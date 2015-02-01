<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use tricho\Runtime;

require '../../tricho.php';
test_setup_login();

$form_name = basename(@$_POST['form'], '.form.xml');
$name_pattern = '/^[-_a-z0-9]+(\.[-_a-z0-9]+)*$/i';
if (empty($_POST['table']) or !preg_match($name_pattern, $form_name)) {
    $_SESSION['setup']['err'] = 'Invalid submission';
    redirect('./');
}

$db = Database::parseXML();
$table = $db->get($_POST['table']);
if (!$table) {
    $_SESSION['setup']['err'] = 'Invalid submission';
    redirect('./');
}

$doc = new DOMDocument();
$attrs = ['table' => $_POST['table']];
$form = HtmlDom::appendNewChild($doc, 'form', $attrs);

if (empty($_POST['cols'])) goto no_cols;
$table = $db->get($_POST['table']);
if (!$table) goto no_cols;

$items = HtmlDom::appendNewChild($form, 'items');
foreach ($_POST['cols'] as $key => $col) {
    $attrs = ['name' => $col];
    if (!empty($_POST['labels'][$key])) {
        $attrs['label'] = $_POST['labels'][$key];
    }
    if (!empty($_POST['apply'][$key])) {
        $attrs['apply'] = $_POST['apply'][$key];
    }
    HtmlDom::appendNewChild($items, 'field', $attrs);
}
no_cols:

/*
$cdata = $doc->createCDATASection(print_r($_POST, true));
$form->appendChild($cdata);
*/

$doc->formatOutput = true;
$file = Runtime::get('root_path') . 'tricho/data/' . $form_name . '.form.xml';
$bytes = @$doc->save($file);
if ($bytes > 0) {
    $_SESSION['setup']['msg'] = 'Form saved';
} else {
    $_SESSION['setup']['err'] = 'Form save failed';
}
redirect('./');
