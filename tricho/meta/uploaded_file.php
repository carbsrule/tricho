<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * Stores data about an uploaded file.
 * 
 */
class UploadedFile {
    protected $file_name;
    protected $mime_type;
    protected $data;
    
    /**
     * @author benno 2012-11-26
     * @param array $source Data from $_FILES['field_name'] or any other array
     *        with the same structure
     * 
     */
    function __construct ($source) {
        $data = file_get_contents($source['tmp_name']);
        if ($data === false) {
            throw new DataValidationException('Failed to read file');
        }
        $this->file_name = $source['name'];
        $this->mime_type = $source['type'];
        $this->data = $data;
    }
    
    function __toString () {
        return $this->file_name;
    }
    
    function getName () {
        return $this->file_name;
    }
    
    function getType () {
        return $this->mime_type;
    }
    
    function getData () {
        return $this->data;
    }
    
    function __printHuman() {
        return $this->file_name . ' (' . $this->mime_type . '), ' .
            strlen($this->data) . ' byte(s)';
    }
}
?>
