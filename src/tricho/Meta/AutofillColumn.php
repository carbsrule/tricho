<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

use DOMDocument;
use DOMElement;

use Tricho\DataUi\Form;
use tricho\Query\OrderColumn;
use Tricho\Util\HtmlDom;

class AutofillColumn extends LinkColumn {
    /** URL to look up available options via AJAX **/
    protected $url;
    
    
    /**
     * Creates a DOMElement that represents this column (for use in tables.xml)
     * @param DOMDocument $doc The document to which this node will belong
     * @return DOMElement
     * @author benno, 2015-06-15
     */
    function toXMLNode(DOMDocument $doc) {
        $node = parent::toXMLNode($doc);
        $param = HtmlDom::appendNewChild($node, 'param');
        $param->setAttribute('name', 'url');
        $param->setAttribute('value', $this->url);
        return $node;
    }
    
    
    /**
     * @author benno, 2015-06-15
     */
    function getConfigArray() {
        $config = parent::getConfigArray();
        $config['url'] = $this->url;
        return $config;
    }
    
    
    /**
     * @author benno, 2015-06-15
     */
    function applyXMLNode(DOMElement $node) {
        parent::applyXMLNode($node);
        $param_nodes = $node->getElementsByTagName('param');
        foreach ($param_nodes as $param) {
            $name = $param->getAttribute('name');
            if ($name != 'url') continue;
            $this->url = $param->getAttribute('value');
            break;
        }
    }
    
    
    static function getConfigFormFields(array $config, $class) {
        $fields = parent::getConfigFormFields($config, $class);
        $fields .= "<p>Lookup URL: " . ROOT_PATH_WEB .
            "<input name=\"{$class}_url\" value=\"" . hsc(@$config['url']) .
            "\"></p>\n";
        return $fields;
    }
    
    
    function applyConfig(array $config, array &$errors) {
        parent::applyConfig($config, $errors);
        
        $this->url = $config['url'];
    }
    
    
    function attachInputField(Form $form, $input_value = '', $primary_key = null, $field_params = array()) {
        $p = self::initInput($form);
        
        $q = $this->getSelectQuery();
        $q->getWhere()->addNewCondition($this->target, '=', $input_value);
        $res = execq($q);
        $row = fetch_assoc($res);
        $name = @$row['Value'];
        
        $params = [
            'id' => $form->getFieldId() . '_lookup',
            'type' => 'text',
            'name' => $this->getPostSafeName() . '_lookup',
            'value' => $name,
            'autocomplete' => 'off',
        ];
        HtmlDom::appendNewChild($p, 'input', $params);
        
        $params = [
            'id' => $form->getFieldId(),
            'type' => 'hidden',
            'name' => $this->getPostSafeName(),
            'value' => $input_value
        ];
        HtmlDom::appendNewChild($p, 'input', $params);
        
        $params = ['type' => 'text/javascript'];
        $script = HtmlDom::appendNewChild($p, 'script', $params);
        $url = addslashes(ROOT_PATH_WEB . $this->url);
        $text = "init_autofill('{$form->getFieldId()}', '{$url}');";
        HtmlDom::appendNewText($script, $text);
    }
}
