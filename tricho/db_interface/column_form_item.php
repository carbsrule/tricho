<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Meta\Column;

/**
 * A field on a form that refers to a database column
 */
class ColumnFormItem extends FormItem {
    protected $column;
    protected $label;
    protected $value;
    protected $apply;
    
    function __construct(Column $col) {
        $this->column = $col;
    }
    
    
    /**
     * @return Column
     */
    function getColumn() {
        return $this->column;
    }
    
    
    function getLabel() {
        return $this->label;
    }
    function setLabel($label) {
        $this->label = $label;
    }
    
    
    function getValue() {
        return $this->value;
    }
    function setValue($value) {
        $this->value = $value;
    }
    
    
    function getApply() {
        return $this->apply;
    }
    function setApply($apply) {
        $this->apply = $apply;
    }
    
    
    /**
     * Gets the properties as an array
     * @return array [$column, $label, $value, $apply]
     */
    function toArray() {
        return [$this->column, $this->label, $this->value, $this->apply];
    }
}
    
