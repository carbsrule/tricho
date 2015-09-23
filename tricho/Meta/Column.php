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
use \InvalidArgumentException;

use Tricho\Runtime;
use Tricho\DataUi\Form;
use Tricho\DataUi\FormManager;
use Tricho\Meta;
use Tricho\Query\QueryField;
use Tricho\Util\HtmlDom;

interface ColumnInterface {
    /**
     * Gets the list of allowed SQL types for this type of column
     * @return array Each element is an uppercase string, e.g. 'INT'
     */
    static function getAllowedSqlTypes();
    
    /**
     * Gets the default SQL type for this type of column
     * @return string
     */
    static function getDefaultSqlType();
}


/**
 * Stores meta-data about a database column
 * @package meta_xml
 */
abstract class Column implements QueryField, ColumnInterface {
    protected $table;
    protected $name;
    protected $engname;
    protected $sqltype;
    protected $sql_size;
    protected $sql_attributes = array ();
    protected $sql_collation;
    protected $mandatory;
    protected $comment;
    protected $params;
    protected $default = null;
    
    
    /**
     * @param string $name The name of this column (in the database)
     * @param mixed $table The Table to which this column belongs, or null
     */
    function __construct ($name, $table = null) {
        if ($table !== null and !($table instanceof Table)) {
            throw new Exception ('Invalid table');
        }
        $this->name = (string) $name;
        $this->table = $table;
        $this->params = array ();
    }
    
    
    /**
     * Creates a DOMElement that represents this column (for use in tables.xml)
     * @param DOMDocument $doc The document to which this node will belong
     * @return DOMElement
     * @author benno, 2011-08-09
     */
    function toXMLNode (DOMDocument $doc) {
        $node = $doc->createElement ('column');
        $node->setAttribute ('name', $this->name);
        $node->setAttribute ('engname', $this->engname);
        $node->setAttribute ('class', get_class ($this));
        $node->setAttribute ('sql_defn', Meta::getSqlDefn($this));
        $node->setAttribute ('mandatory', Meta::toYesNo($this->mandatory));
        
        if ($this->comment != '') {
            $comment_node = HtmlDom::appendNewChild ($node, 'comment');
            $comment = trim ($this->comment);
            $comment = str_replace ("\r\n", '<br/>', $comment);
            $comment = str_replace (array ("\r", "\n"), '<br/>', $comment);
            $comment_node->appendChild ($doc->createCDATASection ($comment));
        }
        return $node;
    }
    
    
    /**
     * Creates a Column meta object from a corresponding XML node.
     * @param DOMElement $node The column node
     * @author benno 2011-08-15
     * @return Column the meta-data store
     */
    static function fromXMLNode (DOMElement $node) {
        $attribs = HtmlDom::getAttribArray ($node);
        if ($attribs['class'] == '') {
            throw new InvalidArgumentException('Node missing class attribute');
        }
        $class = $attribs['class'];
        if (strpos($class, '\\') === false) $class = 'Tricho\\Meta\\' . $class;
        $col = new $class($attribs['name']);
        $col->applyXMLNode ($node);
        return $col;
    }
    
    
    /**
     * Applies generic settings from an XML node to an extant Column meta object
     * @param DOMElement $node The column node
     * @author benno 2011-08-15
     * @return Column the meta-data store
     */
    function applyXMLNode(DOMElement $node) {
        $attribs = HtmlDom::getAttribArray ($node);
        $this->setMandatory (Meta::toBool($attribs['mandatory']));
        $this->setEngName ($attribs['engname']);
        list ($sql_type, $size, $sql_attribs) = Meta::getSqlParams($attribs['sql_defn']);
        $this->setSqlType ($sql_type);
        $this->setSqlSize ($size);
        $this->setSqlAttributes ($sql_attribs);
        
        if ($this instanceof FileColumn) {
            $this->setMask ($attribs['mask']);
        }
        
        $comment_node = HtmlDom::getChildByTagName ($node, 'comment');
        if ($comment_node) {
            foreach ($comment_node->childNodes as $child) {
                if ($child->nodeType == XML_CDATA_SECTION_NODE) {
                    $comment = $child->data;
                    $this->setComment ($comment);
                    break;
                }
            }
        }
        
        if ($attribs['class'] != '') return;
        
        // Process options for old column definitions
        if ($attribs['option'] != '') {
            // Support for Kevin Roth RTE has been removed. Old fields to become TinyMCE
            if ($attribs['option'] == 'richtext3') $attribs['option'] = 'richtext';
            $this->setOption (strtolower ($attribs['option']));
        }
        
        return $col;
    }
    
    
    /**
     * Gets an array of the configuration options for this column's definition
     * @return array
     */
    function getConfigArray () {
        $config = array (
            'name' => $this->name,
            'engname' => $this->engname,
            'class' => get_class ($this),
            'sqltype' => $this->sqltype,
            'sql_size' => $this->sql_size,
            'sql_attribs' => array ()
        );
        
        // break up the sql attributes into an array
        $config['sql_attribs'] = $this->sql_attributes;
        $config['sql_default'] = $this->default;
        $config['collation'] = $this->sql_collation;
        
        // If a column is mandatory, then it has to be NOT NULL
        if ($this->mandatory and !in_array ('NOT NULL', $config['sql_attribs'])) {
            $config['sql_attribs'][] = 'NOT NULL';
        }
        
        $config['mandatory'] = $this->mandatory;
        
        // work out which views the column is in
        $config['list_view'] = (int) $this->table->isColumnInView('list', $this, false);
        
        $form = FormManager::load("admin.{$this->getTable()->getName()}");
        if ($form == null) $form = new Form();
        
        $config['add_view'] = $form->getColumnItem($this, 'add') != null;
        $config['edit_view_show'] = $form->getColumnItem($this, 'edit-view') != null;
        $config['edit_view_edit'] = $form->getColumnItem($this, 'edit') != null;
        $config['export_view'] = $this->table->isColumnInView('export', $this, false);
        
        $config['comments'] = $this->comment;
        
        return $config;
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
        
        echo $this->name, "\n";
        
    }
    
    
    /**
     * Sets the name of this column
     * 
     * @param string $new_name The name to apply
     */
    function setName ($new_name) {
        $this->name = (string) $new_name;
    }
    
    
    /**
     * Sets the plain english name to use to describe this column
     * 
     * @param string $new_name The english name to apply
     */
    function setEngName ($new_name) {
        $this->engname = (string) $new_name;
    }
    
    
    /**
     * Sets the SQL type of this column.
     * Examples of SQL type are: INT; VARCHAR; DECIMAL.
     * 
     * @param string $type The SQL type to use. {@See SqlTypes}
     */
    function setSqlType($type) {
        $type = (string) $type;
        if (!in_array($type, SqlTypes::getAll())) {
            throw new Exception("Invalid type: {$type}");
        }
        $this->sqltype = $type;
        return true;
    }
    
    
    /**
     * Sets the SQL size of this column.
     *
     * Examples of SQL size are: 6 (eg for an INT or VARCHAR type); 7, 3 (e.g.
     * for a DECIMAL type).
     * 
     * @param string $str The SQL size to use.
     */
    function setSqlSize ($str) {
        $this->sql_size = (string) $str;
    }
    
    
    /**
     * Sets the SQL attributes of this column.
     *
     * Examples of SQL attributes are: UNSIGNED; AUTO_INCREMENT; NOT NULL.
     * These can be combined by adding a space between each - do not use a
     * comma.
     * 
     * @param string $str The SQL attributes to use.
     */
    function setSqlAttributes ($str) {
        if (!is_string ($str)) throw new InvalidArgumentException ('Expected string, got '. gettype ($str));
        // Parse standard attributes into an array
        $known_attributes = array ('UNSIGNED', 'AUTO_INCREMENT', 'NOT NULL');
        $sql_attributes = array ();
        foreach ($known_attributes as $attr) {
            if (stripos ($str, $attr) !== false) {
                $sql_attributes[] = $attr;
            }
        }
        $this->sql_attributes = $sql_attributes;
        
        // Parse and set default value
        $matches = array ();
        preg_match ("/default +(NULL|-?[0-9]+(\.[0-9]+)?|'(\\\\'|[^\\'])+'){1}/i", $str, $matches);
        if (isset ($matches[1])) {
            $default_value = $matches[1];
            $str = trim(substr($str, strlen($default_value)));
            if ($default_value[0] == "'" and $default_value[strlen ($default_value) - 1] == "'") {
                $default_value = substr ($default_value, 1, -1);
                $default_value = preg_replace ("/\\\\'|''/", "'", $default_value);
                $default_value = str_replace('\\\\', '\\', $default_value);
            }
            $this->default = $default_value;
        }
        
        $matches = array();
        preg_match("/collate\s+([a-z0-9\_]+)/i", $str, $matches);
        if (isset($matches[1])) {
            $this->sql_collation = $matches[1];
        }
    }
    
    
    /**
     * Gets the collation for this column.
     * Will be an empty string for non-string columns
     * @see http://dev.mysql.com/doc/refman/5.1/en/charset-mysql.html
     */
    function getCollation() {
        return $this->sql_collation;
    }
    
    
    /**
     * Sets the collation for this (string) column.
     * Only use this if the column is going to have a different collation from
     * its parent table.
     * @param string $collation @see http://dev.mysql.com/doc/refman/5.1/en/charset-mysql.html
     */
    function setCollation($collation) {
        if ($collation == '') {
            $this->sql_collation = '';
            return;
        }
        
        if (!in_array($this->sqltype, SqlTypes::getTextual())) {
            $err = "Wrong kind of column ({$this->sqltype}) for a collation";
            throw new InvalidArgumentException($err);
        }
        $this->sql_collation = $collation;
    }
    
    
    /**
     * Defines a comments for this column
     *
     * @param string $str A comment for the database programmer's documentation
     */
    function setComment ($str) {
        $this->comment = (string) $str;
    }
    
    /**
     * Sets whether this column is mandatory or not
     * 
     * Mandatory columns require the user to enter some data.
     * 
     * @param bool $bool Whether the column should be mandatory or not.
     */
    function setMandatory ($bool) {
        if ($bool != false) {
            $this->mandatory = true;
        } else {
            $this->mandatory = false;
        }
    }
    
    
    /**
     * Stores a back-reference from this column to the table to which it
     * belongs.
     * 
     * @param Table $table The table under which this column resides.
     */
    function setTable (Table $table) {
        $this->table = $table;
    }
    
    
    /**
     * Gets the database name of this column
     * 
     * @return string The database name of this column.
     */
    function getName () {
        return $this->name;
    }
    
    
    /**
     * Gets the plain english name of this column
     * 
     * @return string The plain english name of this column.
     */
    function getEngName () {
        return $this->engname;
    }
    
    
    /**
     * Gets the full name (table.column)
     * @return string
     */
    function getFullName() {
        $name = ($this->table == null)? '???': $this->table->getName();
        $name .= '.' . $this->name;
        return $name;
    }
    
    
    /**
     * @author benno, 2013-01-08
     */
    function hasDuplicateEnglishName() {
        $eng_name = $this->engname;
        $table = $this->table;
        if ($table == null) return false;
        foreach ($table->getColumns() as $col) {
            if ($col === $this) continue;
            if ($col->engname == $eng_name) return true;
        }
        return false;
    }
    
    
    /**
     * Gets the SQL type of this column
     * e.g. INT or VARCHAR, etc.
     * 
     * @return string The SQL type of this column.
     */
    function getSqlType () {
        return $this->sqltype;
    }
    
    
    /**
     * Gets the SQL size of this column
     * e.g. 11 (for an INT type); 6, 2 (for a DECIMAL type).
     * 
     * @return string The SQL size of this column.
     */
    function getSqlSize () {
        return $this->sql_size;
    }
    
    
    /**
     * Gets the SQL attributes of this column
     * e.g. AUTO_INCREMENT.
     * 
     * @return array The SQL attributes of this column.
     */
    function getSqlAttributes () {
        return $this->sql_attributes;
    }
    
    
    /**
     * Gets whether or not this column is mandatory.
     * 
     * @return bool
     */
    function isMandatory () {
        return $this->mandatory;
    }
    
    
    /**
     * Gets whether or not this column has the 'NOT NULL' sql attribute set
     *
     * @return bool True if this column has the NOT NULL sql attribute set,
     *         false otherwise
     */
    function isNullAllowed () {
        if (in_array ('NOT NULL', $this->sql_attributes)) {
            return false;
        } else {
            return true;
        }
    }
    
    
    /**
     * Gets whether or not this column is of a numeric SQL type
     * @return bool
     */
    function isNumeric() {
        return in_array($this->sqltype, SqlTypes::getNumeric());
    }
    
    
    /**
     * Gets whether or not this column has the AUTO_INCREMENT sql attribute set
     *
     * @return bool True if this column has the AUTO_INCREMENT sql attribute
     *         set, false otherwise
     * @author benno, 2010-11-15 using string attributes
     * @author benno, 2011-08-18 switched to array
     */
    function isAutoIncrement () {
        if (in_array ('AUTO_INCREMENT', $this->sql_attributes)) {
            return true;
        } else {
            return false;
        }
    }
    
    
    /**
     * Gets whether or not this column has the UNSIGNED sql attribute set
     *
     * @return bool True if this column has the UNSIGNED sql attribute set,
     *         false otherwise
     * @author benno, 2011-08-18
     */
    function isUnsigned () {
        if (in_array ('UNSIGNED', $this->sql_attributes)) {
            return true;
        } else {
            return false;
        }
    }
    
    
    /**
     * Gets the default value for a column if it has one
     *
     * @return string The default value, or null if the column does not have a
     *         default value
     */
    function getDefault () {
        return $this->default;
    }
    
    /**
     * Sets the default value for this column.
     * This function does no checking, so collateInput should be called on the
     * value beforehand.
     * @param mixed $value The new default value to use for this column
     * @return bool True if the new default value was set, false if it was
     *         invalid
     */
    function setDefault ($value) {
        $this->default = $value;
    }
    
    
    /**
     * Get the comment defined for this column
     * 
     * @return string Comment about this column, stored by and for the database
     * programmer
     */
    function getComment () {
        return $this->comment;
    }
    
    
    /**
     * Get the SQL definition for this column.
     * 
     * This includes the type, size and attributes combined in the normal SQL
     * manner, e.g. INT(10) UNSIGNED AUTO_INCREMENT
     * 
     * @return string The SQL definition.
     */
    function getSqlDefn() {
        $sql = $this->sqltype;
        $txt = $this->sql_size;
        if ($txt != '') {
            $sql .= "($txt)";
        }
        $txt = implode (' ', $this->sql_attributes);
        if ($txt != '') {
            $sql .= " $txt";
        }
        if ($this->default !== null) {
            $sql .= ' DEFAULT ' . sql_enclose($this->default);
        }
        if ($this->sql_collation != '') {
            $sql .= " COLLATE {$this->sql_collation}";
        }
        return $sql;
    }
    
    /**
     * Get the table to which this column belongs.
     * 
     * @return mixed The Table, or null.
     */
    function getTable () {
        return $this->table;
    }
    
    /**
     * Determine if this column is linked to the specified table
     * 
     * @param Table $table The table to be checked
     * @return bool True if this column is linked to the table
     * @deprecated is this used? If not, don't keep it
     */
    function linksTo ($table) {
        if (!($table instanceof Table)) return false;
        if ($this instanceof LinkColumn and $this->getLink ()->getToTable () === $table) return true;
        return false;
    }
    
    
    /**
     * Does this column have a link?
     * @return bool True if this column has a link, false otherwise
     * @deprecated just do instanceof LinkColumn
     */
    function hasLink () {
        if (!($this instanceof LinkColumn) or $this->getLink () == null) {
            return false;
        } else {
            return true;
        }
    }
    
    
    /**
     * Gets the mandatory suffix for this column, to append to an input label
     * @author benno 2012-04-12
     */
    function getMandatorySuffix () {
        if ($this->isMandatory ()) {
            if (defined ('IMAGE_MANDATORY') and IMAGE_MANDATORY != '') {
                return ' <img src="'. ROOT_PATH_WEB. IMAGE_MANDATORY. '" alt="*" class="mandatory" title="required">';
            } else {
                return '*';
            }
        }
        return '';
    }
    
    
    /**
     * Gets a label for this column, to use on add/edit forms
     * @author benno 2011-08-30
     */
    function getInputLabel () {
        $label = hsc ($this->engname). $this->getMandatorySuffix ();
        $help_columns = Runtime::get_help_text();
        
        $db = $this->table->getDatabase();
        if (@$_SESSION['setup']['view_h'] and $db->getHelpTable () != null) {
            $label .= ' <a href="help_edit.php?t='. hsc ($this->getTable ()->getName ());
            $label .= '&amp;c='. hsc ($this->getName ()). '" class="help">[help]</a>';
        } else if (@$help_columns[$this->getName()]['HasLongHelp']) {
            $label .= ' <a href="help.php?t='. hsc ($this->getTable ()->getName ());
            $label .= '&amp;c='. hsc ($this->getName ()). '" target="_blank" onclick="return popup_a(this);" class="help">[?]</a>';
        }
        
        $quick_help = trim(@$help_columns[$this->getName()]['QuickHelp']);
        if ($quick_help != '') {
            $label .= "<div class=\"quick_help\">". hsc ($quick_help). "</div>";
        }
        return $label;
    }
    
    
    /**
     * Common code to run at the start of each subclass' attachInputField
     * method. This sets up a DOMDocument with a <<form>> element if necessary,
     * then appends a <<p class="input">> in which the input will be stored.
     * @param Form $form the form to which the input belongs
     * @return DOMElement the <<p>> created to contain the input field(s)
     */
    static function initInput(Form $form) {
        $doc = $form->getDoc();
        if ($doc == null) {
            $form_el = $form->initDocForm();
        } else {
            $form_el = $doc->getElementsByTagName('form')->item(0);
            if (!$form_el) {
                $err = 'Form has DOMDocument with no form element';
                throw new Exception($err);
            }
        }
        $p = HtmlDom::appendNewChild($form_el, 'p', array('class' => 'input'));
        return $p;
    }
    
    
    /**
     * Attaches one or more input fields appropriate for this column to the
     * Form to which it belongs.
     *
     * The DOMNodes generated may be for a plain text input field, a text area,
     * a JavaScript Rich Text Editor field, a file input field, etc. The type
     * of field is determined by the class of the column, and its SQL type.
     * 
     * @param Form $form The form in which this element will be displayed
     * @param string $input_value The preset value for the input field, if
     *        applicable
     * @param array $primary_key The primary key of the record which this field
     *        is editing. The default is null, which is used to specify that
     *        there is no specific primary key. If this is not provided, file
     *        uploads and trees may fail to work correctly.
     *        This param can also be specified as a string, in which case it
     *        should be in $_GET['id'] format (i.e. the primary key fields
     *        should be separated by a comma)
     * @param array $field_params Some alternative field parameters can be
     *        specified here.
     *        'name': An alternative field name, instead of the column name.
     *        Useful for highly custom forms, or when a column is being
     *        displayed multiple times.
     *        'change_event': JavaScript for the onchange event (or onclick for
     *        binary fields)
     *        'max_size': Maximum size allowed for a file upload
     * @return void
     */
    abstract function attachInputField(Form $form, $input_value = '', $primary_key = null, $field_params = array());
    
    function getInputField(Form $form, $input_value = '', $primary_key = null, $field_params = array ()) {
        $this->attachInputField($form, $input_value, $primary_key, $field_params);
        $form_doc = $form->getDoc();
        $form_el = $form_doc->getElementsByTagName('form')->item(0);
        $p_els = $form_el->getElementsByTagName('p');
        for ($i = $p_els->length - 1; $i >= 0; --$i) {
            $p = $p_els->item($i);
            if (@$p->getAttribute('class') == 'input') {
                $input = $form_doc->saveHTML($p);
                $input = preg_replace('#^<p[^>]*>#', '', $input);
                $input = preg_replace('#</p>$#', '', $input);
                return $input;
            }
        }
    }
    
    
    /**
     * Gets the text used to display the value stored in a non-editable column.
     * 
     * @param string $input_value the value to be displayed
     * @return string The HTML text to be used to display the value.
     * @todo code something that will make the link query have a where clause
     * @todo add a primary key field, as per getInputField?
     */
    function displayValue ($input_value = '') {
        return hsc($input_value, ENT_COMPAT, '', false);
    }
    
    
    /**
     * Adds a display-only value to a Form (for a non-editable field)
     * 
     * @param Form $form The form on which to display the value
     * @param string $value The value to be displayed
     * @param array $pk The primary key of the row which contains the value
     * @return void
     */
    function attachValue(Form $form, $value, array $pk) {
        $doc = $form->getDoc();
        $form_el = $doc->getElementsByTagName('form')->item(0);
        $p = $doc->createElement('p');
        $form_el->appendChild($p);
        HtmlDom::appendNewText($p, $value);
    }
    
    
    /**
     * Returns an debugger-friendly version of this object
     */
    function __toString () {
        return 'Column:'. $this->table->getName (). '.'. $this->name;
    }
    
    
    /**
     * Gets the HTML for the configuration settings for a column of this class.
     * n.b. this method should be overridden by each Column class
     * @param array $config Config options with which to prefill fields
     * @param string $class The name of the class for which the form fields
     *        apply. N.B. The form field names must be prefixed with {class}_
     * @return string
     * @author benno, 2011-08-05
     */
    static function getConfigFormFields (array $config, $class) {
        return '';
    }
    
    
    /**
     * Gets the configuration input fields for a JS onchange method
     * @return string
     * @author benno, 2011-08-05
     */
    static function getJsOnchangeConfigFormField ($config) {
        return "    <p class=\"fake-tr\">\n".
            "        <span class=\"fake-td left-col\">JS onchange method</span>\n".
            "        <span class=\"fake-td\"><input type=\"text\" name=\"onchange\" value=\"".
                hsc ($config['onchange']). "\"></span>\n".
            "    </p>\n";
    }
    
    
    /**
     * Calls collateInput or collateMultiInputs using the relevant source
     * data from $_POST or $_FILES
     * @param mixed &$original_value Reference for storage of the original value
     * @throws DataValidationException if the input data isn't valid
     */
    function collateInputData(&$original_value) {
        if ($this instanceof FileColumn) {
            $source = $_FILES;
        } else {
            $source = $_POST;
        }
        
        if (method_exists($this, 'collateMultiInputs')) {
            return $this->collateMultiInputs($source, $original_value);
        } else {
            $input = @$source[$this->getPostSafeName()];
            return $this->collateInput($input, $original_value);
        }
    }
    
    
    /**
     * Gets the data posted from a form
     * @param mixed $data Data submission, e.g. $_POST['AwesomeField']
     * @param mixed $original_value A value into which to store the submitted
     *        data after it has been collated (even if it's invalid), so that
     *        it can be retained for use in a later submission
     * @return array DB field names and their values. Note that a single Column
     *         might actually map to multiple columns in the database.
     * @author benno, 2011-08-12
     * @throws DataValidationException if the input data isn't valid
     */
    function collateInput ($input, &$original_value) {
        $value = (string) $input;
        $original_value = $value;
        return array ($this->name => $value);
    }
    
    
    /**
     * Checks to see if collated input (from collateInput()) is empty or not
     * @param array $input Collated input (result of collateInput)
     * @return bool True if the collated input is empty
     * @author benno, 2011-08-12
     */
    function isInputEmpty (array $input) {
        $value = (string) reset ($input);
        if ($value == '') return true;
    }
    
    
    /**
     * Gets the friendly name used for this column - i.e. the key that exists
     * in the $_POST array if the column name is used as a field name on a web
     * form.
     * @return string
     * @author benno, 2011-08-12
     */
    function getPostSafeName () {
        return str_replace (' ', '_', $this->name);
    }
    
    
    /**
     * Applies config options in setup from a column creation/edit form
     * @param array $config The data posted from the form
     * @param array &$errors Any errors in the config options will add string
     *        elements to this array, describing the error(s) that occurred.
     * @author benno, 2011-08-17
     */
    function applyConfig(array $config, array &$errors) {
        // to be extended in subclasses
    }
    
    
    function getTextFilterArray () {
        throw new Exception ('This is only applicable to the StringColumn class and its subclasses');
    }
    
    
    /**
     * Identify this column in a specific context
     *
     * @param int $context The context to identify this column in:
     *     'select' (FROM x or JOIN y)
     *     'normal' (everywhere else)
     *     'row'        after fetching a row from a record set
     */
    function identify ($context) {
        if ($context == 'row') return $this->name;
        return '`'. $this->table->getName(). '`.`'. $this->name. '`';
    }
    
    
    /**
     * Gets the appropriate TH for use on a main list, perhaps with colspan
     * @return string e.g. <th>Awesome column</th>
     */
     function getTH() {
         return '<th>' . hsc($this->getEngName()) . '</th>';
     }
    
    
    /**
     * gets the appropriate TD for use on a main list, perhaps with colspan
     * 
     * @param string $data the data for this cell
     * @param string $pk the primary key identifier for the row
     * @return string
     */
     function getTD($data, $pk) {
         return '<td>' . hsc($data) . '</td>';
     }
     
     
     /**
      * Gets an array of backlinks to this column from other tables
      * in the database
      * @return array of LinkColumns
      */
     function getBacklinks() {
         $backlinks = array();
         $tables = $this->table->getDatabase()->getTables();
         foreach ($tables as $table) {
             if ($table === $this->table) continue;
             foreach ($table->getColumns() as $col) {
                 if (!($col instanceof LinkColumn)) continue;
                 if ($col->getTarget() !== $this) continue;
                 $backlinks[] = $col;
             }
         }
         return $backlinks;
     }
     
     
     /**
      * Gets info relevant to this column type to display in the column list
      * in the setup area
      */
     function getInfo() {
         return '';
     }
}
?>
