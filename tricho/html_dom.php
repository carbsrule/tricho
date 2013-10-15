<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * Provides static methods for working on DOM trees.
 */
class HtmlDom {
    
    static function getNodeText (DOMNode $node) {
        $doc = new DOMDocument ('1.0', 'utf-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->appendChild ($doc->importNode ($node, true));
        return trim (preg_replace ('/<\?xml[^>]*>/', '', $doc->saveXML ()), "\t\n\r\0\x0B");
    }
    
    /**
     * Remove a node from a DOM tree without removing its children, i.e. the
     * given node's children become children of its parent.
     * @param DOMNode $node DOM node to remove
     */
    static function removeWrapper (DOMNode $node) {
        
        $parent = $node->parentNode;
        
        $children = $node->childNodes;
        while ($children->length > 0) {
            $child = $children->item (0);
            $parent->insertBefore ($child, $node);
        }
        $parent->removeChild ($node);
    }
    
    /**
     * Remove a node (and all its children) from a DOM tree.
     * @param DOMNode $node
     */
    static function removeNode (DOMNode $node) {
        $node->parentNode->removeChild ($node);
        $node = null;
    }
    
    static function switchTag (DOMElement $node, $tag_name) {
        $parent = $node->parentNode;
        $replacement_node = $node->ownerDocument->createElement ($tag_name);
        
        // copy attributes from existing node to replacement
        for ($i = 0; $i < $node->attributes->length; $i++) {
            $attr = $node->attributes->item ($i);
            $replacement_node->setAttribute ($attr->name, $attr->value);
        }
        
        // copy children from existing node to replacement
        while ($node->childNodes->length > 0) {
            $child = $node->firstChild;
            $replacement_node->appendChild ($child);
        }
        
        $parent->replaceChild ($replacement_node, $node);
        
        return $replacement_node;
    }
    
    // See if there's a p, table, div, or ul node above this node.
    // If there's a div, force a p inside. If no appropriate ancestor was found,
    // force a p at highest level.
    // TODO: check standards to see if a DIV is allowed inside P - I don't think it makes sense
    static function forceParagraph (DOMNode $node) {
        
        $tag_names = array ('p', 'div', 'ul', 'table', 'body');
        
        $parent = $node->parentNode;
        
        $found_exit = false;
        while ($parent instanceof DOMElement) {
            
            if (in_array ($parent->tagName, $tag_names)) {
                if ($parent->tagName != 'body') $found_exit = true;
                break;
            }
            
            $node = $parent;
            $parent = $parent->parentNode;
        }
        
        // If no TABLE, P, or UL was found, encase the sub-tree in a P,
        // and hope that there aren't any existing Ps in the sub-tree
        // (if there are, then the DOM structure didn't make sense anyway)
        if (!$found_exit or $parent->tagName == 'div') {
            if ($parent instanceof DOMDocument) {
                $p_wrapper = $parent->createElement ('p');
            } else {
                $p_wrapper = $parent->ownerDocument->createElement ('p');
            }
            $parent->replaceChild ($p_wrapper, $node);
            $p_wrapper->appendChild ($node);
            return $p_wrapper;
        } else {
            return $node;
        }
        
    }

    static function getLeafNodes (DOMNode $node, $leaf_nodes = array ()) {
        
        $children = $node->childNodes;
        if ($children->length == 0) {
            $leaf_nodes[] = $node;
        } else {
            for ($i = 0; $i < $children->length; $i++) {
                $leaf_nodes = self::getLeafNodes ($children->item ($i), $leaf_nodes);
            }
        }
        return $leaf_nodes;
    }

    /**
     * Remove unwanted nodes (and attributes) from a DOM tree rooted at a given
     * node.
     *
     * @param DOMNode $node Root node of DOM subtree.
     * @param boolean $ignore_self Whether to check the given node for removal.
     * @param string $tags_allow Comma separated list of tag;{attr} components.
     *        For example,
     *        'a;href;type,blockquote' will allow 'a' and 'blockquote' nodes to
     *        remain in the tree.
     *        However, 'a' nodes can only have the attributes 'href' and
     *        'type'; all others are removed.
     * @param string $tags_replace Comma separated list of tag replacements.
     *        For example,
     *        'b=strong,i=em' will replace 'b' and 'i' nodes with 'strong' and
     *        'em' nodes, respectively.
     * @param string $tags_deny Comma separated list of tags to remove.
     */
    static function removeUnwantedNodes (
        DOMNode $node,
        $ignore_self = false,
        $tags_allow = '',
        $tags_replace = '',
        $tags_deny = ''
    ) {
        
        /* this is just to help out calling code */
        if (trim ($tags_allow) == '')     $tags_allow     = HTML_TAGS_ALLOW;
        if (trim ($tags_replace) == '') $tags_replace = HTML_TAGS_REPLACE;
        if (trim ($tags_deny) == '')        $tags_deny        = HTML_TAGS_DENY;

        $tags_allowed         = array ();
        $tag_replacements = array ();
        $tags_denied            = array ();

        $tag_attr_pairs = preg_split ('/,\s*/', $tags_allow);
        foreach ($tag_attr_pairs as $pair) {
            
            list ($tag, $attribs_str) = preg_split ('/:\s*/', $pair, 2);
            
            if (preg_match ('/^[a-zA-Z]+$/', $tag)) {
                $tags_allowed[$tag] = array ();
                $attribs_allowed = preg_split ('/;\s*/', $attribs_str);
                foreach ($attribs_allowed as $attrib) {
                    if (preg_match ('/^[a-zA-Z]+$/', $attrib)) {
                        $tags_allowed[$tag][] = $attrib;
                    }
                }
            }
        }
        
        $replacements = preg_split ('/,\s*/', $tags_replace);
        foreach ($replacements as $replace_str) {
            list ($tag, $replacement_tag) = preg_split ('/=\s*/', $replace_str, 2);
            if (preg_match ('/^[a-zA-Z]+$/', $tag)
                        and preg_match ('/^[a-zA-Z]+$/', $replacement_tag)) {
                $tag_replacements[$tag] = $replacement_tag;
            }
        }
        
        $denied = preg_split ('/,\s*/', $tags_deny);
        foreach ($denied as $tag) {
            if (preg_match ('/^[a-zA-Z]+$/', $tag)) {
                $tags_denied[$tag] = $tag;
            }
        }
    
        if (!$ignore_self) {
            if ($node->nodeType == XML_ELEMENT_NODE) {
                
                // See if node is disabled, if so, remove it
                if (in_array ($node->tagName, $tags_denied)) {
                    self::removeNode ($node);
                } else {
                    // Perform replacement if necessary
                    if (isset ($tag_replacements[$node->tagName])) {
                        $node = self::switchTag ($node, $tag_replacements[$node->tagName]);
                    }
                    
                    if (isset ($tags_allowed[$node->tagName])) {
                        self::removeUnwantedAttrs ($node, $tags_allowed[$node->tagName]);
                    } else {
                        
                        // Fix the children of the wrapper so that they also validate
                        $children_to_fix = array ();
                        $children = $node->childNodes;
                        for ($i = 0; $i < $children->length; $i++) {
                            $children_to_fix[] = $children->item ($i);
                        }
                        
                        foreach ($children_to_fix as $child) {
                            self::removeUnwantedNodes ($child, false, $tags_allow, $tags_replace, $tags_deny);
                        }
                        self::removeWrapper ($node);
                    }
                }
                
            } else if ($node->nodeType != XML_TEXT_NODE) {
                self::removeNode ($node);
            }
        }
        
        if ($node != null) {
            
            $children_to_fix = array ();
            $children = $node->childNodes;
            for ($i = 0; $i < $children->length; $i++) {
                $children_to_fix[] = $children->item ($i);
            }
            
            foreach ($children_to_fix as $child) {
                self::removeUnwantedNodes ($child, false, $tags_allow, $tags_replace, $tags_deny);
            }
        }
    }
    
    /**
     * Removed unwanted attributes from a DOMElement.
     *
     * @param DOMElement $node DOM element to work on
     * @param array $allowed_attrs An array of attributes that are allowed;
     *        all others are removed.
     */
    static function removeUnwantedAttrs (DOMElement $node, $allowed_attrs = '') {
        if (!is_array ($allowed_attrs)) {
            if ($allowed_attrs != '') {
                $allowed_attrs = array ($allowed_attrs);
            } else {
                $allowed_attrs = array ();
            }
        }
        
        $attrs = $node->attributes;
        
        for ($i = $attrs->length - 1; $i >= 0; $i--) {
            $attr = $attrs->item ($i);
            if (!in_array ($attr->name, $allowed_attrs)) {
                $node->removeAttribute ($attr->name);
            }
        }
    }
    
    
    /**
     * Creates a DOM element with attributes
     * @param DOMDocument $doc The document to which the new element will belong
     * @param string $element_name The name of the new element, e.g. html
     * @param array $attribs The attributes for the new element
     * @return DOMElement
     * @author benno 2011-08-15
     */
    static function createElement (DOMDocument $doc, $element_name, array $attribs = array ()) {
        $node = $doc->createElement ((string) $element_name);
        foreach ($attribs as $name => $value) {
            $node->setAttribute ($name, $value);
        }
        return $node;
    }
    
    
    /**
     * Creates a DOM element with attributes and attaches it as the last child
     * of a parent element.
     * @param DOMElement $parent The parent element of the new element,
     *        e.g. an HTML node
     * @param string $element_name The name of the new element, e.g. body
     * @param array $attribs The attributes for the new element
     * @return DOMElement
     * @author benno 2011-08-15
     */
    static function appendNewChild (DOMElement $parent, $element_name, array $attribs = array ()) {
        $doc = $parent->ownerDocument;
        $node = self::createElement ($doc, $element_name, $attribs);
        $parent->appendChild ($node);
        return $node;
    }
    
    
    /**
     * Gets an element's attributes as an array, for easy access
     * @param DOMElement $element
     * @return array
     * @author benno 2011-08-15
     */
    static function getAttribArray (DOMElement $element) {
        $arr = array ();
        foreach ($element->attributes as $name => $attr) {
            $arr[$name] = $attr->value;
        }
        return $arr;
    }
    
    
    /**
     * Gets child elements which match a specified node name
     * @param DOMElement $element The element whose children should be searched
     * @param string $tag_name The name of the desired tags
     * @return array
     * @author benno 2011-08-16
     */
    static function getChildrenByTagName (DOMElement $element, $tag_name) {
        $matching_nodes = array ();
        foreach ($element->childNodes as $child) {
            if ($child->nodeType == XML_ELEMENT_NODE and $child->nodeName == $tag_name) {
                $matching_nodes[] = $child;
            }
        }
        return $matching_nodes;
    }
    
    
    /**
     * Gets a child element which matches a specified node name
     * @param DOMElement $element The element whose children should be searched
     * @param string $tag_name The name of the desired tag
     * @return mixed a matching DOMElement, or null
     * @author benno 2011-08-16
     */
    static function getChildByTagName (DOMElement $element, $tag_name) {
        foreach ($element->childNodes as $child) {
            if ($child->nodeType == XML_ELEMENT_NODE and $child->nodeName == $tag_name) {
                return $child;
            }
        }
        return null;
    }
}

?>
