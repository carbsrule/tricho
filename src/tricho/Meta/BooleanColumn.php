<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

use Tricho\DataUi\Form;
use Tricho\Util\HtmlDom;

/**
 * Stores meta-data about a column that stores a boolean value (0 or 1)
 * @package meta_xml
 */
class BooleanColumn extends Column {
    
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
    
    
    function attachInputField(Form $form, $input_value = '', $primary_key = null, $field_params = array()) {
        $p = self::initInput($form);
        
        // TODO: support other representations, e.g. radio buttons 'Yes' and 'No'
        $params = array(
            'type' => 'checkbox',
            'name' => $this->name,
            'id' => $form->getFieldId(),
            'value' => 1
        );
        
        $default = $this->getDefault();
        
        // BIT fields are returned as 1-byte strings, need to convert to ints
        if ($this->sqltype == 'BIT') {
            $input_value = ord($input_value);
        }
        
        if ($input_value === '' and $default != '') $input_value = $default;
        if ($input_value != '' and $input_value != '0') $input_value = '1';
        if ($input_value == '1') {
            $params['checked'] = 'checked';
        }
        
        // TODO: add onchange event
        HtmlDom::appendNewChild($p, 'input', $params);
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