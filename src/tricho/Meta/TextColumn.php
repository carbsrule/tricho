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
 * Stores meta-data about a column that stores a large block of text
 * @package meta_xml
 */
class TextColumn extends StringColumn {
    static function getAllowedSqlTypes () {
        return array ('TEXT', 'TINYTEXT', 'MEDIUMTEXT', 'LONGTEXT', 'CHAR', 'VARCHAR');
    }
    
    static function getDefaultSqlType () {
        return 'TEXT';
    }
    
    
    static function getConfigFormFields(array $config, $class) {
        @list($cols, $rows) = explode('x', $config['size']);
        settype ($cols, 'int');
        settype ($rows, 'int');
        if ($cols == 0) $cols = '';
        if ($rows == 0) $rows = '';
        return "    <p class=\"fake-tr\">\n".
            "        <span class=\"fake-td left-col\">Input size</span>\n".
            "        <span class=\"fake-td\">".
                "<input type=\"text\" name=\"{$class}_cols\" value=\"". hsc ($cols). "\"> cols, ".
                "<input type=\"text\" name=\"{$class}_rows\" value=\"". hsc ($rows). "\"> rows</span>\n".
            "    </p>\n";
    }
    
    
    function attachInputField(Form $form, $input_value = '', $primary_key = null, $field_params = array()) {
        $p = self::initInput($form);
        $params = [
            'id' => $form->getFieldId(),
            'name' => $this->getPostSafeName(),
        ];
        $maxlength = (int) $this->getMaxLength();
        if ($maxlength > 0) $params['maxlength'] = $maxlength;
        $textarea = HtmlDom::appendNewChild($p, 'textarea', $params);
        HtmlDom::appendNewText($textarea, $input_value);
    }
}
?>
