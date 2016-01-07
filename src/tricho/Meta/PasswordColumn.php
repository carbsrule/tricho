<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

use DOMDocument;
use DOMElement;
use Exception;
use DataValidationException;
use InvalidArgumentException;
use RuntimeException;

use Tricho\DataUi\Form;
use Tricho\Meta;
use Tricho\Runtime;
use Tricho\Util\HtmlDom;

/**
 * Stores meta-data about a column that stores a password
 */
class PasswordColumn extends InputColumn {
    protected $sql_collation = 'latin1_general_cs';
    protected $encryption_method;
    protected $require_existing = false;
    protected $salt = '';
    protected static $encryption_methods = array(
        'blowfish' => array('name' => 'Blowfish', 'len' => 13),
        'sha256' => array('name' => 'SHA-256', 'len' => 63),
        'sha512' => array('name' => 'SHA-512', 'len' => 106)
    );
    
    static function getAllowedSqlTypes () {
        return array('CHAR');
    }
    
    static function getDefaultSqlType () {
        return 'CHAR';
    }
    
    
    /**
     * @author benno, 2011-08-25
     */
    function toXMLNode (DOMDocument $doc) {
        $node = parent::toXMLNode ($doc);
        
        $param = HtmlDom::appendNewChild ($node, 'param');
        $param->setAttribute ('name', 'require_existing');
        $param->setAttribute ('value', Meta::toYesNo($this->require_existing));
        
        $param = HtmlDom::appendNewChild ($node, 'param');
        $param->setAttribute ('name', 'encryption');
        $param->setAttribute ('value', strtolower ($this->encryption_method));
        
        if ($this->salt) {
            $param = HtmlDom::appendNewChild($node, 'param');
            $param->setAttribute('name', 'salt');
            $param->setAttribute('value', $this->salt);
        }
        
        return $node;
    }
    
    
    /**
     * @author benno 2011-08-15
     */
    function applyXMLNode (DOMElement $node) {
        parent::applyXMLNode ($node);
        $params = $node->getElementsByTagName ('param');
        foreach ($params as $param) {
            $name = $param->getAttribute ('name');
            if ($name == 'encryption') {
                $this->setEncryptionMethod ($param->getAttribute ('value'));
            } else if ($name == 'require_existing') {
                $this->setExistingRequired (Meta::toBool($param->getAttribute ('value')));
            } else if ($name == 'salt') {
                $this->setSalt($param->getAttribute('value'));
            }
        }
    }
    
    
    /**
     * Does nothing; PasswordColumns always use latin1_general_cs.
     */
    function setCollation($collation) {
    }
    
    
    function setExistingRequired ($required) {
        $this->require_existing = (bool) $required;
    }
    
    function isExistingRequired () {
        return $this->require_existing;
    }
    
    
    /**
     * Sets a salt to use for values stored in this column.
     * If blank, the master salt is used.
     * @param string $salt
     * @author benno 2013-09-17
     */
    function setSalt($salt) {
        $this->salt = trim($salt);
    }
    
    /**
     * Gets the salt used for values stored in this column.
     * If blank, the master salt is used.
     * @return string
     * @author benno 2013-09-17
     */
    function getSalt() {
        return $this->salt;
    }
    
    
    function setEncryptionMethod($enc_method) {
        $enc_method = strtolower($enc_method);
        if (!in_array($enc_method, array_keys(self::$encryption_methods))) {
            $err = "Unknown encryption method: {$enc_method}";
            throw new InvalidArgumentException($err);
        }
        $this->encryption_method = $enc_method;
    }
    
    
    /**
     * Returns the SQL to store a salted, encrypted password in the database
     * @param string $str The password
     * @return QueryFunction
     * @author benno 2013-09-17
     */
    function encrypt($str) {
        $prefix = $this->salt;
        if ($prefix == '') $prefix = Runtime::get('master_salt');
        
        $valid_chars = 'abcdefghijklmnopqrstuvwxyz';
        $valid_chars = $valid_chars . strtoupper($valid_chars) . '1234567890./';
        switch ($this->encryption_method) {
        case 'blowfish':
            $salt = '$2y$' . generate_code(22, $valid_chars);
            break;
        case 'sha256':
            $salt = '$5$' . generate_code(16, $valid_chars);
            break;
        case 'sha512':
            $salt = '$6$' . generate_code(16, $valid_chars);
            break;
        default:
            $err = "Unknown encryption method: {$enc_method}";
            throw new InvalidArgumentException($err);
        }
        $encrypted_str = crypt($prefix . $str, $salt);
        if (strlen($encrypted_str) < 4) {
            throw new RuntimeException('crypt returned invalid value');
        }
        return $encrypted_str;
    }
    
    
    /**
     * Checks to see if a password matches its cryptographic hash
     * @param string $pass The password to check
     * @param string $hash The cryptographic hash
     * @author benno 2013-09-17
     * @return bool True if it matches, false otherwise
     */
    function matchEncrypted($pass, $hash) {
        $prefix = $this->salt;
        if ($prefix == '') $prefix = Runtime::get('master_salt');
        $encrypted_pass = crypt($prefix . $pass, $hash);
        return $encrypted_pass == $hash;
    }
    
    
    /**
     * @author benno 2011-08-30
     */
    function getMultiInputs (Form $form) {
        $inputs = array ();
        $input_name = $this->name;
        if ($this->require_existing) {
            $input = array (
                'label' => 'Current ' . hsc(lcfirst($this->engname)),
                'field' => '<input type="password" name="'. hsc ($this->name). '_existing" maxlength="200">',
                'suffix' => 'existing'
            );
            $inputs[] = $input;
            $input_name = 'New '. $input_name;
        }
        
        $mandatory_suffix = $this->getMandatorySuffix ();
        // TODO: the logic needs to be more complex
        // e.g. if the form is to change a user's password, of course this field is mandatory
        if ($form->getType() == 'edit') $mandatory_suffix = '';
        
        // (new) password
        $input = array (
            'label' => hsc ($input_name). $mandatory_suffix,
            'field' => '<input type="password" name="' . hsc($this->engname) . '" maxlength="200">',
            'suffix' => ''
        );
        $inputs[] = $input;
        
        // repeat (new) password
        $input = array (
            'label' => hsc($this->engname) . ' (confirm)' . $mandatory_suffix,
            'field' => '<input type="password" name="'. hsc ($this->name). '_confirm" maxlength="200">',
            'suffix' => 'confirm'
        );
        $inputs[] = $input;
        
        return $inputs;
    }
    
    
    function attachInputField(Form $form, $input_value = '', $primary_key = null, $field_params = array()) {
        $p = self::initInput($form);
        $label_p_params = array('class' => 'label');
        $input_p_params = array('class' => 'input');
        
        // Current password
        $post_safe_name = $this->getPostSafeName();
        $params = array(
            'type' => 'password',
            'name' => $post_safe_name . '_existing',
            'id' => $form->getFieldId(),
            'maxlength' => 200
        );
        if ($this->require_existing) {
            $password = HtmlDom::appendNewChild($p, 'input', $params);
            
            $form->incrementFieldNum();
            $p = HtmlDom::appendNewChild($p->parentNode, 'p', $label_p_params);
            $label_params = array('for' => $form->getFieldId());
            $label = HtmlDom::appendNewChild($p, 'label', $label_params);
            HtmlDom::appendNewText($label, 'New ' . $this->engname);
            $p = HtmlDom::appendNewChild($p->parentNode, 'p', $input_p_params);
        }
        
        // Password (new if an edit form)
        $params['name'] = $post_safe_name;
        $params['id'] = $form->getFieldId();
        HtmlDom::appendNewChild($p, 'input', $params);
        
        // TODO: the logic needs to be more complex
        // e.g. if the form is to change a user's password, of course this
        // field is mandatory
        $mandatory_suffix = $this->getMandatorySuffix();
        if ($form->getType() == 'edit') $mandatory_suffix = '';
        
        // Repeat new password
        $form->incrementFieldNum();
        $p = HtmlDom::appendNewChild($p->parentNode, 'p', $label_p_params);
        $label_params = array('for' => $form->getFieldId());
        $label = HtmlDom::appendNewChild($p, 'label', $label_params);
        $label_text = $this->engname . ' (confirm)';
        if ($this->require_existing) $label_text = 'New ' . $label_text;
        HtmlDom::appendNewText($label, $label_text);
        
        $p = HtmlDom::appendNewChild($p->parentNode, 'p', $input_p_params);
        $params['name'] = $post_safe_name . '_confirm';
        $params['id'] = $form->getFieldId();
        HtmlDom::appendNewChild($p, 'input', $params);
    }
    
    
    /**
     * @author benno 2011-08-30
     */
    function collateMultiInputs (array $data, &$original_value) {
        $safe_name = $this->getPostSafeName ();
        
        // TODO: move this into Form collation method
        // Needs to happen as early as possible to prevent inclusion in error emails
        Runtime::add('private_field_names', $safe_name . '_existing');
        Runtime::add('private_field_names', $safe_name);
        Runtime::add('private_field_names', $safe_name . '_confirm');
        
        // Passwords are never stored in the session
        $original_value = null;
        
        $min_length = 8;
        if (defined ('PASSWORD_MIN_LENGTH')) $min_length = PASSWORD_MIN_LENGTH;
        if ($data[$safe_name] != '' and strlen ($data[$safe_name]) < $min_length) {
            throw new DataValidationException ("Must be at least {$min_length} characters long");
        }
        if ($data[$safe_name] != '' and $data[$safe_name] != $data[$safe_name. '_confirm']) {
            throw new DataValidationException ('Passwords entered did not match');
        }
        
        if ($data[$safe_name] == '') {
            return array ();
        }
        return array (
            $this->name => $this->encrypt($data[$safe_name])
        );
    }
    
    
    function collateInput($input, &$original_value) {
        throw new Exception ('This column class uses collateMultiInputs');
    }
    
    
    /**
     * @author benno 2011-08-29
     */
    function applyConfig(array $config, array &$errors) {
        $this->setEncryptionMethod(@$config['encryption_method']);
        if (@$config['require_existing']) $this->setExistingRequired(true);
        $this->setSalt($config['salt']);
    }
    
    
    function getConfigArray () {
        $config = parent::getConfigArray ();
        $config['require_existing'] = $this->require_existing;
        $config['encryption_method'] = strtolower($this->encryption_method);
        $config['salt'] = $this->salt;
        return $config;
    }
    
    
    /**
     * @author benno 2011-08-30
     */
    static function getConfigFormFields(array $config, $class) {
        $fields = "    <p><label for=\"require_existing\"><input type=\"checkbox\" id=\"require_existing\" name=\"{$class}_require_existing\" value=\"1\"";
        if (@$config['require_existing']) $fields .= ' checked="checked"';
        $fields .= "> Must enter existing password to change it</label></p>\n";
        
        $fields .= "    <p class=\"fake-tr\">\n".
            "        <span class=\"fake-td left-col\">Encryption method</span>\n".
            "        <span class=\"fake-td\">";
        
        $method_num = 0;
        foreach (self::$encryption_methods as $method => $data) {
            if (++$method_num != 1) $fields .= "<br>\n";
            $fields .= "            <label for=\"enc_{$method}\">" .
                "<input type=\"radio\" name=\"{$class}_encryption_method\" " .
                "id=\"enc_{$method}\" value=\"{$method}\"";
            if (@$config['encryption_method'] == $method) {
                $fields .= ' checked="checked"';
            }
            $fields .= ">{$data['name']} ({$data['len']} chars)</label>";
        }
        
        $fields .= "</span>\n".
            "    </p>\n";
        
        $fields .= "    <p class=\"fake-tr\">\n".
            "        <span class=\"fake-td left-col\">Fixed salt</span>\n" .
            "        <span class=\"fake-td\">";
        $fields .= "<input type=\"text\" name=\"{$class}_salt\" value=\"" .
            hsc(@$config['salt']) . "\"></span>\n" . "    </p>\n";
        
        return $fields;
    }
    
    
    function getInfo() {
        return self::$encryption_methods[$this->encryption_method]['name'];
    }
}
?>
