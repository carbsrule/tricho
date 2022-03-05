<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\DataUi\Form;
use Tricho\DataUi\FormModifier;
use Tricho\Meta\Database;
use Tricho\Query\SelectQuery;
use Tricho\Query\QueryFieldLiteral;

require_once '../tricho.php';
test_admin_login();
$db = Database::parseXML();
$table = $db->getTable ($_POST['_t']);
alt_page_redir($table, 'add_action');

if (!$table->checkAuth ()) {
    $_SESSION[ADMIN_KEY]['err'] = 'Invalid table';
    redirect ('./');
}

// TODO: move to separate class in db_interface
class StaticAddModifier extends FormModifier {
    function postProcess(Form $form, array $data, $insert_id, array $codes) {
        // The newly-inserted row is selected again here so that passwords that
        // are stored in an encrypted state don't get logged with their plain
        // text state, e.g. SET Password = SHA('my_password'). In addition,
        // only end data are logged, not function calls.
        $db = Database::parseXML();

        $table = $form->getTable();
        $cols = $table->getColumns();
        $pk_col_names = $table->getPKnames();
        $pk_values = (array) $insert_id;
        $pk = array_combine($pk_col_names, $pk_values);

        $q = new SelectQuery($table);
        foreach ($cols as $col) {
            $q->addSelectField($col);
        }
        foreach ($pk as $col_name => $value) {
            $col = $table->get($col_name);
            $q->getWhere()->addNewCondition($col, '=', $value, LOGIC_TREE_AND);
        }

        $res = execq($q);
        $log_error = false;
        $rows = (int) @$res->rowCount();
        if ($rows == 1) {
            if ($row = @fetch_assoc($res)) {

                $q = 'INSERT INTO `'. $table->getName (). '` SET ';
                $field_num = 0;
                foreach ($row as $field => $value) {
                    if (++$field_num > 1) $q .= ', ';

                    // if it is known that a field is numeric, don't use string values in the logged update query
                    if ($value !== null) {
                        $col_ref = $table->get ($field);
                        if ($col_ref != null and $col_ref->isNumeric ()) {
                            $value = new QueryFieldLiteral ($value, false);
                        }
                    }
                    $q .= "`{$field}` = ". sql_enclose ($value, false);
                }

                log_action("Added row in static table " . $table->getName(), $q);
            } else {
                $log_error = "failed to fetch row";
            }
        } else {
            $log_error = "query returned {$rows} rows";
        }
        if ($log_error) {
            $message = "Failed to log a query after a row in ". $table->getName ().
                " was inserted, for the following reason:\n\n{$log_error}\n\nThe query was:\n{$q}\n\n";
            email_error ($message);
        }
    }
}

list($urls, $seps) = $table->getPageUrls(['add', 'browse']);
$form_url = $urls['add'] . $seps['add'] . 't=' . $table->getName();
if (empty($_POST['_f'])) redirect($form_url);
$id = $_POST['_f'];
$form = new Form($id);

// if this is a static table, fetch the newly inserted values and log them
if ($table->isStatic()) $form->setModifier(new StaticAddModifier());
$form->setFormURL($form_url);
$success_url = $urls['browse'] . $seps['browse'] . 't=' . $table->getName();
$form->setSuccessURL($success_url);
$form->load("admin.{$table->getName()}");
$form->setType('add');
$form->process();
