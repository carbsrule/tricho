<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * @package custom_exceptions
 */


/**
 * Thrown when file uploads fail
 * @package custom_exceptions
 */
class UploadFailedException extends Exception {
    
    function __construct ($error_type, Column $col) {
        
        if (is_int ($error_type)) {
            switch ($error_type) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $this->message = 'File ('. $col->getName ().
                        ') failed to upload - larger than maximum allowed file size';
                    break;
                case UPLOAD_ERR_PARTIAL:
                case UPLOAD_ERR_NO_FILE:
                    $this->message = 'File ('. $col->getName (). ') failed to upload - please try again';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                case UPLOAD_ERR_CANT_WRITE:
                    $this->message = 'File ('. $col->getName ().
                        ') failed to upload - file system or permissions error';
                    break;
                default:
                    $this->message = 'File ('. $col->getName (). ') failed to upload - Unknown error';
                    break;
            }
        } else {
            $this->message = $error_type;
        }
        
    }
    
}


/**
 * Thrown when images cannot be resized
 * @package custom_exceptions
 */
class ImageResizeException extends Exception {
    
    function __construct (Column $col, $message) {
        $this->message = 'The image <i>'. $col->getName (). '</i> failed to save - ' . $message;
    }
    
}


/**
 * Thrown when images cannot be resized
 * @package custom_exceptions
 */
class InvalidSizeException extends Exception {
    
    function __construct (Column $col, $actual_width, $actual_height) {
        $params = $col->getParams();
        list ($width, $height) = explode ('x', $params['size']);
        
        $this->message    = 'The image <i>'. $col->getName (). '</i> failed to save - it is the wrong size. ';
        
        if ($width == 0) {
            $this->message .= 'The required height is exactly '. $height. ' pixels. ';
        } else if ($height == 0) {
            $this->message .= 'The required width is exactly '. $width. ' pixels. ';
        } else {
            $this->message .= 'The required dimensions are exactly '. $width. '&times;'. $height. ' pixels. ';
        }
        
        $this->message .= 'The image provided was '. $actual_width. '&times;'. $actual_height. ' pixels.';
    }
    
}


/**
 * Thrown when a file can't be opened for writing
 * @author benno, 2011-08-09
 */
class FileNotWriteableException extends Exception {
    function __construct ($file_loc) {
        $this->message = "Unable to write to file {$file_loc}";
    }
}


/**
 * Thrown when a Column is defined with an invalid configuration.
 * This is typically thrown when loading or saving XML.
 * @author benno, 2011-08-09
 */
class InvalidColumnConfigException extends Exception {
}


/**
 * Thrown when invalid data is submitted for a column
 * @author benno, 2012-02-11
 */
class DataValidationException extends Exception {
    function __construct ($message, $original_data = null) {
        $this->message = $message;
        $this->data = $original_data;
    }
}


/**
 * Thrown when a database query fails
 * @author benno, 2013-06-25
 */
class QueryException extends Exception {
    /**
     * Store the error code with the error text
     */
    function setCode($code) {
        $code = (int) $code;
        if ($code <= 0) return;
        if ($this->message != '') {
            $this->message = "[{$code}] {$this->message}";
        } else {
            $this->message = "Error {$code}";
        }
    }
    
    
    function addError($extra_error) {
        $this->message = "{$this->message}\n" .
            "In addition, the following error occurred:\n{$extra_error}";
    }
}


/**
 * Column doesn't exist in metadata
 */
class UnknownColumnException extends Exception {
    
    /**
     * @param string $column_name The name of the missing column
     */
    function __construct($column_name) {
        $this->message = $column_name;
    }
}
