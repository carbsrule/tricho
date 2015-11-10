<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

/**
 * Stores meta-data about a database link between a column in one table and a column in another
 *
 * @author Josh, Benno 2007-07-*
 * @package meta_xml
 */
class Link {
    private $from_column;
    private $to_column;
    private $desc;
    private $format_type;
    private $parent;
    private $top_item;
    private $alt_eng_name;
    private $show_record_count = null;
    private $inline_search = false;
    private $order;
    
    /**
     * Create a new link
     *
     * @param Column $from The from column
     * @param Column $to The to column
     * @param array $desc An array of descriptors
     * @param array $opts An array of options. Valid options:
     *        'type': The format type. Defaults to LINK_FORMAT_SELECT
     *        'parent': If this is a parent link. Defaults to false
     *        'top_item': The first item text. Defaults to an empty string
     *        'alt_eng_name': An alternate english name. only for parental links
     *        'show_cnt': True to show record counts, false to not show record
     *        counts, null to inherit value from table (default)
     *        'order': The ordering method. Defaults to ORDER_LINKED_TABLE
     */
    function __construct (Column $from, Column $to, $desc, $opts = null) {
        // description valid
        if ((!is_array ($desc)) or (count ($desc) == 0)) {
            throw new Exception ('Invalid data type for $desc.');
        }
        
        // default options
        if ($opts == null) $opts = array ();
        if (!isset ($opts['type'])) $opts['type'] = LINK_FORMAT_SELECT;
        if (!isset ($opts['parent'])) $opts['parent'] = false;
        if (!isset ($opts['top_item'])) $opts['top_item'] = '';
        if (!isset ($opts['alt_eng_name'])) $opts['alt_eng_name'] = null;
        if (!isset ($opts['order'])) $opts['order'] = ORDER_LINKED_TABLE;
        
        // set our values
        $this->from_column = $from;
        $this->to_column = $to;
        $this->desc = $desc;
        $this->format_type = $opts['type'];
        $this->parent = $opts['parent'];
        $this->first_item = $opts['top_item'];
        $this->alt_eng_name = $opts['alt_eng_name'];
        $this->show_record_count = $opts['show_cnt'];
        $this->order = $opts['order'];
    }
    
    /**
     * Returns a string representing this object. Used for debugging
     */
    function __toString () {
        $s = 'Link: ';
        $s .= $this->from_column->getTable ()->getName () . '.' . $this->from_column->getName () . ' -> ';
        $s .= $this->to_column->getTable ()->getName () . '.' . $this->to_column->getName ();
        
        return $s;
    }
    
    /**
     * Get the linked table
     *
     * @return Table The linked table
     */
    function getToTable () {
        return $this->getToColumn ()->getTable ();
    }
    
    /**
     * Get the from column
     *
     * @return Column The from column
     */
    function getFromColumn () {
        return $this->from_column;
    }
    
    ///**
    //* Set the from column
    //* @param Column $column The new from column
    //* @todo Should this move the link across from the old column to the new one (Column::setLink)??
    //*/
    //function setFromColumn(Column $column) {
    //    $this->from_column = $column;
    //}
    
    /**
     * Get the to column
     *
     * @return Column To to column
     */
    function getToColumn () {
        return $this->to_column;
    }
    
    ///**
    //* Set the to column
    //* @param Column $column The new to column
    //*/
    //function setToColumn(Column $column) {
    //    $this->to_column = $column;
    //}
    
    /**
     * Returns an array of descriptors (string or column objects)
     *
     * @return Array The descriptors
     */
    function getDescription () {
        return $this->desc;
    }
    
    /**
     * Sets a new description for the link.
     * Accepts an array of descriptors (string or column objects)
     *
     * @param Array $desc The descriptors
     */
    function setDescription ($desc) {
        if (!is_array ($desc) or count ($desc) == 0) {
            throw new Exception ('Invalid data type for $desc.');
        }
        $this->desc = $desc;
    }
    
    /**
     * Gets the format type of this link
     *
     * @return int The type (e.g. LINK_FORMAT_SELECT)
     */
    function getFormatType () {
        return $this->format_type;
    }
    
    /**
     * Sets a new format type
     *
     * @param int $type The type: LINK_FORMAT_SELECT, LINK_FORMAT_RADIO or
     *        LINK_FORMAT_INLINE_SEARCH
     */
    function setFormatType ($type) {
        switch ($type) {
            case LINK_FORMAT_SELECT:
            case LINK_FORMAT_RADIO:
            case LINK_FORMAT_INLINE_SEARCH:
                $this->format_type = $type;
                break;
            
            default:
                throw new Exception ('Invalid format type (' . $type . ') for ' . $this);
        }
    }
    
    /**
     * Returns true of this link is a parent link, or false otherwise
     *
     * @return bool See if you can work it out
     */
    function isParent () {
        return $this->parent;
    }
    
    /**
     * Sets if this link is a perent or not
     *
     * @param bool $parent True if this link should be a parent, false otherwise
     */
    function setIsParent ($parent) {
        $this->parent = $parent;
    }
    
    /**
     * Get the top item
     *
     * @return string The top item
     */
    function getTopItem () {
        return $this->first_item;
    }
    
    /**
     * Gets a SelectQuery used to show a select list or bunch of radio buttons
     * for selecting from a linked table
     * Does not support trees - an exception is thrown instead.
     *
     * @return SelectQuery An editable select query
     */
    function getChooserQuery () {
        return $this->from_column->getChooserQuery ();
    }
    
    /**
     * Get the show_record_count setting.
     * True to show tab record counts, False to not show tab record counts,
     * null to inherit from table
     *
     * @author Josh 2007-08-14
     * @return boolean The setting
     */
    function getShowRecordCount () {
        return $this->show_record_count;
    }
    
    /**
     * Get the ordering method
     *
     * @return int The ordering method (1: ORDER_DESCRIPTORS; 2:
     *         ORDER_LINKED_TABLE)
     * @author Lay 2009-12-23
     */
    function getOrderingMethod () {
        return $this->order;
    }
    
    /**
     * Set the ordering method for the linked column
     *
     * @param $order The ordering method (1: ORDER_DESCRIPTORS; 2:
     *        ORDER_LINKED_TABLE)
     * @author Lay 2009-12-23
     */
    function setOrderingMethod ($order) {
        if ($order == ORDER_DESCRIPTORS) {
            $this->order = $order;
        } else {
            $this->order = ORDER_LINKED_TABLE;
        }
    }
    
    /**
     * Determine if we should show the record count for this tab
     *
     * @return boolean True if we should, false otherwise
     * @author Josh 2007-08-14
     */
    function showTabCount () {
        if ($this->show_record_count === null) {
            //echo 'inheriting...';
            return $this->to_column->getTable ()->showTabCount ();
        } else {
            //echo 'using '; var_dump($this->show_record_count);
            return $this->show_record_count;
        }
    }
    
    /**
     * Sets an alternate english name for the from table when editing a row of
     * the to table. This is only valid for parental links.
     * 
     * @author Benno, 2007-07-16
     * 
     * @param string $eng_name
     */
    function setAltEngName ($eng_name) {
        settype ($eng_name, 'string');
        if ($eng_name != '') {
            $this->alt_eng_name = $eng_name;
        } else {
            $this->alt_eng_name = null;
        }
    }
    
    /**
     * Gets an alternate english name for the from table when editing a row of
     * the 'to' table. This is only valid for parental links.
     * 
     * @author Benno, 2007-07-16
     * 
     * @return mixed $eng_name a string if the name exists and the link is a
     *         parent link, or null otherwise
     */
    function getAltEngName () {
        if ($this->isParent () and $this->alt_eng_name != null) {
            return $this->alt_eng_name;
        } else {
            return null;
        }
    }
}

?>
