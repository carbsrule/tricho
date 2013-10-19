<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * @package main_system
 */

/**
 * Represents a form that contains multiple items (columns, headings, etc.)
 * @todo Extend with an AdminForm class that handles parent traversal etc.
 */
class Form {
    protected $id;
    protected $id_field = 'f';
    protected $method = 'post';
    protected $form_url;
    protected $action_url;
    protected $success_url;
    protected $type;
    protected $table;
    protected $items;
    protected $presets;
    protected $modifier;
    
    
    function __construct($id = '', $method = '') {
        if ($method != '') $this->setMethod($method);
        $this->items = array();
        if ($id != '') {
            $this->id = $id;
        } else {
            $this->id = generate_code(20);
        }
    }
    
    
    function getID() {
        return $this->id;
    }
    
    
    /**
     * @param string $method 'post' or 'get'
     */
    function setMethod($method) {
        $method = strtolower($method);
        if ($method != 'post' and $method != 'get') {
            $err = "Invalid method, must be post or get";
            throw new InvalidArgumentException($err);
        }
        $this->method = $method;
    }
    
    
    /**
     * @param string $field
     */
    function setIDField($field) {
        $this->id_field = (string) $field;
    }
    
    
    /**
     * @param string $form_url
     */
    function setFormURL($form_url) {
        $this->form_url = (string) $form_url;
    }
    
    /**
     * @param string $action_url
     */
    function setActionURL($action_url) {
        $this->action_url = (string) $action_url;
    }
    
    /**
     * @param string $success_url
     */
    function setSuccessURL($success_url) {
        $this->success_url = (string) $success_url;
    }
    
    
    /**
     * @return string 'add' or 'edit'
     */
    function getType() {
        return $this->type;
    }
    
    /**
     * @param string $type 'add' or 'edit'
     */
    function setType($type) {
        $type = strtolower($type);
        if ($type != 'add' and $type != 'edit') {
            $err = "Invalid type, must be add or edit";
            throw new InvalidArgumentException($err);
        }
        $this->type = $type;
    }
    
    
    function setTable(Table $table) {
        $this->table = $table;
    }
    function getTable() {
        return $this->table;
    }
    
    
    function addItem(array $item, $pos = null) {
        if ($pos === null) {
            $this->items[] = $item;
            return;
        }
        $pos = (int) $pos;
        if ($pos < 0) $pos = 0;
        
        $added = false;
        $num_nodes = count($this->items);
        $items = array();
        for ($i = 0; $i < $num_nodes; ++$i) {
            if ($i == $pos) {
                $items[] = $item;
                $added = true;
            }
            // N.B. this doesn't rely on contiguous key numbers
            $items[] = array_shift($this->items);
        }
        if (!$added) $items[] = $item;
        $this->items = $items;
    }
    
    
    function setModifier(FormModifier $modifier) {
        $this->modifier = $modifier;
    }
    
    
    function load($file_path) {
        $file_path = (string) $file_path;
        if ($file_path[0] != '/') {
            $root = tricho\Runtime::get('root_path') . 'tricho/data/';
            $file_path = $root . $file_path;
        }
        if (!@is_file($file_path) or !is_readable($file_path)) {
            throw new InvalidArgumentException('Missing or unreadable file');
        }
        $doc = new DOMDocument();
        $doc->load($file_path);
        
        $db = Database::parseXML('admin/tables.xml');
        
        $form = $doc->documentElement;
        $this->setType($form->getAttribute('type'));
        $table = $db->get($form->getAttribute('table'));
        if (!($table instanceof Table)) {
            $err = 'No table named ' . $form->getAttribute('table');
            throw new UnexpectedValueException($err);
        }
        $this->setTable($table);
        $modifier = $form->getAttribute('modifier');
        if ($modifier != '') $this->setModifier(new $modifier());
        
        $items = $form->getElementsByTagName('items')->item(0);
        foreach ($items->childNodes as $node) {
            if ($node instanceof DOMText) continue;
            if ($node instanceof DOMComment) continue;
            $type = $node->tagName;
            if ($type == 'field') {
                $item = array(
                    $table->get($node->getAttribute('name')),
                    $node->getAttribute('label'),
                    $node->getAttribute('value')
                );
            }
            $this->items[] = $item;
        }
        
        $presets = $form->getElementsByTagName('presets')->item(0);
        foreach ($presets->childNodes as $node) {
            if (!($node instanceof DOMElement)) continue;
            $type = $node->getAttribute('type');
            $value = $node->getAttribute('value');
            switch ($type) {
            case '':
            case 'string':
                $preset = new QueryFieldLiteral($value);
                break;
            
            case 'literal':
                $preset = new QueryFieldLiteral($value, false);
                break;
            
            case 'random':
                $preset = new RandomString($value);
                break;
            
            default:
                throw new Exception('No idea what to do here');
            }
            $field_name = $node->getAttribute('field');
            $this->presets[$field_name] = $preset;
        }
        
        if ($this->modifier) {
            $this->modifier->postLoad($this);
        }
    }
    
    
    function render($values = '', $errors = '') {
        $doc = $this->generateDoc($values, $errors);
        return $doc->saveXML($doc->documentElement);
    }
    
    
    function generateDoc($values = '', $errors = '') {
        if (!is_array($values)) $values = array();
        if (!is_array($errors)) $errors = array();
        $doc = new DOMDocument();
        $inner_doc = new DOMDocument();
        $form = $doc->createElement('form');
        $doc->appendChild($form);
        $form->setAttribute('method', $this->method);
        $form->setAttribute('action', $this->action_url);
        $input = $doc->createElement('input');
        $input->setAttribute('type', 'hidden');
        $input->setAttribute('name', '_f');
        $input->setAttribute('value', $this->id);
        $form->appendChild($input);
        $id_base = $this->table->getName() . '-' . $this->type . '-';
        $field_num = 0;
        foreach ($this->items as $item) {
            ++$field_num;
            $id = $id_base . $field_num;
            list($col, $label, $value) = $item;
            $col_name = $col->getName();
            if (isset($values[$col_name])) $value = $values[$col_name];
            if ($label != '') $col->setEngName($label);
            
            $p = $doc->createElement('p');
            $p->setAttribute('class', 'label');
            $form->appendChild($p);
            $label = $doc->createElement('label');
            $label->setAttribute('for', $id);
            $p->appendChild($label);
            $text = $doc->createTextNode($col->getEngName());
            $label->appendChild($text);
            
            if (isset($errors[$col_name])) {
                $p = $doc->createElement('p');
                $p->setAttribute('class', 'error');
                $form->appendChild($p);
                $text = $doc->createTextNode($errors[$col_name]);
                $p->appendChild($text);
            }
            
            // TODO: this is nasty. If the end goal is to use DOM, then
            // columns need their own methods to create DOMNodes
            if (method_exists($col, 'getMultiInputs')) {
                $inputs = $col->getMultiInputs($this, $value);
            } else {
                $inputs = array(array(
                    'label' => 'N/A',
                    'field' => $col->getInputField($this, $value),
                    'suffix' => ''
                ));
            }
            
            $field_num = 0;
            foreach ($inputs as $input) {
                if (++$field_num != 1) {
                    $id = $id_base . $field_num . '-' . $field_num;
                    $p = $doc->createElement('p');
                    $p->setAttribute('class', 'label');
                    $form->appendChild($p);
                    $label = $doc->createElement('label');
                    $label->setAttribute('for', $id);
                    $p->appendChild($label);
                    
                    // nasty hack :(
                    // TODO: remove this once admin add/edit pages use Forms
                    $input_label = $input['label'];
                    if ($col instanceof PasswordColumn) {
                        $lt_pos = strpos($input_label, '<');
                        if ($lt_pos > 0) {
                            $input_label = substr($input_label, 0, $lt_pos);
                            $input_label = rtrim($input_label);
                        }
                    }
                    
                    $text = $doc->createTextNode($input_label);
                    $label->appendChild($text);
                }
                $input = $input['field'];
                
                $inner_doc->loadHTML($input);
                $node = $inner_doc->getElementsByTagName('body')->item(0)
                    ->firstChild;
                $node = $doc->importNode($node, true);
                $node->setAttribute('id', $id);
                if (strcasecmp($node->tagName, 'textarea') == 0) {
                    $blank = $doc->createTextNode('');
                    $node->appendChild($blank);
                }
                $p = $doc->createElement('p');
                $p->setAttribute('class', 'input');
                $form->appendChild($p);
                $p->appendChild($node);
            }
        }
        $p = $doc->createElement('p');
        $p->setAttribute('class', 'submit');
        $form->appendChild($p);
        
        $submit = $doc->createElement('input');
        $submit->setAttribute('type', 'submit');
        $submit->setAttribute('name', '_submit');
        $submit->setAttribute('value', 'Submit');
        $p->appendChild($submit);
        
        $cancel = $doc->createElement('input');
        $cancel->setAttribute('type', 'submit');
        $cancel->setAttribute('name', '_cancel');
        $cancel->setAttribute('value', 'Cancel');
        $p->appendChild($cancel);
        
        if ($this->modifier) {
            $this->modifier->postGenerate($this, $doc);
        }
        
        return $doc;
    }
    
    
    /**
     * @param mixed $pk Primary Key value(s). Only applies for edit forms.
     */
    function process($pk = 0) {
        if ($this->form_url == '' or $this->success_url == '') {
            throw new Exception('Invalid configuration');
        }
        
        $source_data = array();
        $db_data = array();
        $errors = array();
        
        if ($this->modifier) {
            $this->modifier->preValidate($this, $source_data, $db_data, $errors);
        }
        
        foreach ($this->items as $item) {
            list($col, $label, $value) = $item;
            if ($label != '') $col->setEngName($label);
            if ($col instanceof FileColumn) {
                $source = $_FILES;
            } else {
                $source = $_POST;
            }
            
            // No need to ask for the current password when adding new record
            if ($col instanceof PasswordColumn and $this->type == 'add') {
                $col->setExistingRequired(false);
            }
            
            $input = null;
            try {
                if ($col instanceof FileColumn) {
                    $source = $_FILES;
                } else {
                    $source = $_POST;
                }
                
                // TODO: replace $col->getMandatory () with a value for each form
                // e.g. new fields added long after a table's creation may be mandatory
                // for new records (add), but not for existing records (edit)
                if (method_exists($col, 'collateMultiInputs')) {
                    $value = $col->collateMultiInputs($source, $input);
                } else {
                    $value = $col->collateInput($source[$col->getPostSafeName()], $input);
                }
                
                $extant_value = @$_SESSION['forms'][$this->id]['values'][$col->getName()];
                if ($col instanceOf FileColumn and $col->isInputEmpty($value)) {
                    if ($extant_value instanceof UploadedFile) {
                        $source_data[$col->getName()] = $extant_value;
                        $db_data[$col->getName()] = $extant_value;
                        continue;
                    }
                }
                
                if ($col->isMandatory() and $col->isInputEmpty($value)) {
                    $errors[$col->getName()] = 'Required field';
                } else {
                    $db_data = array_merge($db_data, $value);
                }
            } catch (DataValidationException $ex) {
                $errors[$col->getName()] = $ex->getMessage();
            }
            $source_data[$col->getName()] = $input;
        }
        
        if ($this->modifier) {
            $this->modifier->postValidate($this, $source_data, $db_data, $errors);
        }
        
        if (count($errors) > 0) {
            $_SESSION['forms'][$this->id]['values'] = $source_data;
            $_SESSION['forms'][$this->id]['errors'] = $errors;
            $url = $this->form_url;
            if (strpos($url, '?') !== false) {
                $url .= '&';
            } else {
                $url .= '?';
            }
            $url .= $this->id_field . '=' . $this->id;
            redirect($url);
        }
        
        $data = $db_data;
        
        $codes = array();
        foreach ($this->presets as $field => $preset) {
            if ($preset instanceof RandomString) {
                $code = $preset->generate();
                $codes[$field] = $code;
                $preset = new QueryFieldLiteral($code);
            }
            $db_data[$field] = $preset;
        }
        
        if ($this->type == 'add') {
            $q = new InsertQuery($this->table, $db_data);
        } else {
            $q = new UpdateQuery($this->table, $db_data, $pk);
        }
        $q->exec();
        
        unset($_SESSION['forms'][$this->id]);
        
        $insert_id = 0;
        if ($this->type == 'add') {
            $conn = ConnManager::get_active();
            $insert_id = $conn->get_pdo()->lastInsertId();
        }
        
        if ($this->modifier) {
            $this->modifier->postProcess($this, $data, $insert_id, $codes);
        }
        
        redirect($this->success_url);
    }
}
?>
