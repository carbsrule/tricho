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
 * If this item is encounted on a view list, a function should be rendered
 * @package meta_xml
 * @subpackage view
 * @author Josh 2007-08-24
 */
class IncludeViewItem extends ViewItem {
    
    private $file;
    private $passthrough;
    private $name;
    
    
    /**
     * Creates a DOMElement that represents this item (for use in tables.xml)
     * @param DOMDocument $doc The document to which this node will belong
     * @param array $add_edit The add/edit info from the add/edit view to which
     *        this item belongs. i.e. has keys 'add', 'edit_view' and
     *        'edit_change'
     * @return DOMElement
     * @author benno, 2012-02-25
     */
    function toXMLNode (DOMDocument $doc, $add_edit = array()) {
        $node = $doc->createElement('item');
        $node->setAttribute('type', 'inc');
        $node->setAttribute('name', $this->name);
        $node->setAttribute('file', $this->file);
        if ($this->passthrough) {
            $node->setAttribute('passthrough', $this->passthrough);
        }
        if ($add_edit) {
            $node->setAttribute ('add', ($add_edit['add']? 'y': 'n'));
            $node->setAttribute ('edit', ($add_edit['edit_view']? 'y': 'n'));
        }
        return $node;
    }
    
    
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
        
        echo "&lt;{$this->name}&gt; {$this->file}";
        if ($this->passthrough != '') {
            echo " ({$this->passthrough})";
        }
        echo "\n";
    }
    
    /**
     * Loader of details from the xml
     * See ViewItem::loadFromXML for full specs
     */
    public function loadFromXML ($xml_params, $view) {
        if ($view != 'add' and $view != 'edit') {
            throw new exception ("Invalid usage of view item heading. Heading can only be used in the ADD or EDIT view.");
        }
        
        $this->file = $xml_params['FILE'];
        $this->name = $xml_params['NAME'];
        $this->passthrough = $xml_params['PASS'];
    }
    
    /**
     * Set the details for this IncludeViewItem. Should only be used in setup
     *
     * @param string $file The filename to include for this IncludeViewItem.
     * @param string $name The name to use for this IncludeViewItem.
     * @param string $passthrough The passthrough value to use
     */
    public function setDetails ($file, $name, $passthrough) {
        $this->file = $file;
        $this->name = $name;
        $this->passthrough = $passthrough;
    }
    
    /**
     * Get the XML attributes
     */
    public function getXMLAttribs () {
        return array ('type' => 'inc', 'file' => $this->file, 'name' => $this->name, 'pass' => $this->passthrough);
    }
    
    
    /**
     * Gets the file name for the file to include
     * @return string The file name
     */
    public function getFilename () {
        return $this->file;
    }
    
    /**
     * Gets a value that should be put into the variable $passthrough at
     * include-time.
     * @return string The value to use for the passthrough variable
     */
    public function getPassthroughValue () {
        return $this->passthrough;
    }
    
    /**
     * Gets the text for this include
     * @return string The text for this include
     */
    public function getName () {
        return $this->name;
    }
    
    
    /**
     * Sets the name for this include
     * @author Josh 2008-03-06
     * @param string $name The new name of the include
     */
    public function setName ($name) {
        $this->name = $name;
    }
    
    /**
     * Sets the file to include
     * @author Josh 2008-03-06
     * @param string $file The filename of the file to include
     */
    public function setFilename ($file) {
        $this->file = $file;
    }
    
    /**
     * Sets the value that should be put into the variable $passthrough at
     * include-time.
     * @author Josh 2008-03-06
     * @param string $passthrough The new value to use for the passthrough
     *        variable
     */
    public function setPassthroughValue ($passthrough) {
        $this->passthrough = $passthrough;
    }
    
    public function __toString () {
        return __CLASS__. "{ name: {$this->name}; file: {$this->file}; }";
    }
}
?>
