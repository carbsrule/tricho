<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../../tricho.php';
test_setup_login(true, SETUP_ACCESS_LIMITED);

require_once 'order_functions.php';

$db = Database::parseXML();
$table = $db->getTable($_GET['t']);

list ($in_list, $out_list) = get_in_out_lists ($table, $_GET['list']);


// if the IN list contains an ordernum will will output a message
$ordernum = false;
foreach ($in_list as $order_item) {
    if ($order_item[0]->getOption () == 'ordernum') {
        $ordernum = true;
        break;
    }
}


if ($_GET['sect'] == 'in') {
    if ($_GET['go'] == 'up') {
        // perform action and don't care
        switch ($_GET['list']) {
            case 'show':
                // todo: rejig to edit the view
                $item1 = $in_list[$_GET['id']];
                $item2 = $in_list[$_GET['id'] - 1];
                $item1->setViewOrder ($_GET['id'] - 1);
                $item2->setViewOrder ($_GET['id']);
                break;
                
            case 'order':
                $table->ChangeOrder ('view', $_GET['id'], true);
                break;
        }
        
    } else if ($_GET['go'] == 'down') {
        switch ($_GET['list']) {
            case 'show':
                // todo: rejig to edit the view
                $item1 = $in_list[$_GET['id']];
                if ($_GET['id'] == count($in_list) - 1) {
                    $item1->setViewOrder (-1);
                } else {
                    $item2 = $in_list[$_GET['id'] + 1];
                    $item1->setViewOrder ($_GET['id'] + 1);
                    $item2->setViewOrder ($_GET['id']);
                }
                break;
                
            case 'order':
                $table->ChangeOrder ('view', $_GET['id'], false);
                break;
                
        }
        
    } else if ($_GET['go'] == 'rev') {
        // reverse search order, ie "ASC"/"DESC"
        $col = $in_list[$_GET['id']][0];
        $table->changeOrderDirection ($col);
    }
    
} else if ($_GET['sect'] == 'out') {
    if ($_GET['go'] == 'up') {
        $item = $out_list[$_GET['id']];
        switch ($_GET['list']) {
            case 'show':
                // todo: rejig to edit the view
                $item->setViewOrder (count($in_list));
                break;
                
            case 'order':
                if ($item->getOption () == 'ordernum') $ordernum = true;
                $table->addToOrder ('view', $item);
                break;
        }
    }
}

if ($ordernum) {
    $_SESSION['setup']['ordernum_changed'] = true;
}

$url = 'table_edit_order_iframe.php?t=' . urlencode($_GET['t']) .
    "&list={$_GET['list']}";
$db->dumpXML('', $url);
?>
