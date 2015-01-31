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
    protected $step;
    protected $final;
    protected $presets = array();
    protected $modifier;
    
    /**
     * The DOMDocument created by Form::generateDoc, which can be manipulated
     * by Column::attachInputField.
     */
    protected $doc = null;
    
    /**
     * A counter for the fields generated by Form::generateDoc.
     */
    protected $field_num = 1;
    
    
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
    
    
    function getStep() {
        return $this->step;
    }
    
    
    function getDoc() {
        return $this->doc;
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
    function getItems() {
        return $this->items;
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
        
        $db = Database::parseXML();
        
        $form = $doc->documentElement;
        $this->setType($form->getAttribute('type'));
        $table = $db->get($form->getAttribute('table'));
        if (!($table instanceof Table)) {
            $err = 'No table named ' . $form->getAttribute('table');
            throw new UnexpectedValueException($err);
        }
        $this->setTable($table);
        $this->final = true;
        $step = (int) @$form->getAttribute('step');
        if ($step > 0) {
            $this->step = $step;
            if (!$form->hasAttribute('final')) $this->final = false;
        } else {
            $this->step = 1;
        }
        
        $step = @$_SESSION['forms'][$this->id]['step'];
        if ($this->step > 1 and $this->step > $step) {
            throw new FormStepException('Step(s) skipped');
        }
        
        $modifier = $form->getAttribute('modifier');
        if ($modifier != '') $this->setModifier(new $modifier());
        
        $items = $form->getElementsByTagName('items')->item(0);
        foreach ($items->childNodes as $node) {
            if ($node instanceof DOMText) continue;
            if ($node instanceof DOMComment) continue;
            $type = $node->tagName;
            if ($type == 'field') {
                if ($node->hasAttribute('label')) {
                    $label = (string) $node->getAttribute('label');
                } else {
                    $label = null;
                }
                $col_name = $node->getAttribute('name');
                $col = $table->get($col_name);
                if (!$col) throw new Exception('Unknown column: ' . $col_name);
                $item = array(
                    $col,
                    $label,
                    $node->getAttribute('value')
                );
            }
            $this->items[] = $item;
        }
        
        $presets = $form->getElementsByTagName('presets')->item(0);
        if ($presets) {
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
        }
            
        
        if ($this->modifier) {
            $this->modifier->postLoad($this);
        }
    }
    
    
    function render($values = '', $errors = '') {
        $doc = $this->generateDoc($values, $errors);
        return $doc->saveXML($doc->documentElement);
    }
    
    
    /**
     * Initialises a DOMDocument with a FORM element for storing input fields.
     * @return DOMElement The FORM element
     */
    function initDocForm() {
        $this->doc = new DOMDocument();
        $form = $this->doc->createElement('form');
        $this->doc->appendChild($form);
        return $form;
    }
    
    
    function incrementFieldNum() {
        ++$this->field_num;
    }
    function getFieldId() {
        return $this->table->getName() . '-' . $this->type . '-' .
            $this->field_num;
    }
    
    
    function generateDoc($values = '', $errors = '', $pk = null) {
        if (!is_array($values)) $values = array();
        if (!is_array($errors)) $errors = array();
        $form = $this->initDocForm();
        $doc = $form->ownerDocument;
        $inner_doc = new DOMDocument();
        $form->setAttribute('method', $this->method);
        $form->setAttribute('action', $this->action_url);
        $input = $doc->createElement('input');
        $input->setAttribute('type', 'hidden');
        $input->setAttribute('name', '_f');
        $input->setAttribute('value', $this->id);
        $form->appendChild($input);
        $id_base = $this->table->getName() . '-' . $this->type . '-';
        $this->field_num = 0;
        foreach ($this->items as $item) {
            ++$this->field_num;
            $id = $id_base . $this->field_num;
            list($col, $label, $value) = $item;
            $col_name = $col->getName();
            if (isset($values[$col_name])) $value = $values[$col_name];
            
            if ($label === null) {
                $label = $col->getEngName();
            }
            if ($label != '') {
                $p = $doc->createElement('p');
                $p->setAttribute('class', 'label');
                $form->appendChild($p);
                $label_node = $doc->createElement('label');
                $label_node->setAttribute('for', $id);
                $p->appendChild($label_node);
                $text = $doc->createTextNode($label);
                $label_node->appendChild($text);
            }
            if (isset($errors[$col_name])) {
                $p = $doc->createElement('p');
                $p->setAttribute('class', 'error');
                $form->appendChild($p);
                $text = $doc->createTextNode($errors[$col_name]);
                $p->appendChild($text);
            }
            
            // Have columns make their own DOMNodes where possible
            if (method_exists($col, 'attachInputField')) {
                if ($col instanceof FileColumn and $pk !== null) {
                    $col->attachInputField($this, $value, $pk);
                } else {
                    $col->attachInputField($this, $value);
                }
                HtmlDom::appendNewText($form, "\n");
                continue;
            }
            $inputs = $col->getMultiInputs($this, $value);
            
            $input_num = 0;
            foreach ($inputs as $input) {
                if (++$input_num != 1) {
                    $id = $id_base . $this->field_num . '-' . $input_num;
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
                
                // Need to wrap in HTML to specify UTF-8 encoding
                $input = '<html><head><meta http-equiv="Content-Type" ' .
                    'content="text/html; charset=UTF-8"></head><body>' .
                    $input . '</body></html>';
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
        
        if (!isset($_SESSION['forms'][$this->id])) {
            $_SESSION['forms'][$this->id] = array();
        }
        $session = &$_SESSION['forms'][$this->id];
        
        if ($this->step > 1 and $this->step > @$session['step']) {
            redirect($this->form_url);
        }
        
        // Session data needs to be retained, otherwise multi-step forms won't
        // work, as only the data entered on the final step will be saved in
        // the DB
        if (@count($session['values']) > 0) {
            $db_data = $source_data = $session['values'];
        } else {
            $db_data = $source_data = array();
        }
        $errors = array();
        
        if ($this->modifier) {
            $this->modifier->preValidate($this, $source_data, $db_data, $errors);
        }
        
        $file_fields = array();
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
                    $value = $col->collateInput(@$source[$col->getPostSafeName()], $input);
                }
                
                $extant_value = @$session['values'][$col->getName()];
                if ($col instanceof FileColumn) {
                    $file_fields[] = $col;
                    if ($col->isInputEmpty($value)) {
                        if ($extant_value instanceof UploadedFile) {
                            $source_data[$col->getName()] = $extant_value;
                            $db_data[$col->getName()] = $extant_value;
                            continue;
                        }
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
            $session['values'] = $source_data;
            $session['errors'] = $errors;
            $url = url_append_param($this->form_url, $this->id_field, $this->id);
            redirect($url);
        }
        
        if (!$this->final) {
            $session['values'] = $source_data;
            $session['errors'] = array();
            $session['step'] = $this->step + 1;
            $url = url_append_param($this->success_url, $this->id_field, $this->id);
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
        
        $key = ($this->type == 'edit')? $pk: $insert_id;
        foreach ($file_fields as $col) {
            $name = $col->getPostSafeName();
            if (!(@$db_data[$name] instanceof UploadedFile)) continue;
            $col->saveData($db_data[$name], $key);
        }
        
        if ($this->modifier) {
            $this->modifier->postProcess($this, $data, $insert_id, $codes);
        }
        
        redirect($this->success_url);
    }
}


/**
 * Thrown when a user tries to skip a step in a multi-form process.
 */
class FormStepException extends Exception {
}
?>
