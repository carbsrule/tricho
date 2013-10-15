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
            echo "    theme_advanced_toolbar_location: 'top',\n";
            echo "    theme_advanced_toolbar_align: 'left',\n";
            echo "    plugins: '", (defined ('TINYMCE_PLUGINS')? TINYMCE_PLUGINS: ''), "',\n";
            echo "    content_css: '", ROOT_PATH_WEB, "tinymce/content.css',\n";
            echo "    spellchecker_languages: '+English=en',\n";
            
            // Work out which buttons to display: use the default set if none are explicitly specified
            $button_lines = $tinymce_field->getParam ('tinymce_buttons');
            if (!is_array ($button_lines) or count ($button_lines) == 0 or $button_lines[0] == '') {
                if (defined ('TINYMCE_DEFAULT_BUTTONS')) {
                    $button_lines = explode ('/', TINYMCE_DEFAULT_BUTTONS);
                } else {
                    $button_lines = array ();
                }
            }

            /* build the valid_elements string */
            $valid_elements = '';

            /* collect and translate allowed tags */
            $tags_allow = array ();

            if ($tinymce_field->getParam ('tinymce_tags_allow') == '') {
                $tag_attr_array = explode (',', HTML_TAGS_ALLOW);
            } else {
                $tag_attr_array = explode (',', $tinymce_field->getParam ('tinymce_tags_allow'));
            }

            foreach ($tag_attr_array as $t) {
                list ($tag, $attrs) = explode (':', $t, 2);
                $tags_allow[$tag] = explode (';', $attrs);
            }

            /* append allowed tags to valid_elements string */
            $i = count ($tags_allow);
            foreach ($tags_allow as $tag => $attrs) {
                $valid_elements .= $tag;
                if (count($attrs) > 0 and $attrs[0] != '') {
                    $valid_elements .= '['. implode ('|', $attrs). ']';
                }
                if (--$i > 0) {
                    $valid_elements .= ',';
                }
            }

            /* collect and translate tag replacements */
            if ($tinymce_field->getParam ('tinymce_tags_replace') == '') {
                $tag_map = explode (',', HTML_TAGS_REPLACE);
            } else {
                $tag_map = explode (',', $tinymce_field->getParam ('tinymce_tags_replace'));
            }

            $tags_replace = array ();
            if (!(count ($tag_map) == 1 and $tag_map[0] == '')) {
                foreach ($tag_map as $t) {
                    $synonyms = explode ('=', $t);
                    $tags_replace[] = implode ('/', $synonyms);
                }
            }

            /* append tag replacements to valid_elements string */
            if (count ($tags_replace) > 0) {
                if (!(count($tags_allow) == 1 and $tags_allow[0] == '')) {
                    $valid_elements .= ',';
                }
                $valid_elements .= implode (',', $tags_replace);
            }

            /* invalid elements are easy - no processing required */
            if ($tinymce_field->getParam ('tinymce_tags_deny') == '') {
                $invalid_elements = HTML_TAGS_DENY;
            } else {
                $invalid_elements = $tinymce_field->getParam ('tinymce_tags_deny');
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
                
                // check if there are any buttons that should be hidden for non-setup users
                // (those that should be hidden will start with #)
                $buttons = preg_split ('/,\s*/', $line);
                foreach ($buttons as &$button_val) {
                    if ($button_val[0] == '#') {
                        if (test_setup_login (false, SETUP_ACCESS_LIMITED)) {
                            $button_val = substr ($button_val, 1);
                        } else {
                            unset ($button_val);
                        }
                    }
                }
                $line = implode (',', $buttons);
                
                echo '    theme_advanced_buttons', $line_num++, ": '{$line}',\n";
            }
            
            echo "    theme: 'advanced'\n";

            if ($valid_elements != '') {
                echo "    valid_elements: '{$valid_elements}'\n";
            }
            if ($invalid_elements != '') {
                echo "    invalid_elements: '{$invalid_elements}'\n";
            }
            echo "});\n";
        }
    }
}

?>
