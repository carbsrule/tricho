<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

use \DOMDocument;
use \DOMElement;
use \Exception;

use Tricho\Meta;
use Tricho\Runtime;

/**
 * @package meta_xml
 */

/**
 * Stores meta-data about the tables in a database
 * @package meta_xml
 */
class Database {
    private $tables;
    private $menu_type;
    private $data_check;
    private $section_headings;
    private $primary_headings;
    private $show_sub_record_count = true;
    private $show_search = false;
    private $help_table;
    private $convert_output;
    static private $loaded_files = [];
    
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
        
        echo "tables {\n";
        
        foreach ($this->tables as $table) {
            print_human ($table, $indent_tab + 1);
        }
        
        echo $indent, "}\n";
        
    }
    
    
    /**
     * Read a Tricho-formatted XML file into meta-data store.
     * 
     * @param string $file_name Path of XML file to read; '' for default path
     * @param bool $force_reload If false, use cache if possible
     * @author benno 2011-08-12 complete rewrite to use DOMDocument instead of
     *         XML Parser
     * @return Database the meta-data store
     */
    static function parseXML($file_name = '', $force_reload = false) {
        if ($file_name == '') {
            $file_name = Runtime::get('root_path') . 'tricho/data/tables.xml';
        }
        if (!$force_reload and isset(self::$loaded_files[$file_name])) {
            return self::$loaded_files[$file_name];
        }
        
        $readable = true;
        if (file_exists ($file_name) and is_file ($file_name)) {
            if (!is_readable ($file_name)) $readable = false;
        } else {
            $readable = false;
        }
        if (!$readable) return new Database ();
        
        if (filesize ($file_name) == 0) {
            return new Database ();
        }
        
        $doc = new DOMDocument ();
        $doc->load ($file_name);
        
        $dbs = $doc->getElementsByTagName ('database');
        if ($dbs->length != 1) {
            throw new Exception ('Invalid XML');
        }
        $db_node = $dbs->item (0);
        $db = self::fromXMLNode($db_node);
        self::$loaded_files[$file_name] = $db;
        return $db;
    }
    
    
    /**
     * Creates a Database meta object from a corresponding XML node.
     * Also creates its Table and Column objects by calling their respective
     * fromXMLNode methods.
     * @param DOMElement $node The database node
     * @author benno 2011-08-15
     * @return Database the meta-data store
     */
    static function fromXMLNode (DOMElement $node) {
        $db = new Database ();
        
        if ($node->hasAttribute('primary_headings')) {
            $db->setShowPrimaryHeadings(Meta::toBool($node->getAttribute('primary_headings')));
        }
        if ($node->hasAttribute('section_headings')) {
            $db->setShowSectionHeadings(Meta::toBool($node->getAttribute('section_headings')));
        }
        if ($node->hasAttribute('show_sub_record_count')) {
            $db->setShowSubRecordCount(Meta::toBool($node->getAttribute('show_sub_record_count')));
        }
        if ($node->hasAttribute('show_search')) {
            $db->setShowSubRecordCount(Meta::toBool($node->getAttribute('show_search')));
        }
        
        // load tables
        $children = $node->childNodes;
        foreach ($children as $child) {
            if ($child->nodeType == XML_ELEMENT_NODE and $child->nodeName == 'table') {
                $table = Table::fromXMLNode ($child);
                $db->addTable ($table);
                $table->setDatabase ($db);
            }
        }
        
        $help_table_name = $node->getAttribute ('help_table');
        if ($help_table_name != '') {
            $help_table = $db->getTable ($help_table_name);
            if ($help_table == null) {
                throw new Exception('Missing help table');
            }
            $db->help_table = $help_table;
        }
        
        // Activate LinkColumns, now that all tables are loaded
        foreach ($db->getTables() as $table) {
            foreach ($table->getColumns() as $col) {
                if (!($col instanceof LinkColumn)) continue;
                
                list($table_name, $col_name) = @explode('.', $col->getTarget());
                $target_table = $db->get($table_name);
                if (!$target_table) {
                    throw new Exception('Unknown table: ' . $table_name);
                }
                $target_col = $target_table->get($col_name);
                if (!$target_col) {
                    $err = 'Unknown column: ' . $table_name . '.' . $col_name;
                    throw new Exception($err);
                }
                $col->setTarget($target_col);
            }
        }
        return $db;
    }
    
    
    /**
     * Saves meta-data to an XML file.
     * 
     * This is used to create tables.xml, which is used by admin and setup pages
     *
     * @param string $file_loc Name and location of file in which to save data
     * @param string $success_page Redirect to this location if dump succeeds.
     *        If this parameter is blank, no redirect will be performed.
     *        Otherwise, a session confirmation message
     *        ($_SESSION['setup']['msg']) will be set, and the browser
     *        redirected.
     * @return void
     * @author benno, 2011-08-09 (rewrite)
     */
    function dumpXML($file_loc = '', $success_page = 'table_edit.php') {
        if ($file_loc == '') {
            $file_loc = Runtime::get('root_path') . 'tricho/data/tables.xml';
        }
        
        // check write permissions
        $dir = dirname ($file_loc);
        
        $permissions_ok = false;
        if ((is_file ($file_loc) and is_writeable ($file_loc)) or is_writeable ($dir)) {
            $permissions_ok = true;
        }
        
        if (!$permissions_ok) {
            throw new FileNotWriteableException ($file_loc);
        }
        
        // Generate XML tree
        $doc = new DOMDocument ('1.0', 'utf-8');
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $database_node = $doc->createElement ('database');
        $doc->appendChild ($database_node);
        $params = [
            'primary_headings' => Meta::toYesNo($this->primary_headings),
            'section_headings' => Meta::toYesNo($this->section_headings),
            'show_sub_record_count' => Meta::toYesNo($this->show_sub_record_count),
            'show_search' => Meta::toYesNo($this->show_search)
        ];
        
        if ($this->help_table != null) {
            $params['help_table'] = $this->help_table->getName();
        }
        foreach ($params as $param => $value) {
            $database_node->setAttribute ($param, $value);
        }
        
        // arrange the tables so that the project's tables come first,
        // then the help table, and finally Tricho's internal tables
        $tables = array ();
        $internal_tables = array ();
        foreach ($this->tables as $table) {
            if ($table === $this->help_table) continue;
            $name = $table->getName();
            if ($name[0] == '_') {
                $internal_tables[] = $table;
                continue;
            }
            $tables[] = $table;
        }
        
        foreach ($tables as $table) {
            $database_node->appendChild ($table->toXMLNode ($doc));
        }
        if ($this->help_table != null) {
            $database_node->appendChild ($this->help_table->toXMLNode ($doc));
        }
        foreach ($internal_tables as $table) {
            $database_node->appendChild ($table->toXMLNode ($doc));
        }
        
        // Save new XML
        $xml = $doc->saveXML ();
        unset ($doc);
        $file = @fopen ($file_loc, 'w');
        if (!$file) {
            throw new FileNotWriteableException ($file_loc);
        }
        fwrite ($file, $xml);
        fclose ($file);
        
        if (is_array(@$_SESSION['setup']['msg'])) {
            $_SESSION['setup']['msg'][] = "XML written";
        } else {
            $_SESSION['setup']['msg'] = "XML written";
        }
        
        if ($success_page != null) redirect ($success_page);
    }
    
    
    /**
     * Constructor takes no arguments
     * 
     * @return Database
     */
    function __construct () {
        $this->tables = array ();
        $this->data_check = true;
        $this->primary_headings = false;
        $this->section_headings = true;
        $this->convert_output = CONVERT_OUTPUT_WARN;
    }
    
    /**
     * Adds meta-data about a table
     * 
     * @param Table $table The new table to add to the meta-data store
     * 
     * @return void
     */
    function addTable (Table $table) {
        $this->tables[] = $table;
    }
    
    /**
     * Set the table that should function as a help table
     */
    function setHelpTable (Table $table) {
        $this->help_table = $table;
    }
    
    /**
     * Gets the help table for this database
     * 
     * @return mixed a Table, or null
     */
    function getHelpTable () {
        return $this->help_table;
    }
    
    
    /**
     * Sets whether or not the main view search bar will be open by default for
     * all tables. This can be overridden on an individual table basis.
     * 
     * @author benno, 2008-09-14
     * 
     * @param bool $search True if the search bar will be open
     */
    function setShowSearch ($search) {
        $this->show_search = (bool) $search;
    }
    
    
    /**
     * Gets whether or not the main view search bar will be open by default for
     * all tables.
     * 
     * @author benno, 2008-09-14
     * 
     * @return bool $search True if the search bar will be open
     */
    function getShowSearch () {
        return $this->show_search;
    }
    
    
    /**
     * Returns one of CONVERT_OUTPUT_FAIL|WARN|NONE
     */
    function getConvertOutput () {
        return $this->convert_output;
    }
    
    /**
     * If a conversion should fail, warn or be silently ignored
     */
    function setConvertOutput ($value) {
        $this->convert_output = $value;
    }
    
    /**
     * Sets the data checking parameter
     *
     * @param bool $value True = perform data checking; False = Don't
     */
    function setDataChecking ($value) {
        $this->data_check = (bool) $value;
    }
    
    /**
     * Gets the data checking parameter
     *
     * @return bool True if performing data checking, false otherwise
     */
    function getDataChecking () {
        return $this->data_check;
    }
    
    /**
     * Sets whether or not to use headings for the top-level menu item
     *
     * @param bool $value
     */
    function setShowPrimaryHeadings ($value) {
        $this->primary_headings = (bool) $value;
    }
    
    /**
     * Sets the Show Section Headings parameter
     *
     * @param bool $value True to show section headings, false to not.
     */
    function setShowSectionHeadings ($value) {
        $this->section_headings = (bool) $value;
    }
    
    /**
     * Gets the Show Section Headings parameter
     *
     * @return bool True if section headings are enabled, false if they are not
     */
    function getShowSectionHeadings () {
        return $this->section_headings;
    }
    
    /**
     * Set the show_sub_record_count setting.
     * True to show tab record counts, False to not show tab record counts
     *
     * @author Josh 2007-08-14
     * @param boolean $value The setting
     */
    function setShowSubRecordCount ($value) {
        $this->show_sub_record_count = $value;
    }
    
    /**
     * Gets whether or not to use headings for the top-level menu item
     *
     * @param bool $value
     */
    function getShowPrimaryHeadings () {
        return $this->primary_headings;
    }
    
    /**
     * Get the show_sub_record_count setting.
     * True to show tab record counts, False to not show tab record counts
     *
     * @author Josh 2007-08-14
     * @return boolean The setting
     */
    function getShowSubRecordCount () {
        return $this->show_sub_record_count;
    }
    
    /**
     * Determine if we should show the record count tabs in this database
     * This is an alias of getShowSubRecordCount()
     *
     * @author Josh 2007-08-14
     * @return boolean True if we should, false otherwise
     */
    function showTabCount () {
        //echo 'using '; var_dump($this->show_sub_record_count);
        return $this->show_sub_record_count;
    }
    
    /**
     * Gets the meta-data for a table (searches by table name)
     *
     * @param string $table_name The name of the table to get meta-data for
     * @return Table
     */
    function getTable ($table_name) {
        $found_table = null;
        foreach ($this->tables as $table) {
            if ($table->getName() == $table_name) {
                $found_table = $table;
                break;
            }
        }
        return $found_table;
    }
    
    /**
     * Short for {@link getTable}
     *
     * @param string $table_name The name of the table to get meta-data for
     * @return Table
     */
    function get ($table_name) {
        return $this->getTable ($table_name);
    }
    
    /**
     * Gets the meta-data for a table (searches by table mask - i.e. hides the
     * table name)
     *
     * @param string $mask The mask that identifies the table to get meta-data
     *        for
     * @return Table
     */
    function getTableByMask ($mask) {
        $found_table = null;
        foreach ($this->tables as $table) {
            if ($table->getMask () == $mask) {
                $found_table = $table;
                break;
            }
        }
        return $found_table;
    }
    
    /**
     * Gets the meta-data for a table (given a number that matches the key of
     * the table in the database object's table array).
     *
     * @param int $int The number of the table (counting as per a normal array,
     * starting at 0. Tables are numbered in the order they are stored in the
     * XML and corresponding meta-data store).
     * @return Table
     */
    function getTableNum ($int) {
        $found_table = null;
        settype ($int, 'int');
        foreach ($this->tables as $id => $table) {
            if ($id == $int) {
                $found_table = $table;
                break;
            }
        }
        return $found_table;
    }
    
    /**
     * Gets the meta-data for all tables, in the order they were added to this
     * Database object.
     *
     * @return array Array of Tables
     */
    function getTables () {
        return $this->tables;
    }
    
    /**
     * Gets the meta-data for all tables, in alphabetical order.
     *
     * @return array Array of Tables
     */
    function getOrderedTables () {
        $tables = $this->tables;
        usort($tables, function($a, $b) {
            $a_name = $a->getName();
            $b_name = $b->getName();
            if ($a_name[0] == '_' and $b_name[0] != '_') return 1;
            if ($a_name[0] != '_' and $b_name[0] == '_') return -1;
            return strcmp($a->getName(), $b->getName());
        });
        return $tables;
    }
    
    /**
     * Removes meta-data about a table from the database meta-data store
     * 
     * @param string $table_name The name of the table to be removed
     * @return bool True if the table was deleted, false otherwise (ie: the
     *         table wasn't found)
     */
    function removeTable ($table_name) {
        $removed = false;
        foreach ($this->tables as $id => $table) {
            if ($table->getName() == $table_name) {
                unset($this->tables[$id]);
                $removed = true;
                break;
            }
        }
        return $removed;
    }
    
    
    public function __toString () {
        return __CLASS__;
    }
    
}

?>
