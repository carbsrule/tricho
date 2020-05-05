<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

use DataValidationException;

use Tricho\DataUi\Form;
use Tricho\Util\HtmlDom;


class SetColumn extends EnumColumn {
    static function getAllowedSqlTypes () {
        return array('SET');
    }

    static function getDefaultSqlType () {
        return 'SET';
    }

    function setMandatory($bool) {
        Column::setMandatory($bool);
    }


    function attachValue(Form $form, $value, array $pk)
    {
        $doc = $form->getDoc();
        $form_el = $doc->getElementsByTagName('form')->item(0);
        $p = $doc->createElement('p');
        $form_el->appendChild($p);

        if (!is_array($value)) {
            $value = array_filter(explode(',', $value));
        }
        $display = [];
        foreach ($value as $component) {
            if (!empty($this->choices[$component])) {
                $display[] = $this->choices[$component];
            } else {
                $display[] = $component;
            }
        }

        HtmlDom::appendNewText($p, implode(', ', $display));
    }


    function attachSelect($p, $form, $input_value, $primary_key)
    {
        if (is_string($input_value)) {
            $input_value = array_filter(explode(',', $input_value));
        }
        $id = $form->getFieldId();
        $params = [
            'name' => $this->getPostSafeName() . '[]',
            'id' => $id,
            'multiple' => 'multiple',
        ];
        $select = HtmlDom::appendNewChild($p, 'select', $params);

        foreach ($this->choices as $choice => $choice_label) {
            $params = ['value' => $choice];
            if ($choice_label == '') {
                $choice_label = $choice;
            }
            if (in_array($choice, $input_value)) {
                $params['selected'] = 'selected';
            }
            $option = HtmlDom::appendNewChild($select, 'option', $params);
            HtmlDom::appendNewText($option, $choice_label);
        }
    }


    function collateInput($input, &$original_value) {
        if (empty($input) || !is_array($input)) {
            $original_value = [];
            return [$this->name => ''];
        }
        $values = [];
        foreach ($input as $value) {
            if (isset($this->choices[$value])) {
                $values[] = $value;
            } else {
                throw new DataValidationException('Nonexistent value');
            }
        }
        $original_value = $values;
        return [$this->name => implode(',', $values)];
    }
}
