<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

use \DOMDocument;
use \DOMElement;
use \DataValidationException;

use Tricho\DataUi\Form;
use Tricho\Util\HtmlDom;
use Tricho\Query\AliasedColumn;
use Tricho\Query\LogicConditionNode;
use Tricho\Query\OrderColumn;
use Tricho\Query\QueryFieldLiteral;
use Tricho\Query\QueryFunction;
use Tricho\Query\SelectQuery;

/**
 * Stores meta-data about a column with a choice of values
 * @package meta_xml
 */
class EnumColumn extends Column {
    protected $choices;
    
    
    function __construct($name, $table = null) {
        parent::__construct($name, $table = null);
        $this->mandatory = true;
    }
    
    
    static function getAllowedSqlTypes () {
        return array('ENUM');
    }
    
    static function getDefaultSqlType () {
        return 'ENUM';
    }
    
    
    /**
     * Gets the choices allowed in the ENUM
     * @return array The keys are the data to be stored in the database, and
     *         the values are their labels
     */
    function getChoices() {
        return $this->choices;
    }
    
    
    function toXMLNode(DOMDocument $doc) {
        $node = parent::toXMLNode($doc);
        foreach ($this->choices as $choice) {
            HtmlDom::appendNewChild($node, 'param', ['value' => $choice]);
        }
        return $node;
    }
    
    function applyXMLNode(DOMElement $node) {
        parent::applyXMLNode($node);
        $sql_defn = $node->getAttribute('sql_defn');
        $par_pos = strrpos($sql_defn, ')');
        if ($par_pos < 4) throw new Exception('Invalid ENUM definition');
        $this->sqltype = substr($sql_defn, 0, $par_pos + 1);
        
        $enum_choices = substr($this->sqltype, 5, -1);
        $values = enum_to_array($enum_choices);
        
        $labels = [];
        $params = $node->getElementsByTagName('param');
        foreach ($params as $param) {
            $labels[] = $param->getAttribute('value');
        }
        $this->choices = array_combine($values, $labels);
    }
    
    
    static function getConfigFormFields(array $config, $class) {
        $db = Database::parseXML();
        
        $fields = "<p>Choices</p>\n";
        if (!isset($config['choices'])) $config['choices'] = [];
        $max = count($config['choices']) + 3;
        if ($max < 5) $max = 5;
        reset($config['choices']);
        for ($i = 0; $i < $max; ++$i) {
            list($junk, $choice) = each($config['choices']);
            $value = $choice['value'];
            $label = $choice['label'];
            $fields .= "<p>";
            $fields .= "<label for=\"enum_value_{$i}\">Value</label> ";
            $fields .= "<input id=\"enum_value_{$i}\" type=\"text\"";
            $fields .= " name=\"{$class}_choices[{$i}][value]\"";
            $fields .= ' value="' . hsc($value) . '">';
            
            $fields .= " &nbsp; <label for=\"enum_label_{$i}\">Label</label> ";
            $fields .= "<input id=\"enum_label_{$i}\" type=\"text\"";
            $fields .= " name=\"{$class}_choices[{$i}][label]\"";
            $fields .= ' value="' . hsc($label) . '">';
            $fields .= "</p>\n";
        }
        return $fields;
    }
    
    
    function getConfigArray() {
        $config = parent::getConfigArray();
        $config['choices'] = [];
        foreach ($this->choices as $value => $label) {
            $config['choices'][] = ['value' => $value, 'label' => $label];
        }
        return $config;
    }
    
    
    function applyConfig(array $config, array &$errors) {
        $this->choices = [];
        foreach ($config['choices'] as $i => $choice) {
            if (empty($choice['value']) and empty($choice['label'])) break;
            $this->choices[$choice['value']] = $choice['label'];
        }
        if (count($this->choices) == 0) {
            $errors[] = 'Must specify choices';
            return;
        }
        
        $this->sqltype .= '(';
        $choice_num = 0;
        foreach (array_keys($this->choices) as $choice) {
            if (++$choice_num != 1) $this->sqltype .= ',';
            $this->sqltype .= sql_enclose((string) $choice, false);
        }
        $this->sqltype .= ')';
    }
    
    
    function attachInputField(Form $form, $input_value = '', $primary_key = null, $field_params = array()) {
        $p = self::initInput($form);
        
        $params = ['name' => $this->getPostSafeName()];
        $select = HtmlDom::appendNewChild($p, 'select', $params);
        $params = array('value' => '');
        $option = HtmlDom::appendNewChild($select, 'option', $params);
        HtmlDom::appendNewText($option, '- Select below -');
        
        foreach ($this->choices as $choice => $choice_label) {
            $params = array('value' => $choice);
            if ($choice == $input_value) $params['selected'] = 'selected';
            $option = HtmlDom::appendNewChild($select, 'option', $params);
            HtmlDom::appendNewText($option, $choice_label);
        }
    }
    
    
    function displayValue ($input_value = '') {
        return hsc($this->choices[$input_value]);
    }
    
    
    function collateInput($input, &$original_value) {
        if (isset($this->choices[$input]) or $input == '') {
            $original_value = $input;
            return [$this->name => $input];
        }
        throw new DataValidationException('Nonexistent value');
    }
    
    
    function isInputEmpty(array $input) {
        $value = (string) reset($input);
        if ($value == '' and !isset( $this->choices[''])) return true;
        return false;
    }
    
    
    // ENUMs are always mandatory; the DB value has to be one of the choices
    function setMandatory($bool) {
        $this->mandatory = true;
    }
}
