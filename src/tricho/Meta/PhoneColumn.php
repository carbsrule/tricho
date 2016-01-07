<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

use DOMDocument;
use DOMElement;
use DataValidationException;

/**
 * Meta-data for a column that stores a phone number
 */
class PhoneColumn extends InputColumn {
    /** Minimum length of a valid phone number */
    protected $min_len = 0;
    
    /** Whether to allow international numbers (i.e. with the + prefix) */
    protected $allow_intl = false;
    
    /** Whether to allow letters in the number, e.g. 1800-YOU-SQUEAL */
    protected $allow_letters = false;
    
    
    static function getAllowedSqlTypes() {
        return array('VARCHAR');
    }
    
    
    static function getDefaultSqlType() {
        return 'VARCHAR';
    }
    
    
    function toXMLNode(DOMDocument $doc) {
        $node = parent::toXMLNode($doc);
        $node->setAttribute('min_len', $this->min_len);
        $allow_intl = ($this->allow_intl? 'y': 'n');
        $node->setAttribute('allow_intl', $allow_intl);
        $allow_letters = ($this->allow_letters? 'y': 'n');
        $node->setAttribute('allow_letters', $allow_letters);
        return $node;
    }
    
    
    function applyXMLNode(DOMElement $node) {
        parent::applyXMLNode($node);
        $this->setMinLength($node->getAttribute('min_len'));
        $allow_intl = $node->getAttribute('allow_intl');
        if ($allow_intl == 'y') $this->setAllowIntl(true);
        $allow_letters = $node->getAttribute('allow_letters');
        if ($allow_letters == 'y') $this->setAllowLetters(true);
    }
    
    
    function getConfigArray() {
        $config = parent::getConfigArray();
        $config['min_len'] = $this->min_len;
        $config['allow_intl'] = $this->allow_intl;
        $config['allow_letters'] = $this->allow_letters;
        return $config;
    }
    
    
    static function getConfigFormFields(array $config, $class) {
        $fields = parent::getConfigFormFields($config, $class);
        $min_len = @$config['min_len'];
        if ($min_len == 0) $min_len = '';
        $fields .= "<p>Minimum length <input type=\"text\" " .
            "name=\"{$class}_min_len\" value=\"{$min_len}\"></p>\n";
        $id = "{$class}_allow_intl";
        $fields .= '<p>Allow international numbers ' .
            "<label for=\"{$id}_y\">" .
            "<input type=\"radio\" id=\"{$id}_y\" name=\"{$id}\"" .
            ' value="1"';
        if (@$config['allow_intl']) $fields .= ' checked="checked"';
        $fields .= ">Yes</label> <label for=\"{$id}_n\">" .
            "<input type=\"radio\" id=\"{$id}_n\" name=\"{$id}\"" .
            ' value="0"';
        if (!@$config['allow_intl']) $fields .= ' checked="checked"';
        $fields .= ">No</label></p>\n";
        
        $id = "{$class}_allow_letters";
        $fields .= '<p>Allow letters ' .
            "<label for=\"{$id}_y\">" .
            "<input type=\"radio\" id=\"{$id}_y\" name=\"{$id}\"" .
            ' value="1"';
        if (@$config['allow_letters']) $fields .= ' checked="checked"';
        $fields .= ">Yes</label> <label for=\"{$id}_n\">" .
            "<input type=\"radio\" id=\"{$id}_n\" name=\"{$id}\"" .
            ' value="0"';
        if (!@$config['allow_letters']) $fields .= ' checked="checked"';
        $fields .= ">No</label></p>\n";
        return $fields;
    }
    
    function applyConfig(array $config, array &$errors) {
        $this->setMinLength(@$config['min_len']);
        $this->setAllowIntl(@$config['allow_intl']);
        $this->setAllowLetters(@$config['allow_letters']);
    }
    
    
    function collateInput($input, &$original_value) {
        $value = str_replace('-', ' ', $input);
        $value = trim($value);
        $value = preg_replace('/  +/', ' ', $value);
        $original_value = $value;
        if ($value == '') return array($this->name => '');
        
        $intl = false;
        if ($this->allow_intl and $value[0] == '+') {
            $intl = true;
            $value = ltrim(substr($value, 1));
        }
        
        $allowed = '0-9 ';
        if ($this->allow_letters) $allowed .= 'a-zA-Z';
        
        if (!preg_match("/^[{$allowed}]+\$/", $value)) {
            throw new DataValidationException('Invalid character(s) entered');
        }
        
        $length_check = str_replace(' ', '', $value);
        if (strlen($length_check) < $this->min_len) {
            throw new DataValidationException('Too few digits entered');
        }
        
        if ($intl) $value = '+' . $value;
        
        return array($this->name => $value);
    }
    
    
    function setMinLength($len) {
        $len = (int) $len;
        if ($len < 0) $len = 0;
        $this->min_len = $len;
    }
    
    
    function setAllowIntl($allow) {
        if ($allow) {
            $this->allow_intl = true;
        } else {
            $this->allow_intl = false;
        }
    }
    
    
    function setAllowLetters($allow) {
        if ($allow) {
            $this->allow_letters = true;
        } else {
            $this->allow_letters = false;
        }
    }
}
