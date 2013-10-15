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
 * This is the view item for a heading.
 * If this item is encounted on a view list, a heading should be rendered
 * @package meta_xml
 * @subpackage view
 * @author Josh 2007-08-24
 */
class HeadingViewItem implements ViewItem {
    
    private $name;
    
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
        
        echo "{$this->name}\n";
    }
    
    /**
     * Loader of details from the xml
     * See ViewItem::loadFromXML for full specs
     */
    public function loadFromXML ($xml_params, $view) {
        if ($view != 'add' and $view != 'edit') {
            throw new exception ("Invalid usage of view item heading. Heading can only be used in the ADD or EDIT view.");
        }
        $this->name = trim ($xml_params['NAME']);
    }
    
    /**
     * Get the XML attributes
     */
    public function getXMLAttribs () {
        return array ('type' => 'heading', 'name' => $this->name);
    }
    
    
    /**
     * Set the details for this HeadingViewItem. Should only be used in setup
     *
     * @param string $name The name to use for this HeadingViewItem.
     */
    public function setDetails ($name) {
        $this->name = $name;
    }
    
    /**
     * Gets the text for this heading
     * @return string The text for this heading
     */
    public function getName () {
        return $this->name;
    }
    
    
    /**
     * Sets the name for this HeadingViewItem
     * @author Josh 2008-03-06
     * @param string $name The new name for this HeadingViewItem
     */
    public function setName ($name) {
        $this->name = $name;
    }
    
    public function __toString () {
        return __CLASS__ ." { name: {$this->name}; }";
    }
}
?>
