<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * Initialises TinyMCE fields - to be used on main_add and main_edit.
 * Note that this function will echo javascript
 * 
 * @param array $tinymce_fields the {@link Column}s which are TinyMCE fields to be initialised
 */
function init_tinymce ($tinymce_fields) {
    if (count ($tinymce_fields) > 0) {
        $tinymce_field_num = 0;
        foreach ($tinymce_fields as $tinymce_field) {
            echo "tinyMCE.init({\n";
            echo "    mode: 'exact',\n";
            echo "    elements: '{$tinymce_field->getName ()}',\n";
            echo "    forced_root_block: 'p',\n";
            echo "    document_base_url: 'http://", $_SERVER['SERVER_NAME']. ROOT_PATH_WEB, "',\n";
            echo "    plugins: '", (defined ('TINYMCE_PLUGINS')? TINYMCE_PLUGINS: ''), "',\n";
            echo "    content_css: '", ROOT_PATH_WEB, "tinymce/content.css',\n";
            echo "    spellchecker_languages: '+English=en',\n";
            
            // Work out which buttons to display: use the default set if none are explicitly specified
            $button_lines = $tinymce_field->getButtons();
            if (count($button_lines) == 0 or $button_lines[0] == '') {
                if (defined('TINYMCE_DEFAULT_BUTTONS')) {
                    $button_lines = explode('/', TINYMCE_DEFAULT_BUTTONS);
                } else {
                    $button_lines = array();
                }
            }

            /* build the valid_elements string */
            $valid_elements = '';

            /* collect and translate allowed tags */
            $tags_allow = array ();
            
            $data = $tinymce_field->getAllowedTags();
            if ($data == '') $data = HTML_TAGS_ALLOW;
            $tag_defns = preg_split('/, */', $data);
            foreach ($tag_defns as $defn) {
                @list($tag, $attrs) = explode(':', $defn, 2);
                $tags_allow[$tag]['attrs'] = explode(';', $attrs);
            }
            
            /* collect and translate tag replacements */
            $tags_replace = array();
            $data = $tinymce_field->getReplaceTags();
            if ($data == '') $data = HTML_TAGS_REPLACE;
            $tag_defns = preg_split('/, */', $data);
            if (!(count($tag_defns) == 1 and $tag_defns[0] == '')) {
                foreach ($tag_defns as $tag_defn) {
                    list($source, $dest) = explode('=', $tag_defn, 2);
                    $tags_allow[$dest]['alt'] = $source;
                }
            }

            /* append allowed tags to valid_elements string */
            $i = count ($tags_allow);
            foreach ($tags_allow as $tag => $data) {
                $valid_elements .= $tag;
                $attrs = @$data['attrs'];
                if (@count($attrs) > 0 and $attrs[0] != '') {
                    $valid_elements .= '['. implode ('|', $attrs). ']';
                }
                if (@$data['alt']) {
                    $valid_elements .= '/'. $data['alt'];
                }
                if (--$i > 0) {
                    $valid_elements .= ',';
                }
            }

            /* invalid elements are easy - no processing required */
            if ($tinymce_field->getRemoveTags() == '') {
                $invalid_elements = HTML_TAGS_DENY;
            } else {
                $invalid_elements = $tinymce_field->getRemoveTags();
            }
            
            // If some buttons have been specified, make sure that the TinyMCE defaults
            // for lines 2 and 3 aren't used
            if (count ($button_lines) > 0) {
                while (count ($button_lines) < 3) {
                    $button_lines[] = '';
                }
            }
            
            $line_num = 1;
            foreach ($button_lines as $line) {
                if ($line == '') break;
                
                // check if there are any buttons that should be hidden for non-setup users
                // (those that should be hidden will start with #)
                $buttons = preg_split ('/,\s*/', $line);
                foreach ($buttons as &$button_val) {
                    if (@$button_val[0] == '#') {
                        if (test_setup_login (false, SETUP_ACCESS_LIMITED)) {
                            $button_val = substr ($button_val, 1);
                        } else {
                            unset ($button_val);
                        }
                    }
                }
                $line = implode (',', $buttons);
                
                echo '    toolbar', $line_num++, ": '{$line}',\n";
            }
            
            echo "    theme: 'modern',\n";
            echo "    menubar: false,\n";
            if (test_admin_login(false)) {
                echo "    statusbar: true";
            } else {
                echo "    statusbar: false";
            }

            if ($valid_elements != '') {
                echo ",\n    valid_elements: '{$valid_elements}'";
            }
            if ($invalid_elements != '') {
                echo ",\n    invalid_elements: '{$invalid_elements}'";
            }
            echo "\n});\n";
        }
    }
}

?>
