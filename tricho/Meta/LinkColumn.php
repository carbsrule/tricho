<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

use \DOMDocument;
use \DOMElement;
use \Form;

use Tricho\Util\HtmlDom;

/**
 * Stores meta-data about a column that uses a text or textarea input field
 * @package meta_xml
 */
class LinkColumn extends Column {
    protected $target;
    protected $type;
    
    function getTarget() {
        return $this->target;
    }
    
    function setTarget($target) {
        if (!($target instanceof Column)) $target = (string) $target;
        $this->target = $target;
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
            if ($name != 'target') continue;
            $value = $param->getAttribute('value');
            $this->target = $value;
            break;
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
    
    
    function getSelectQuery() {
        $target_table = $this->target->getTable();
        $q = new SelectQuery($target_table);
        $id_col = new AliasedColumn($target_table, $this->target, 'ID');
        $q->addSelectField($id_col);
        
        $func = false;
        $ident = $target_table->getRowIdentifier();
        if (count($ident) > 0) {
            $func = new QueryFunction('CONCAT');
            $func->setAlias('Value');
            foreach ($ident as $part) {
                if ($part instanceof Column) {
                    $null_func = new QueryFunction('IFNULL', array($part, '?'));
                    $func->addParam($null_func);
                } else {
                    $func->addParam(new QueryFieldLiteral($part));
                }
            }
        }
        if ($func) {
            $q->addSelectField($func);
            $q->addOrderBy(new OrderColumn($func));
        }
        return $q;
    }
    
    
    function attachInputField(Form $form, $input_value = '', $primary_key = null, $field_params = array()) {
        $p = self::initInput($form);
        
        $q = $this->getSelectQuery();
        $res = execq($q);
        
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
