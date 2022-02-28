<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

use DOMDocument;
use DOMElement;
use DataValidationException;

use Tricho\DataUi\Form;
use Tricho\Util\HtmlDom;
use Tricho\Query\AliasedColumn;
use Tricho\Query\IdentifierQuery;
use Tricho\Query\LogicConditionNode;
use Tricho\Query\OrderColumn;
use Tricho\Query\QueryFieldLiteral;
use Tricho\Query\SelectQuery;


/**
 * Stores meta-data about a column that uses a text or textarea input field
 * @package meta_xml
 */
class LinkColumn extends Column {
    protected $target;
    protected $type = 'select';
    protected $is_parent = false;

    function getTarget() {
        return $this->target;
    }

    function setTarget($target) {
        if (!($target instanceof Column)) $target = (string) $target;
        $this->target = $target;
    }

    /**
     * Gets the table which the target column belongs to
     *
     * This is shorthand for ->getTarget()->getTable()
     *
     * @return Table|null
     */
    function getTargetTable()
    {
        return $this->target->getTable();
    }


    function isParentLink() {
        return $this->is_parent;
    }


    static function getAllowedSqlTypes () {
        return array('LINK');
    }

    static function getDefaultSqlType () {
        return 'LINK';
    }


    /**
     * Creates a DOMElement that represents this column (for use in tables.xml)
     * @param DOMDocument $doc The document to which this node will belong
     * @return DOMElement
     * @author benno, 2013-02-28
     */
    function toXMLNode (DOMDocument $doc) {
        $node = parent::toXMLNode($doc);
        $param = HtmlDom::appendNewChild($node, 'param');
        $target_table = $this->target->getTable()->getName();
        $target = $target_table . '.' . $this->target->getName();
        $param->setAttribute('name', 'target');
        $param->setAttribute('value', $target);
        return $node;
    }


    function getConfigArray () {
        $config = parent::getConfigArray ();
        $config['target'] = $this->target->getTable()->getName() . '.' .
            $this->target->getName();
        return $config;
    }


    /**
     * @author benno 2012-10-27
     */
    function applyXMLNode(DOMElement $node) {
        parent::applyXMLNode($node);
        $param_nodes = $node->getElementsByTagName('param');
        foreach ($param_nodes as $param) {
            $name = $param->getAttribute('name');
            $value = $param->getAttribute('value');
            switch ($name) {
            case 'target':
                $this->target = $value;
                break;
            case 'type':
                $this->type = $type;
                break;
            }
        }
    }

    static function getConfigFormFields(array $config, $class) {
        $db = Database::parseXML();

        $fields = "<p>Target <select name=\"{$class}_target\">\n";
        $fields .= "<option value=\"\">- Select below -</option>\n";

        $tables = $db->getTables();
        foreach ($tables as $table) {
            $pks = $table->getPKnames();
            if (count($pks) != 1) continue;
            $target = "{$table->getName()}.{$pks[0]}";
            if (@$config['target'] == $target) {
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }
            $target = hsc($target);
            $fields .= "<option value=\"{$target}\"{$selected}>" .
                "{$target}</option>\n";
        }
        $fields .= "</select></p>\n";
        return $fields;
    }


    /**
     * @author benno, 2013-02-28
     */
    function applyConfig(array $config, array &$errors) {
        $db = Database::parseXML();

        @list($table, $col) = explode('.', $config['target']);
        $table = $db->get($table);
        if ($table == null) {
            $errors[] = 'Unknown table';
            return;
        }
        $col = $table->get($col);
        if ($col == null) {
            $errors[] = 'Unknown column';
            return;
        }
        $this->target = $col;
        $this->sql_collation = $col->sql_collation;
    }


    function getSelectQuery()
    {
        $target_table = $this->target->getTable();

        $q = new IdentifierQuery($target_table);
        $id_col = new AliasedColumn($target_table, $this->target, 'ID');
        $q->addSelectField($id_col);

        $order_column = new OrderColumn($q->getSelectFieldByAlias('Value'));
        $q->addOrderBy($order_column);

        return $q;
    }


    function attachInputField(Form $form, $input_value = '', $primary_key = null, $field_params = array()) {
        $p = self::initInput($form);

        $q = $this->getSelectQuery();
        $res = execq($q);

        if (@$this->display_as == 'radio') {
            $this->attachRadios($res, $p, $form, $input_value, $primary_key);
        } else {
            $this->attachSelect($res, $p, $form, $input_value, $primary_key);
        }
    }


    function attachRadios($res, $p, $form, $input_value, $primary_key)
    {
        $id = $form->getFieldId();

        while ($row = fetch_assoc($res)) {
            if (count($row) > 1) {
                $label_text = $row['Value'];
            } else {
                $label_text = $row['ID'];
            }

            $field_id = $id . '_' . preg_replace('/[^a-z0-9]/', '', strtolower($value));
            $params = ['for' => $field_id];
            $label_el = HtmlDom::appendNewChild($p, 'label', $params);

            $params = [
                'id' => $field_id,
                'name' => $this->getPostSafeName(),
                'value' => $row['ID'],
            ];
            if ($params['value'] == $input_value) {
                $params['checked'] = 'checked';
            }
            $input = HtmlDom::appendNewChild($label_el, 'input', $params);

            HtmlDom::appendNewText($label_el, $label_text);
        }
    }


    function attachSelect($res, $p, $form, $input_value, $primary_key)
    {
        $params = array(
            'name' => $this->getPostSafeName(),
            'id' => $form->getFieldId()
        );
        $select = HtmlDom::appendNewChild($p, 'select', $params);
        $params = array('value' => '');
        $option = HtmlDom::appendNewChild($select, 'option', $params);
        HtmlDom::appendNewText($option, '- Select below -');
        while ($row = fetch_assoc($res)) {
            if (count($row) > 1) {
                $value = $row['Value'];
            } else {
                $value = $row['ID'];
            }
            $params = array('value' => $row['ID']);
            if ($row['ID'] == $input_value) $params['selected'] = 'selected';
            $option = HtmlDom::appendNewChild($select, 'option', $params);
            HtmlDom::appendNewText($option, $value);
        }

        if (@$_SESSION['setup']['view_q']) {
            $pre = HtmlDom::appendNewChild($p, 'pre');
            HtmlDom::appendNewText($pre, "Q:\n{$q}");
        }
    }


    function displayValue ($input_value = '') {
        $q = $this->getSelectQuery();
        $val = new QueryFieldLiteral($input_value);
        $cond = new LogicConditionNode($this->target, LOGIC_CONDITION_EQ, $val);
        $q->getWhere()->addCondition($cond);
        if (@$_SESSION['setup']['view_q']) {
            $pre = "<pre>Q: {$q}</pre>";
        } else {
            $pre = '';
        }
        $res = execq($q);
        $row = fetch_row($res);
        return $pre . hsc($row[1]);
    }


    function attachValue(Form $form, $value, array $pk) {
        $doc = $form->getDoc();
        $form_el = $doc->getElementsByTagName('form')->item(0);

        $q = $this->getSelectQuery();
        $q->getWhere()->addNewCondition($this->target, '=', $value);
        if (@$_SESSION['setup']['view_q']) {
            $pre = HtmlDom::appendNewChild($form_el, 'pre');
            HtmlDom::appendNewText($pre, "Q: {$q}");
        }

        $res = execq($q);
        $row = fetch_assoc($res);

        $p = HtmlDom::appendNewChild($form_el, 'p');
        HtmlDom::appendNewText($p, $row['Value']);
    }


    /**
     * @author benno, 2013-12-18
     */
    function collateInput($input, &$original_value) {
        $values = $this->target->collateInput($input, $original_value);
        $value = reset($values);
        $original_value = $value;
        if ($this->target->isInputEmpty($values)) {
            return array($this->name => $value);
        }

        $col = $this->target;
        $table = $this->target->getTable();
        $q = new SelectQuery($table);
        $q->addSelectField($col);
        $qval = new QueryFieldLiteral($value);
        $cond = new LogicConditionNode($col, LOGIC_CONDITION_EQ, $qval);
        $q->getWhere()->setRoot($cond);
        $res = execq($q);
        $count = $res->rowCount();
        $res->closeCursor();
        if ($count == 0) {
            throw new DataValidationException('Nonexistent value');
        }
        return array($this->name => $value);
    }


    function getInfo() {
        return '&#8658; ' . $this->target->getFullName();
    }
}
