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
class UploadedImage extends UploadedFile {
    protected $size;
    
    /**
     * @author benno 2012-11-26
     * @param array $source Data from $_FILES['field_name'] or any other array
     *        with the same structure
     * 
     */
    function __construct ($source) {
        parent::__construct($source);
        $size = getimagesize($source['tmp_name']);
        $w = (int) $size[0];
        $h = (int) $size[1];
        if ($w <= 0 or $h <= 0) {
            throw new DataValidationException('Failed to determine dimensions');
        }
        $this->size = array('w' => $w, 'h' => $h);
    }
    
    function getSize() {
        return $this->size;
    }
}
