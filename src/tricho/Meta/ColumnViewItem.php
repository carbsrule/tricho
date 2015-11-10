<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

use \DOMDocument;
use \Exception;

/**
 * This is the view item for a column.
 * If this item is encounted on a view list, a column should be rendered
 * @package meta_xml
 * @subpackage view
 * @author Josh 2007-08-24
 */
class ColumnViewItem extends ViewItem {
    
    private $column;
    private $editable = false;
    
    /**
     * Creates a DOMElement that represents this item (for use in tables.xml)
     * @param DOMDocument $doc The document to which this node will belong
     * @param array $add_edit The add/edit info from the add/edit view to which
     *        this item belongs, i.e. has keys 'add', 'edit_view' and
     *        'edit_change'
     * @return DOMElement
     * @author benno, 2011-08-09
     */
    function toXMLNode (DOMDocument $doc, $add_edit = array ()) {
        $node = $doc->createElement ('item');
        $node->setAttribute ('type', 'col');
        $node->setAttribute ('name', $this->column->getName ());
        if ($add_edit) {
            $node->setAttribute ('add', ($add_edit['add']? 'y': 'n'));
            $edit = 'n';
            if ($add_edit['edit_change']) {
                $edit = 'y';
            } else if ($add_edit['edit_view']) {
                $edit = 'v';
            }
            $node->setAttribute ('edit', $edit);
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
        
        echo $this->column->getName ();
        if ($link = $this->column->getLink ()) {
            echo ' -&gt; ', $link->getToColumn ()->getTable ()->getName (), '.', $link->getToColumn ()->getName ();
        }
        echo "\n";
    }
    
    /**
     * Loader of details from the xml
     * See ViewItem::loadFromXML for full specs
     */
    public function loadFromXML ($xml_params, $view) {
        $this->column = $xml_params['_table']->get ($xml_params['NAME']);
        
        if ($this->column == null) {
            die ("The column named '{$xml_params['NAME']}' that was specified for this ColumnViewItem
                does not exist in the table '{$xml_params['_table']->getName()}'.");
        }
        
        switch ($view) {
            case 'add':
                if ($xml_params['ADD'] == 'y') $this->editable = true;
                break;
            
            case 'edit':
                if ($xml_params['EDIT'] == 'y') $this->editable = true;
                break;
        }
    }
    
    /**
     * Set the details for this ColumnViewItem. Should only be used in setup
     *
     * @param Column $column The column to use for this ColumnViewItem
     * @param bool $editable True if this column should be editable, false
     *        otherwise.
     */
    public function setDetails (Column $column, $editable) {
        $this->column = $column;
        $this->editable = $editable;
    }
    
    /**
     * Get the XML attributes
     */
    public function getXMLAttribs () {
        return array('type' => 'col', 'name' => $this->column->getName());
    }
    
    
    
    /**
     * Gets the column for this ColumnViewItem
     * @return Column The column
     */
    public function getColumn () {
        return $this->column;
    }
    
    /**
     * Gets the editable flag for this ColumnViewItem
     * @return boolean The flag value
     */
    public function getEditable () {
        return $this->editable;
    }
    
    
    /**
     * Sets the column for this ColumnViewItem
     * @author Josh 2008-03-06
     * @param Column $column The column to use for this ColumnViewItem
     */
    public function setColumn (Column $column) {
        if ($column == null) {
            throw new Exception("Invalid column specified");
        }
        
        $this->column = $column;
    }
    
    /**
     * Sets the editable flag for this ColumnViewItem
     * @author Josh 2008-03-06
     * @param boolean $editable The flag value
     */
    public function setEditable ($editable) {
        $this->editable = $editable;
    }
    
    
    /**
     * Displays a better debug string
     */
    public function __toString () {
        return 'ColumnViewItem { column: '. $this->column->getName (). '; editable: '.
            ($this->editable? 'y': 'n'). ' }';
    }
}
?>
