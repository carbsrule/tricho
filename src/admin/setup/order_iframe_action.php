<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
test_setup_login (true, SETUP_ACCESS_LIMITED);
require 'order_functions.php';

/*
example URL (broken up):
    order_iframe_action.php?
    -->list=view_show
    -->sect=in
    go=down
    id=1
*/

$table = $_SESSION['setup']['create_table']['table'];

list ($in_list, $out_list) = get_in_out_lists ($table, $_GET['list']);

// echo "(Before) Table: <pre>"; print_r ($table); echo "</pre><br>\n";

if ($_GET['sect'] == 'in') {
    if ($_GET['go'] == 'up') {
        // perform action and don't care
        switch ($_GET['list']) {
            case 'show':
                
                // TODO rejig to edit the view
                
                $item1 = $in_list[$_GET['id']];
                $item2 = $in_list[$_GET['id'] - 1];
                $item1->setViewOrder ($_GET['id'] - 1);
                $item2->setViewOrder ($_GET['id']);
                break;
            case 'order':
                $table->ChangeOrder ('view', $_GET['id'], true);
                break;
                /*
            case 'edit_show':
                $table->changeEditCriteriaOrder ($_GET['id'], true);
                break;
            case 'edit_order':
                $table->ChangeOrder ('edit', $_GET['id'], true);
                break;
                */
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
            /*
            case 'edit_show':
                $table->changeEditCriteriaOrder ($_GET['id'], false);
                break;
            case 'edit_order':
                $table->ChangeOrder ('edit', $_GET['id'], false);
                break;
                */
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
                $item->setViewOrder (count($in_list));
                break;
            case 'order':
                $table->addToOrder ('view', $item);
                break;
                /*
            case 'edit_show':
                $table->addEditCriteria ($item);
                break;
            case 'edit_order':
                $table->addToOrder ('edit', $item);
                break;
                */
        }
    }
}

// echo "(After) Table: <pre>"; print_r ($table); echo "</pre><br>\n";

redirect ("order_iframe.php?list={$_GET['list']}");
?>
