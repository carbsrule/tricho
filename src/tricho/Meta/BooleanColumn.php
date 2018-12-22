<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

use DOMElement;
use InvalidArgumentException;
use Tricho\DataUi\Form;
use Tricho\Util\HtmlDom;


/**
 * Stores meta-data about a column that stores a boolean value (0 or 1)
 * @package meta_xml
 */
class BooleanColumn extends Column {
    /**
     * Text choices to specify for radio buttons.
     * Otherwise a checkbox will be used.
     * The array indexes (0 and 1) are the values which will be saved
     * e.g. ['No', 'Yes']
     */
    protected $choices = [];
    
    function __construct ($name, $table = null) {
        parent::__construct ($name, $table);
        $this->sql_attributes = array ('UNSIGNED', 'NOT NULL');
    }
    
    static function getAllowedSqlTypes () {
        return array ('TINYINT', 'BIT');
    }
    
    static function getDefaultSqlType () {
        return 'TINYINT';
    }
    
    function setSqlType($type) {
        parent::setSqlType($type);
        if ($type == 'BIT') array_remove('UNSIGNED', $this->sql_attributes);
    }
    
    function setChoices(array $choices) {
        if (!in_array(count($choices), [0, 2])) {
            throw new InvalidArgumentException('Must contain 0 or 2 elements');
        }
        if (count($choices) == 2) {
            if (!isset($choices[0]) or !isset($choices[1])) {
                throw new InvalidArgumentException('Must be a numerically-indexed array');
            }
        }
        $this->choices = $choices;
    }
    
    function attachInputField(Form $form, $input_value = '', $primary_key = null, $field_params = array()) {
        $p = self::initInput($form);
        
        $default = $this->getDefault();
        
        // BIT fields are returned as 1-byte strings, need to convert to ints
        if ($this->sqltype == 'BIT') {
            $input_value = ord($input_value);
        }
        
        if ($input_value === '' and $default != '') $input_value = $default;
        if ($input_value != '' and $input_value != '0') $input_value = '1';
        
        // Use radio buttons if text choices specified
        if (count($this->choices) == 2) {
            $sibling = $p->previousSibling;
            while ($sibling and (!($sibling instanceof DOMElement) or $sibling->tagName != 'p')) {
                $sibling = $sibling->previousSibling;
            }
            
            if ($sibling) {
                $labels = $sibling->getElementsByTagName('label');
                if ($labels->length > 0) {
                    HtmlDom::removeWrapper($labels->item(0));
                }
            }
            
            // Allow canonical order of 0,1 or irregular 1,0
            $choices = $this->choices;
            reset($choices);
            $label_text = current($choices);
            $val = key($choices);
            
            $id0 = $form->getFieldId();
            $params = [
                'type' => 'radio',
                'name' => $this->name,
                'value' => $val,
                'id' => $id0,
            ];
            if ($input_value !== '' and $input_value == $val) {
                $params['checked'] = 'checked';
            }
            HtmlDom::appendNewChild($p, 'input', $params);
            $label = HtmlDom::appendNewChild($p, 'label', ['for' => $id0]);
            HtmlDom::appendNewText($label, $label_text);
            
            $label_text = next($choices);
            $val = key($choices);
            
            $form->incrementFieldNum();
            $id1 = $form->getFieldId();
            $params['id'] = $id1;
            $params['value'] = $val;
            unset($params['checked']);
            if ($input_value !== '' and $input_value == $val) {
                $params['checked'] = 'checked';
            }
            HtmlDom::appendNewChild($p, 'input', $params);
            $label = HtmlDom::appendNewChild($p, 'label', ['for' => $id1]);
            HtmlDom::appendNewText($label, $label_text);
            return;
        }
        
        $params = [
            'type' => 'checkbox',
            'name' => $this->name,
            'id' => $form->getFieldId(),
            'value' => 1
        ];
        if ($input_value == '1') {
            $params['checked'] = 'checked';
        }
        
        // TODO: add onchange event
        HtmlDom::appendNewChild($p, 'input', $params);
    }


    /**
     * Adds a display-only value to a Form (for a non-editable field)
     *
     * @param Form $form The form on which to display the value
     * @param string $value The value to be displayed
     * @param array $pk The primary key of the row which contains the value
     * @return void
     */
    function attachValue(Form $form, $value, array $pk) {
        $doc = $form->getDoc();
        $form_el = $doc->getElementsByTagName('form')->item(0);
        $p = $doc->createElement('p');
        $form_el->appendChild($p);

        if (array_key_exists($value, $this->choices)) {
            HtmlDom::appendNewText($p, $this->choices[$value]);
        } else {
            HtmlDom::appendNewText($p, $value);
        }
    }


    function collateInput($input, &$original_value) {
        $value = (int) $input;
        if ($value != 1) $value = 0;
        $original_value = $value;
        return array($this->name => $value);
    }
    
    
    function getTD($data, $pk) {
        return '<td>' . ($data? 'Y': 'N') . '</td>';
    }
}
?>
