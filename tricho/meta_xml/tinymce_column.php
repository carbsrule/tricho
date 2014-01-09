<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * Meta-data for a column that stores HTML and uses TinyMCE for user input
 */
class TinymceColumn extends HtmlColumn {
    protected $buttons = array();
    
    
    function toXMLNode(DOMDocument $doc) {
        $node = parent::toXMLNode($doc);
        $buttons = $doc->createElement('buttons');
        $node->appendChild($buttons);
        foreach ($this->buttons as $line) {
            $line_node = $doc->createElement('line');
            $buttons->appendChild($line_node);
            $line_node->setAttribute('contents', $line);
        }
        return $node;
    }
    
    
    function applyXMLNode(DOMElement $node) {
        parent::applyXMLNode($node);
        $buttons = $node->getElementsByTagName('buttons')->item(0);
        $lines = $buttons->getElementsByTagName('line');
        $this->buttons = array();
        foreach ($lines as $line) {
            $this->buttons[] = $line->getAttribute('contents');
        }
    }
    
    
    function getConfigArray() {
        $config = parent::getConfigArray();
        $config['buttons'] = implode("\n", $this->buttons);
        return $config;
    }
    
    
    static function getConfigFormFields(array $config, $class) {
        $fields = parent::getConfigFormFields($config, $class);
        $fields .= "Buttons <textarea name=\"{$class}_buttons\" cols=\"40\" " .
            "rows=\"3\">" . hsc(@$config['buttons']) . "</textarea>";
        return $fields;
    }
    
    
    function applyConfig(array $config, array &$errors) {
        parent::applyConfig($config, $errors);
        $buttons = trim($config['buttons']);
        $this->buttons = array();
        if ($buttons != '') {
            $buttons = str_replace("\r\n", "\n", $buttons);
            $buttons = str_replace("\r", "\n", $buttons);
            $this->buttons = explode("\n", $buttons);
        }
    }
    
    
    function getButtons() {
        return $this->buttons;
    }
}
