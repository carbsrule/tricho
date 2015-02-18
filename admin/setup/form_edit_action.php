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

$file = Runtime::get('root_path') . 'tricho/data/' . $form_name . '.form.xml';
$items = null;
$doc = new DOMDocument();
$doc->preserveWhiteSpace = false;
$comments = [];
if (file_exists($file)) {
    $doc->load($file);
    $forms = $doc->getElementsByTagName('form');
    $form = $forms->item(0);
    if (!$form) throw new Exception('Invalid extant file');
    $items = $form->getElementsByTagName('items');
    if ($items->length > 0) {
        $items = $items->item(0);
        while ($items->hasChildNodes()) {
            // Preserve comments
            $last = $items->lastChild;
            $last_name = $last->getAttribute('name');
            if ($last->hasChildNodes()) {
                $last_child = $last->firstChild;
                if ($last_child->nodeType == XML_COMMENT_NODE) {
                    $comments[$last_name] = $last_child->data;
                }
            }
            $items->removeChild($items->lastChild);
        }
    }
} else {
    $form = HtmlDom::appendNewChild($doc, 'form');
}

$form->setAttribute('table', $_POST['table']);
if ($_POST['modifier'] != '') {
    $form->setAttribute('modifier', $_POST['modifier']);
} else {
    $form->removeAttribute('modifier');
}

$table = $db->get($_POST['table']);
if (!$table) goto no_cols;

if ($items == null) $items = HtmlDom::appendNewChild($form, 'items');
if (empty($_POST['cols'])) goto no_cols;
foreach ($_POST['cols'] as $key => $col) {
    $attrs = ['name' => $col];
    if (!empty($_POST['labels'][$key])) {
        $attrs['label'] = $_POST['labels'][$key];
    }
    if (!empty($_POST['apply'][$key])) {
        $attrs['apply'] = $_POST['apply'][$key];
    }
    $item = HtmlDom::appendNewChild($items, 'field', $attrs);
    if (isset($comments[$col])) {
        $item->appendChild($doc->createComment($comments[$col]));
    }
}
no_cols:

/*
$cdata = $doc->createCDATASection(print_r($_POST, true));
$form->appendChild($cdata);
*/

$doc->formatOutput = true;
$contents = @$doc->saveXML();

// use 4 spaces instead of 2 for indenting
if (strpos($contents, "\n  <items") !== false) {
    $contents = preg_replace('/^( +)</m', '$1$1<', $contents);
}
$bytes = file_put_contents($file, $contents);

if ($bytes > 0) {
    $_SESSION['setup']['msg'] = 'Form saved';
} else {
    $_SESSION['setup']['err'] = 'Form save failed';
}
if (!empty($_POST['r'])) redirect($_POST['r']);
redirect('./');
