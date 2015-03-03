<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';
test_admin_login();

$db = Database::parseXML();
$table = $db->getTable ($_GET['t']);
alt_page_redir($table, 'main_order');

if (!$table->checkAuth ()) {
    $_SESSION[ADMIN_KEY]['err'] = 'Invalid table';
    redirect ('./');
}

list ($urls, $seps) = $table->getPageUrls ();

$primary_key_cols = $table->getIndex ('PRIMARY KEY');

$_SESSION[ADMIN_KEY]['search_params'] = array ();

// possible actions for this page:
// rem (uses del[Primary Key]), add, {prev, next} (both use start)

// determine if there is a column with the ordernum option (if so, need to re-order upon deletion)
$ordernum_col = null;
$columns = $table->getColumns ();
foreach ($columns as $col) {
    if ($col->getOption () == 'ordernum') {
        $ordernum_col = $col;
        break;
    }
}

if ($ordernum_col != null) {
    // get ordernum (and extra parameters if necessary) of chosen row from database
    $ordernum_name = $ordernum_col->getName ();
    $order_params_arr = array ();
    $order_fields = $table->getOrder ('view');
    foreach ($order_fields as $field) {
        if ($field[0] === $ordernum_col) {
            break;
        } else {
            $order_params_arr[] = $field[0]->getName ();
        }
    }
    $order_params = '';
    if (count($order_params_arr) > 0) {
        foreach ($order_params_arr as $col_name) {
            $order_params .= ", `{$col_name}`";
        }
    }
    
    $q = "SELECT `{$ordernum_name}`{$order_params} FROM `". $table->getName (). "` WHERE ";
    $ids = explode (',', $_GET['id']);
    $pk_clause = '';
    if (count($ids) == count($primary_key_cols)) {
        reset($ids);
        reset($primary_key_cols);
        $i = 0;
        while (list($junk, $id) = each($ids)) {
            list($junk, $primary_key) = each($primary_key_cols);
            if ($i++ > 0) $pk_clause .= ' AND ';
            $pk_clause .= '`'. $primary_key->getName (). "` = ". sql_enclose ($id);
        }
    }
    $q .= $pk_clause;
    // echo "Q1: {$q}<br>\n";
    $res = execq($q);
    if ($res->rowCount() == 1) {
        $self = fetch_assoc($res);
        // find (get primary key of) previous (up) or next (down) record
        if ($_GET['d'] == 'u') {
            $mod = -1;
        } else {
            $mod = 1;
        }
        $new_ordernum = $self[$ordernum_name] + $mod;
        $q = "SELECT ";
        $i = 0;
        foreach ($primary_key_cols as $primary_key) {
            if ($i++ > 0) $q .= ', ';
            $q .= '`' . $primary_key->getName () . '`';
        }
        $q .= " FROM `". $table->getName (). "` WHERE `{$ordernum_name}` = {$new_ordernum}";
        if (count($order_params_arr) > 0) {
            foreach ($order_params_arr as $param_name) {
                if ($self[$param_name] === null) {
                    $q .= " AND `{$param_name}` IS NULL";
                } else {
                    $q .= " AND `{$param_name}` = ". sql_enclose ($self[$param_name]);
                }
            }
        }
        // echo "Q2: {$q}<br>\n";
        $res = execq($q);
        if ($res->rowCount() == 1) {
            $other = fetch_assoc($res);
            $q = "UPDATE `". $table->getName (). "` SET `{$ordernum_name}` = {$new_ordernum} WHERE ". $pk_clause;
            $qs = "{$q};\n";
            // echo "Q3: {$q}<br>\n";
            execq($q);
            
            $q = "UPDATE `". $table->getName (). "` SET `{$ordernum_name}` = {$self[$ordernum_name]} WHERE ";
            $i = 0;
            foreach ($primary_key_cols as $primary_key) {
                if (++$i > 1) $q .= ' AND ';
                $q .= '`' . $primary_key->getName (). "` = ". sql_enclose ($other[$primary_key->getName ()]);
            }
            // echo "Q4: {$q}<br>\n";
            $qs .= "{$q};";
            execq($q);
            if ($table->isStatic ()) {
                log_action ($db, "Updated order in static table ". $table->getName (), $qs);
            }
        }
    }
}


$url = "{$urls['main']}{$seps['main']}t=". $table->getName ();
if ($table->getDisplayStyle () == TABLE_DISPLAY_STYLE_TREE) {
    $url .= '&_open='. $_GET['id'];
}
if ($_GET['p'] != '') {
    $url .= '&p=' . $_GET['p'];
}
redirect ($url);

?>
