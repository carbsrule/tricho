<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

use DOMDocument;
use DOMElement;

use Tricho\DataUi\Form;
use Tricho\Util\HtmlDom;

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
    
    
    function attachInputField(Form $form, $input_value = '', $primary_key = null, $field_params = array()) {
        parent::attachInputField($form, $input_value, $primary_key,
            $field_params);
        
        $form_el = $form->getDoc()->getElementsByTagName('form')->item(0);
        $params = array('type' => 'text/javascript');
        $script = HtmlDom::appendNewChild($form_el, 'script', $params);
        $init_js = $this->generateInitJs();
        
        $comment = $form->getDoc()->createComment("//\n{$init_js}\n//");
        $script->appendChild($comment);
    }
    
    
    function generateInitJs() {
        $js = "tinyMCE.init({\n";
        $js .= "    mode: 'exact',\n";
        $js .= "    elements: '{$this->getPostSafeName()}',\n";
        $js .= "    forced_root_block: 'p',\n";
        $js .= "    document_base_url: 'http://" . $_SERVER['SERVER_NAME'] .
            ROOT_PATH_WEB . "',\n";
        $js .= "    plugins: '" .
            (defined('TINYMCE_PLUGINS')? TINYMCE_PLUGINS: '') . "',\n";
        $js .= "    content_css: '" . ROOT_PATH_WEB . "tinymce/content.css',\n";
        $js .= "    spellchecker_languages: '+English=en',\n";
        
        // Work out which buttons to display: use the default set if none are
        // explicitly specified
        $button_lines = $this->buttons;
        if (count($button_lines) == 0 or $button_lines[0] == '') {
            if (defined('TINYMCE_DEFAULT_BUTTONS')) {
                $button_lines = explode('/', TINYMCE_DEFAULT_BUTTONS);
            } else {
                $button_lines = array();
            }
        }
        
        // Collect allowed tags
        $tags_allow = array();
        $data = $this->allowed_tags;
        if ($data == '') $data = HTML_TAGS_ALLOW;
        $tag_defns = preg_split('/, */', $data);
        foreach ($tag_defns as $defn) {
            @list($tag, $attrs) = explode(':', $defn, 2);
            $tags_allow[$tag]['attrs'] = explode(';', $attrs);
        }
        
        // Collect tag replacements
        $tags_replace = array();
        $data = $this->replace_tags;
        if ($data == '') $data = HTML_TAGS_REPLACE;
        $tag_defns = preg_split('/, */', $data);
        if (!(count($tag_defns) == 1 and $tag_defns[0] == '')) {
            foreach ($tag_defns as $tag_defn) {
                list($source, $dest) = explode('=', $tag_defn, 2);
                $tags_allow[$dest]['alt'] = $source;
            }
        }

        // Combine allowed and replacement tags into format required by TinyMCE
        $valid_elements = '';
        $i = count($tags_allow);
        foreach ($tags_allow as $tag => $data) {
            $valid_elements .= $tag;
            $attrs = @$data['attrs'];
            if (@count($attrs) > 0 and $attrs[0] != '') {
                $valid_elements .= '['. implode('|', $attrs). ']';
            }
            if (@$data['alt']) {
                $valid_elements .= '/'. $data['alt'];
            }
            if (--$i > 0) {
                $valid_elements .= ',';
            }
        }

        // Collect invalid elements
        if ($this->remove_tags == '') {
            $invalid_elements = HTML_TAGS_DENY;
        } else {
            $invalid_elements = $this->remove_tags;
        }
        
        // If some buttons have been specified, make sure that the TinyMCE
        // defaults for lines 2 and 3 aren't used
        if (count($button_lines) > 0) {
            while (count($button_lines) < 3) {
                $button_lines[] = '';
            }
        }
        
        $line_num = 1;
        foreach ($button_lines as $line) {
            if ($line == '') break;
            
            // Check if there are any buttons that should be hidden for
            // non-setup users. Those that should be hidden will start with #
            $buttons = preg_split('/,\s*/', $line);
            foreach ($buttons as &$button_val) {
                if (@$button_val[0] == '#') {
                    if (test_setup_login(false, SETUP_ACCESS_LIMITED)) {
                        $button_val = substr($button_val, 1);
                    } else {
                        unset($button_val);
                    }
                }
            }
            $line = implode(',', $buttons);
            
            $js .= '    toolbar' . $line_num++ . ": '{$line}',\n";
        }
        
        $js .= "    theme: 'modern',\n";
        $js .= "    menubar: false,\n";
        $js .= "    statusbar: false";

        if ($valid_elements != '') {
            $js .= ",\n    valid_elements: '{$valid_elements}'";
        }
        if ($invalid_elements != '') {
            $js .= ",\n    invalid_elements: '{$invalid_elements}'";
        }
        $js .= "\n});";
        return $js;
    }
    
    
    function getButtons() {
        return $this->buttons;
    }
}
