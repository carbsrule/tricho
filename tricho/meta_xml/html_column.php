<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

class HtmlColumn extends InputColumn {
    protected $allowed_tags = '';
    protected $replace_tags = '';
    protected $remove_tags = '';
    
    
    static function getAllowedSqlTypes() {
        return array ('TEXT', 'TINYTEXT', 'MEDIUMTEXT', 'LONGTEXT');
    }
    
    
    static function getDefaultSqlType() {
        return 'TEXT';
    }
    
    function toXMLNode(DOMDocument $doc) {
        $node = parent::toXMLNode($doc);
        $allow = $doc->createElement('allow');
        $allow->setAttribute('value', $this->allowed_tags);
        $node->appendChild($allow);
        
        $replace = $doc->createElement('replace');
        $replace->setAttribute('value', $this->replace_tags);
        $node->appendChild($replace);
        
        $remove = $doc->createElement('remove');
        $remove->setAttribute('value', $this->remove_tags);
        $node->appendChild($remove);
        
        return $node;
    }
    
    
    function applyXMLNode(DOMElement $node) {
        parent::applyXMLNode($node);
        $allow = $node->getElementsByTagName('allow');
        if ($allow->length > 0) {
            $allow = $allow->item(0);
            $this->allowed_tags = $allow->getAttribute('value');
        }
        
        $replace = $node->getElementsByTagName('replace');
        if ($replace->length > 0) {
            $replace = $replace->item(0);
            $this->replace_tags = $replace->getAttribute('value');
        }
        
        $remove = $node->getElementsByTagName('remove');
        if ($remove->length > 0) {
            $remove = $remove->item(0);
            $this->remove_tags = $remove->getAttribute('value');
        }
    }
    
    
    function getConfigArray() {
        $config = parent::getConfigArray();
        $config['allow'] = $this->allowed_tags;
        $config['replace'] = $this->replace_tags;
        $config['remove'] = $this->remove_tags;
        return $config;
    }
    
    
    static function getConfigFormFields(array $config, $class) {
        if (!isset($config['allow'])) $config['allow'] = HTML_TAGS_ALLOW;
        if (!isset($config['replace'])) $config['replace'] = HTML_TAGS_REPLACE;
        if (!isset($config['remove'])) $config['remove'] = HTML_TAGS_DENY;
        
        $fields = '<p>Allow tags <input type="text" name="' . $class .
            '_allow" value="' . hsc($config['allow']) . '"></p>';
        
        $fields .= '<p>Replace tags <input type="text" name="' . $class .
            '_replace" value="' . hsc($config['replace']) . '"></p>';
        
        $fields .= '<p>Remove tags <input type="text" name="' . $class .
            '_remove" value="' . hsc($config['remove']) . '"></p>';
        return $fields;
    }
    
    function applyConfig(array $config, array &$errors) {
        $this->setAllowedTags(@$config['allow']);
        $this->setReplaceTags(@$config['replace']);
        $this->setRemoveTags(@$config['remove']);
    }
    
    
    function getInputField(Form $form, $input_value = '', $primary_key = null, $field_params = array()) {
        $field = '<textarea name="' . $this->name . '"';
        $maxlength = (int) $this->getMaxLength();
        if ($maxlength > 0) $field .= " maxlength=\"{$maxlength}\"";
        $field .= '>' . hsc($input_value) . "</textarea>";
        return $field;
    }
    
    
    function collateInput($input, &$original_value) {
        $html = '<html><head><meta http-equiv="Content-Type" ' .
            'content="text/html; charset=UTF-8"></head><body>' . $input .
            '</body></html>';
        $doc = new DOMDocument();
        @$doc->loadHTML($html);
        $body = $doc->getElementsByTagName('body')->item(0);
        HtmlDom::removeUnwantedNodes(
            $body,
            true,
            $this->allowed_tags,
            $this->replace_tags,
            $this->remove_tags
        );
        $body = $doc->saveHTML($body);
        
        // strip <body> and </body>
        $body = substr($body, strpos($body, '>') + 1, -7);
        
        $original_value = $body;
        return array($this->name => $body);
    }
    
    
    function setAllowedTags($tags) {
        $this->allowed_tags = (string) $tags;
    }
    
    
    function setReplaceTags($tags) {
        $this->replace_tags = (string) $tags;
    }
    
    
    function setRemoveTags($tags) {
        $this->remove_tags = (string) $tags;
    }
}
