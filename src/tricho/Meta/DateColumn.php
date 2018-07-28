<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

use DOMDocument;
use DOMElement;

use Tricho\Meta;
use Tricho\Util\HtmlDom;

class DateColumn extends TemporalColumn {
    protected $has_date = true;
    
    
    static function getAllowedSqlTypes() {
        return array('DATE');
    }
    
    
    static function getDefaultSqlType() {
        return 'DATE';
    }
    
    
    function toXMLNode(DOMDocument $doc) {
        $node = parent::toXMLNode($doc);
        
        $params = ['name' => 'required'];
        $reqd = [];
        foreach (['year', 'month', 'day'] as $type) {
            $field = "{$type}_required";
            if ($this->$field) $reqd[] = $type;
        }
        $params['value'] = implode(',', $reqd);
        HtmlDom::appendNewChild($node, 'param', $params);
        return $node;
    }
    
    function applyXMLNode(DOMElement $node) {
        parent::applyXMLNode($node);
        $params = $node->getElementsByTagName('param');
        foreach ($params as $param) {
            if ($param->getAttribute('name') != 'required') continue;
            $reqd = $param->getAttribute('value');
            $reqd = preg_split('/,\s*/', $reqd);
            foreach (['year', 'month', 'day'] as $type) {
                $field = "{$type}_required";
                if (in_array($type, $reqd)) {
                    $this->$field = true;
                } else {
                    $this->$field = false;
                }
            }
        }
    }
    
    
    function getConfigArray() {
        $config = parent::getConfigArray();
        $config['year_required'] = $this->year_required;
        $config['month_required'] = $this->month_required;
        $config['day_required'] = $this->day_required;
        return $config;
    }
    
    static function getConfigFormFields(array $config, $class) {
        $fields = parent::getConfigFormFields($config, $class);
        
        $fields .= '<p>Year range: ';
        $fields .= "<input type=\"text\" name=\"{$class}_min_year\" ";
        $fields .= 'style="width:2.5em;" value="' . hsc(@$config['min_year']) .
            '"> to ';
        $fields .= "<input type=\"text\" name=\"{$class}_max_year\" ";
        $fields .= 'style="width:2.5em;" value="' . hsc(@$config['max_year']) .
            "\"><br>\n";
        
        if ($class == 'DateColumn') {
            foreach (['year', 'month', 'day'] as $type) {
                $id = $type . '_required';
                $fields .= '<label for="' . $id . '">';
                $fields .= '<input id="' . $id . '" type="checkbox"';
                $fields .= " name=\"{$class}_{$type}_required\" value=\"1\"";
                if (!isset($config["{$type}_required"])) {
                    $fields .= ' checked="checked"';
                } else if ($config["{$type}_required"]) {
                    $fields .= ' checked="checked"';
                }
                $fields .= ">Require {$type}</label><br>\n";
            }
        }
        
        return $fields;
    }
    
    
    function applyConfig(array $config, array &$errors) {
        $this->year_required = (bool) @$config['year_required'];
        $this->month_required = (bool) @$config['month_required'];
        $this->day_required = (bool) @$config['day_required'];
        
        parent::applyConfig($config, $errors);
    }
}
