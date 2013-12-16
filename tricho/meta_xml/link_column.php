<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * @package meta_xml
 */

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
        return array('LINK');
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
        // TODO: fix this kludge
        global $db;
        
        if (!($db instanceof Database)) return '';
        
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
        // TODO IMPROVE
        $path = tricho\Runtime::get('root_path') . 'admin/tables.xml';
        $db = Database::parseXML($path);
        
        list($table, $col) = @explode('.', $config['target']);
        $table = $db->get($table);
        if ($table == null) throw new Exception('Unknown table');
        $col = $table->get($col);
        if ($col == null) throw new Exception('Unknown column');
        $this->target = $col;
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
    
    
    function getInputField (Form $form, $input_value = '', $primary_key = null, $field_params = array()) {
        $q = $this->getSelectQuery();
        $res = execq($q);
        
        $out = '<select name="' . $this->getPostSafeName() . "\">\n";
        $out .= "<option value=\"\">- Select below -</option>\n";
        while ($row = fetch_assoc($res)) {
            if (count($row) > 1) {
                $value = $row['Value'];
            } else {
                $value = $row['ID'];
            }
            $out .= '<option value="' . hsc($row['ID']) . '"';
            if ($row['ID'] == $input_value) $out .= ' selected="selected"';
            $out .= '>' . hsc($value) . "</option>\n";
        }
        $out .= "</select>\n";
        
        // TODO: show query if required
        if ($_SESSION['setup']['view_q']) {
            $out .= "<pre>Q:\n" . hsc($q) . "</pre>\n";
        }
        
        return $out;
    }
}
