<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * @package meta_xml
 */

/**
 * Stores meta-data about a column that stores an email address
 * @package meta_xml
 */
class EmailColumn extends StringColumn {
    protected $validation_type = 'dns';
    
    /**
     * Creates a DOMElement that represents this column (for use in tables.xml)
     * @param DOMDocument $doc The document to which this node will belong
     * @return DOMElement
     * @author benno, 2013-04-28
     */
    function toXMLNode (DOMDocument $doc) {
        $node = parent::toXMLNode($doc);
        $node->setAttribute('validate', $this->validation_type);
        return $node;
    }
    
    
    /**
     * @author benno 2013-04-28
     */
    function applyXMLNode (DOMElement $node) {
        parent::applyXMLNode($node);
        @$this->setValidationType($node->getAttribute('validate'));
    }
    
    
    static function getAllowedSqlTypes () {
        return array('VARCHAR');
    }
    
    static function getDefaultSqlType () {
        return 'VARCHAR';
    }
    
    
    function setValidationType ($type) {
        if ($type == 'basic') {
            $this->validation_type = 'basic';
        } else {
            $this->validation_type = 'dns';
        }
    }
    
    
    /**
     * @author benno, 2013-04-28
     */
    function getConfigArray () {
        $config = parent::getConfigArray ();
        $config['validate'] = $this->validation_type;
        return $config;
    }
    
    
    /**
     * @author benno, 2013-04-28
     */
    static function getConfigFormFields(array $config, $class) {
        $fields = parent::getConfigFormFields($config, $class);
        $fields = "    <p>Validate using</p>\n";
        
        $fields .= "<label for=\"val_basic\"><input type=\"radio\" name=\"{$class}_validate\" id=\"val_basic\" value=\"basic\"";
        if ($config['validate'] == 'basic') $fields .= ' checked="checked"';
        $fields .= ">Basic checks</label><br>\n";
        
        $fields .= "<label for=\"val_dns\"><input type=\"radio\" name=\"{$class}_validate\" id=\"val_dns\" value=\"dns\"";
        if ($config['validate'] != 'basic') $fields .= ' checked="checked"';
        $fields .= ">DNS lookup</label><br>\n";
        return $fields;
    }
    
    
    /**
     * @author benno, 2013-04-28
     */
    function applyConfig (array $config) {
        @$this->setValidationType($config['validate']);
    }
    
    
    /**
     * @author benno, 2013-04-28
     */
    function collateInput ($input, &$original_value) {
        $input = trim($input);
        $original_value = $input;
        if ($input == '') {
            return array($this->name => '');
        }
        
        $user_pattern = '[_a-z0-9\'-]+(\.[_a-z0-9\'-]+)*';
        $domain_pattern = '[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,6})';
        if (!preg_match ("/^{$user_pattern}@{$domain_pattern}\$/i", $input)) {
            throw new DataValidationException('Badly formed address');
        }
        
        // Check TLD exists in local database
        list($user, $domain) = explode('@', $input);
        $dot_pos = strrpos($domain, '.');
        $tld = substr($domain, $dot_pos + 1);
        $q = "SELECT Domain FROM _tricho_tlds
            WHERE Domain = " . sql_enclose($tld);
        $res = execq($q);
        if (!$res or $res->rowCount() != 1) {
            $err = "Unrecognised top-level domain: {$tld}";
            throw new DataValidationException($err);
        }
        
        // Check that there are DNS records for the domain
        if ($this->validation_type != 'dns') {
            return array($this->name => $input);
        }
        if (!checkdnsrr($domain, 'ANY')) {
            $err = "Domain failed to resolve: {$domain}";
            throw new DataValidationException($err);
        }
        return array($this->name => $input);
    }
}
?>
