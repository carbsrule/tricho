<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

use DataValidationException;
use DOMDocument;
use DOMElement;

use Tricho\Util\HtmlDom;


/**
 * Contains metadata about a column which is used to store part of a person's
 * name
 */
class PersonNameColumn extends CharColumn {
    protected $text_filters = ['trim', 'multispace', 'tags'];
    protected $is_last_name = false;
    protected $allow_initial = false;
    
    static function getAllowedSqlTypes() {
        return ['VARCHAR'];
    }
    
    
    function toXMLNode(DOMDocument $doc) {
        $node = parent::toXMLNode($doc);
        if ($this->is_last_name) {
            $attrs = ['name' => 'is_last', 'value' => 1];
            $param = HtmlDom::appendNewChild($node, 'param', $attrs);
        }
        if ($this->allow_initial) {
            $attrs = ['name' => 'allow_initial', 'value' => 1];
            $param = HtmlDom::appendNewChild($node, 'param', $attrs);
        }
        return $node;
    }
    
    
    function applyXMLNode(DOMElement $node) {
        parent::applyXMLNode($node);
        $params = $node->getElementsByTagName('param');
        foreach ($params as $param) {
            if ($param->getAttribute('name') == 'is_last') {
                $is_last = (bool) (int) $param->getAttribute('value');
                $this->is_last_name = $is_last;
            }
            if ($param->getAttribute('name') == 'allow_initial') {
                $allow = (bool) (int) $param->getAttribute('value');
                $this->allow_initial = $allow;
            }
        }
    }
    
    
    /**
     * Validates part of a name, where a part is any block of characters
     * without a space
     * @return bool False if the part doesn't validate
     */
    function validatePart($part) {
        if ($this->allow_initial and preg_match('/^[a-z]$/i', $part)) {
            return true;
        }
        
        $pattern = '[a-z]{2,}';
        if ($this->is_last_name) $pattern = '(?:O\')?' . $pattern;
        $pattern .= '(?:\-' . $pattern . ')?';
        if (preg_match('/^' . $pattern . '$/i', $part)) return true;
        
        return false;
    }
    
    
    /**
     * Cleans the constituent parts of a name
     * @param array &$parts The name to clean
     * @return void
     */
    function cleanName(array &$parts) {
        $bad_caps = false;
        
        // Must have at least one first-letter capital
        $has_first_cap = false;
        foreach ($parts as $part) {
            if (strtoupper($part[0]) == $part[0]) {
                $has_first_cap = true;
                break;
            }
        }
        if (!$has_first_cap) $bad_caps = true;
        
        // Cannot have any caps after the first letter, unless proceeded by
        // punctuation (i.e. ' or -). Initials must be all uppercase
        if (!$bad_caps) {
            foreach ($parts as $part) {
                if (preg_match('/.+[^\'\-][A-Z]/', $part)) {
                    $bad_caps = true;
                    break;
                }
                if (preg_match('/^[a-z]$/', $part)) {
                    $bad_caps = true;
                    break;
                }
            }
        }
        
        if (!$bad_caps) return;
        
        foreach ($parts as &$part) {
            $part = ucfirst(strtolower($part));
        }
    }
    
    
    function collateInput($input, &$original_value) {
        $input = $this->applyTextFilters($input);
        
        $parts = array_filter(preg_split('/\s+/', $input));
        $parts = array_merge($parts);
        $original_value = $input;
        
        $errs = [];
        foreach ($parts as $part) {
            if (!$this->validatePart($part)) $errs[] = $part;
        }
        
        $this->cleanName($parts);
        
        $original_value = implode(' ', $parts);
        
        if ($errs) {
            $s = (count($errs) == 1)? '': 's';
            $err = "Invalid part{$s}: " . implode(', ', $errs);
            throw new DataValidationException($err);
        }
        
        if ($original_value != $input) {
            $err = 'Please verify changed capitalisation';
            throw new DataValidationException($err);
        }
        
        return [$this->name => $input];
    }
    
    
    function applyConfig(array $config, array &$errors) {
        $this->is_last_name = (bool) @$config['is_last_name'];
        $this->allow_initial = (bool) @$config['allow_initial'];
    }
    
    function getConfigArray() {
        $config = parent::getConfigArray();
        $config['is_last_name'] = $this->is_last_name;
        $config['allow_initial'] = $this->allow_initial;
        return $config;
    }
    
    static function getConfigFormFields(array $config, $class) {
        $fields = "<label for=\"is_last_name\"><input type=\"checkbox\" name=\"{$class}_is_last_name\" id=\"is_last_name\" value=\"1\"";
        if (@$config['is_last_name']) $fields .= ' checked="checked"';
        $fields .= ">Is a last name</label><br>\n";
        
        $fields .= "<label for=\"allow_initial\"><input type=\"checkbox\" name=\"{$class}_allow_initial\" id=\"allow_initial\" value=\"1\"";
        if (@$config['allow_initial']) $fields .= ' checked="checked"';
        $fields .= ">Allow initial</label><br>\n";
        
        return $fields;
    }
}
