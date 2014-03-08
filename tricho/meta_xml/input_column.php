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
abstract class InputColumn extends Column {
    protected $text_size;
    
    
    function attachInputField(Form $form, $input_value = '', $primary_key = null, $field_params = array()) {
        $p = self::initInput($form);
        if ($this->sqltype == SQL_TYPE_BIT and strlen($input_value) == 1) {
            $ord = ord($input_value);
            if ($ord == 0 or $ord == 1) $input_value = $ord;
        }
        $params = array(
            'type' => 'text',
            'name' => $this->name,
            'id' => $form->getFieldId(),
            'value' => $input_value
        );
        if ($this->text_size > 0) $params['size'] = $this->text_size;
        $maxlength = (int) $this->getMaxLength();
        if ($maxlength > 0) $params['maxlength'] = $maxlength;
        
        // TODO: add onchange event
        HtmlDom::appendNewChild($p, 'input', $params);
    }
    
    
    /**
     * Gets the maximum length (for use as the maxlength attribute of an input
     * element), if applicable.
     * @return int If zero, no maxlength applies.
     */
    function getMaxLength() {
        return 0;
    }
    
    
    /**
     * Gets the text field size for this column.
     * 
     * @return string $size the size to use for the field.
     */
    function getTextSize () {
        return $this->text_size;
    }
    
    
    /**
     * Sets the text field size for this column.
     * 
     * Normal inputs need just a width in characters (W).
     * Textarea columns need a width and height in characters (WxH).
     * Rich text columns need a width and height in pixels (WxH).
     * 
     * @param string $size the size to use for the field.
     */
    function setTextSize ($size) {
        $this->text_size = (int) $size;
    }
    
    
    static function getConfigFormFields(array $config, $class) {
        return "    <p class=\"fake-tr\">\n".
            "        <span class=\"fake-td left-col\">Input size</span>\n".
            "        <span class=\"fake-td\"><input type=\"text\" name=\"{$class}_size\" value=\"".
            hsc(@$config['size']). "\"></span>\n" .
            "    </p>\n";
    }
}
?>
