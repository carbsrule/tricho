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
 * Stores meta-data about a column that stores numeric data
 * @package meta_xml
 */
abstract class NumericColumn extends InputColumn {
    protected $signed = true;
    protected $min = null;
    protected $max = null;
    
    /**
     * @author benno, 2011-08-09
     */
    function toXMLNode (DOMDocument $doc) {
        $node = parent::toXMLNode ($doc);
        if ($this->min !== null) {
            $param = HtmlDom::appendNewChild ($node, 'param');
            $param->setAttribute ('name', 'min');
            $param->setAttribute ('value', $this->min);
        }
        if ($this->max !== null) {
            $param = HtmlDom::appendNewChild ($node, 'param');
            $param->setAttribute ('name', 'max');
            $param->setAttribute ('value', $this->max);
        }
        return $node;
    }
    
    
    /**
     * @author benno 2011-08-17
     */
    function applyXMLNode (DOMElement $node) {
        parent::applyXMLNode ($node);
        $params = $node->getElementsByTagName ('param');
        foreach ($params as $param) {
            $name = $param->getAttribute ('name');
            $value = $param->getAttribute ('value');
            if ($name == 'min' or $name == 'max') {
                $this->$name = $value;
            }
        }
    }
    
    
    /**
     * Sets whether or not this column has a signed value
     * @param bool $signed true for signed, false for unsigned
     * @todo remove? Or just move into Column
     */
    function setSigned ($signed) {
        $this->signed = (bool) $signed;
    }
    
    
    /**
     * Gets whether or not this column allows signed values
     * @return bool true unless the UNSIGNED sql attribute is set
     * @todo remove? Or just move into Column
     */
    function isSigned () {
        return $this->signed;
    }
    
    
    /**
     * @author benno 2011-08-17
     */
    function applyConfig (array $config) {
        $this->min = null;
        $this->max = null;
        if ($config['min'] != '') {
            if (strpos ($config['min'], '.') !== false) {
                settype ($config['min'], 'float');
            } else {
                settype ($config['min'], 'int');
            }
            $this->min = $config['min'];
        }
        if ($config['max'] != '') {
            if (strpos ($config['max'], '.') !== false) {
                settype ($config['max'], 'float');
            } else {
                settype ($config['max'], 'int');
            }
            $this->max = $config['max'];
        }
    }
    
    
    function getConfigArray () {
        $config = parent::getConfigArray ();
        if ($this->min !== null) $config['min'] = $this->min;
        if ($this->max !== null) $config['max'] = $this->max;
        return $config;
    }
    
    
    /**
     * @author benno 2011-08-17
     */
    static function getConfigFormFields(array $config, $class) {
        return parent::getConfigFormFields ($config, $class).
            "    <p class=\"fake-tr\">\n".
            "        <span class=\"fake-td left-col\">Range</span>\n".
            "        <span class=\"fake-td\"><input type=\"text\" name=\"{$class}_min\" value=\"".
            hsc(@$config['min']) . "\" size=\"4\"> to <input type=\"text\" name=\"{$class}_max\" value=\"" .
            hsc(@$config['max']) . "\" size=\"4\"></span></p>\n";
    }
    
    
    /**
     * gets the appropriate TD for use on a main list, perhaps with colspan
     * 
     * @param string $data the data for this cell
     * @param string $pk the primary key identifier for the row
     * @return string
     */
    function getTD($data, $pk) {
        return '<td align="right">' . hsc($data) . '</td>';
    }
}
?>
