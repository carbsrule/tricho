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
 * This defines what every view item must extend
 * @package meta_xml
 * @subpackage view
 * @author Josh 2007-08-24 original interface
 * @author benno 2011-08-15 changed to abstract class with factory method
 *         fromXMLNode
 */
abstract class ViewItem {
    /**
     * Creates a ViewItem meta object from a corresponding XML node.
     * @param DOMElement $node The item node
     * @param Table $table The table to which this view belongs
     * @author benno 2011-08-15
     * @return ViewItem the meta-data store
     */
    static function fromXMLNode (DOMElement $node, Table $table) {
        $attribs = HtmlDom::getAttribArray ($node);
        
        switch ($node->getAttribute ('type')) {
            case 'col':
                $item = new ColumnViewItem ();
                $col = $table->get ($attribs['name']);
                if ($col == null) {
                    throw new Exception ('No such column: '. $attribs['name']);
                }
                $item->setColumn ($col);
                return $item;
                break;
            
            case 'inc':
                $item = new IncludeViewItem();
                $item->setDetails(
                    @$attribs['file'],
                    @$attribs['name'],
                    @$attribs['passthrough']
                );
                return $item;
                break;
            
            case 'func':
                $item = new FunctionViewItem();
                $item->setDetails(@$attribs['name'], @$attribs['code']);
                return $item;
                break;
            
            default:
                throw new Exception ('Unknown view item type');
        }
    }
    
    /**
     * The object should create itself
     * @param string $xml_params The parameters as provided by the xml parser
     * @param string $view The view that this item will be getting added to
     */
    public abstract function loadFromXML ($xml_params, $view);
    
    
    /**
     * Return as a key-value pair the attributes that should be used to turn
     * this object back into an XML tag. This is basically the opposite of
     * loadFromXML.
     *
     * @return array The XML attributes.
     */
    public abstract function getXMLAttribs ();
    
}
?>
