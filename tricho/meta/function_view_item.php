<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * @package meta_xml
 * @subpackage view
 */

/**
 * This is the view item for a function.
 * If this item is encounted on a view list, a MySQL function should be rendered
 * @package meta_xml
 * @subpackage view
 * @author Josh 2007-08-24
 */
class FunctionViewItem extends ViewItem {
    
    private $name;
    private $code;
    
    /**
     * Used by {@link print_human} to create a human-readable string that
     * expresses this object's properties.
     * 
     * @param int $indent_tab The indent tab to start on
     * @param bool $indent_self If true, the output of this item will be
     *        indented. If not, only its children will be indented.
     */
    function __printHuman ($indent_tab = 0, $indent_self = true) {
        
        if (defined ('PRINT_HUMAN_INDENT_WIDTH')) {
            $indent_width = PRINT_HUMAN_INDENT_WIDTH;
        } else {
            $indent_width = 2;
        }
        
        $indent = str_repeat (' ', $indent_width * $indent_tab);
        
        if ($indent_self) {
            echo $indent;
        }
        
        echo "&lt;{$this->name}&gt; {$this->code}\n";
    }
    
    /**
     * Loader of details from the xml
     * See ViewItem::loadFromXML for full specs
     */
    public function loadFromXML ($xml_params, $view) {
        $this->name = $xml_params['NAME'];
        $this->code = $xml_params['CODE'];
    }
    
    
    function toXMLNode(DOMDocument $doc, $add_edit = array()) {
        $node = $doc->createElement('item');
        $node->setAttribute('type', 'func');
        $node->setAttribute('name', $this->name);
        $node->setAttribute('code', $this->code);
        return $node;
    }
    
    /**
     * Set the details for this FunctionViewItem. Should only be used in setup
     *
     * @param string $name The name to use for this FunctionViewItem.
     * @param string $code The MySQL code to use for this FunctionViewItem.
     */
    public function setDetails ($name, $code) {
        $this->name = $name;
        $this->code = $code;
    }
    
    /**
     * Get the XML attributes
     */
    public function getXMLAttribs () {
        return array ('type' => 'func', 'name' => $this->name, 'code' => $this->code);
    }
    
    
    /**
     * Gets the function name
     * @return string The function name
     */
    public function getName () {
        return $this->name;
    }
    
    /**
     * Gets the MySQL code for this function
     * @return string The code of this function
     */
    public function getCode () {
        return $this->code;
    }
    
    
    /**
     * Sets the name for this function
     * @author Josh 2008-03-06
     * @param string $name The new name of the function
     */
    public function setName ($name) {
        $this->name = $name;
    }
    
    /**
     * Sets the MySQL code for this function
     * @author Josh 2008-03-06
     * @param string $code The new MySQL code for this function
     */
    public function setCode ($code) {
        $this->code = $code;
    }
    
    public function __toString () {
        return __CLASS__. " { name: {$this->name}; }";
    }
}
?>
