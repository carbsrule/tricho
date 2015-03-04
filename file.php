<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Meta\Database;

require_once 'tricho.php';

$return_404 = false;

list ($table_name, $column_name, $file_id) = explode ('.', $_GET['f']);
$file_prim_key_vals = explode (',', $file_id);

$db = Database::parseXML();
$table = $db->getTableByMask ($table_name);

if (count($file_prim_key_vals) == 0) {
    $return_404 = true;
}

if ($table == null) {
    $column = null;
    // echo "Table not found<br>\n";
} else {
    $column = $table->getColumnByMask ($column_name);
}

if ($column == null) {
    $return_404 = true;
    // echo "Column not found<br>\n";
} else if (!$return_404) {
    // build query using primary key values
    $prim_key_cols = $table->getIndex ('PRIMARY KEY');
    if (count($file_prim_key_vals) == count($prim_key_cols)) {
        $q = "SELECT `". $column->getName (). "` FROM `". $table->getName ().
            "` WHERE ";
        $i = 0;
        reset($prim_key_cols);
        reset($file_prim_key_vals);
        while (list($pk_field_id, $pk_field) = each($prim_key_cols)) {
            list($var_name, $var_val) = each($file_prim_key_vals);
            if ($i++ > 0) $q .= ' AND ';
            
            $junk = '';
            $data = $pk_field->collateInput($var_val, $junk);
            if (count($data) != 1) {
                throw new LogicException('Wrong type of column for a PK');
            }
            $var_val = sql_enclose(reset($data));
            
            $q .= '`' . $pk_field->getName (). "` = {$var_val}";
        }
        // echo $q;
    } else {
        $return_404 = true;
    }
}

if (!$return_404) {
    // give doc type based on file extension
    $file_types = array (
        'png'    => 'image/png',
        'gif'    => 'image/gif',
        'jpg'    => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'doc'    => 'application/msword',
        'xls'    => 'application/vnd.ms-excel',
        'pdf'    => 'application/pdf',
        'rtf'    => 'text/rtf',
        'txt'    => 'text/plain',
        'dot'    => 'application/x-dot',
        'mp3'    => 'audio/mpeg',
        'htm'    => 'text/html',
        'html' => 'text/html',
        'flv' => 'video/x-flv'
    );
    
    $res = execq($q);
    if ($row = @fetch_assoc($res)) {
    
        $file_name = $row[$column->getName ()];
        $type = @$file_types[get_file_extension($file_name)];
        if ($type == '') $type = 'application/octet-stream';
        
        $file_loc = $column->getStorageLocation ();
        if ($file_loc != '' and $file_loc{strlen ($file_loc) - 1} != '/') {
            $file_loc .= '/';
        }
        $file_loc .= $_GET['f'];
        if (file_exists ($file_loc)) {
            if (is_readable ($file_loc)) {
                
                // hard set disposition if user agent asks specifically
                $_GET['dl'] = strtolower(@$_GET['dl']);
                if ($_GET['dl'] == 'y') {
                    $disposition = 'attachment';
                } else if ($_GET['dl'] == 'n') {
                    $disposition = 'inline';
                } else {
                    // automagically determine disposition
                    switch ($type) {
                        case 'image/png':
                        case 'image/gif':
                        case 'image/jpeg':
                            $disposition = 'inline';
                            break;
                        
                        default:
                            $disposition = 'attachment';
                    }
                }
                
                // For front-end users, cache images
                // Admins will want to see their changes instantly, so don't cache for them
                if ($column instanceof ImageColumn) {
                    list ($cache_length, $cache_scale) = determine_cache_length ($column->getParam ('cache_period'));
                    
                    if (test_admin_login (false)) {
                        $cache_expiry = time () + ($cache_length * $image_cache_scales[$cache_scale]['seconds']);
                    } else {
                        // Tell admins the image expired 1 hr ago
                        $cache_expiry = time () - 3600;
                    }
                    header ("Expires: ". gmdate ('r', $cache_expiry));
                }
                
                header ("Content-type: {$type}");
                header ("Content-Disposition: {$disposition}; filename={$file_name}");
                header ("Cache-Control: cache, must-revalidate");
                header ("Pragma: public");
                readfile ($file_loc);
                die ();
            } else {
                header ("HTTP/1.1 403 Forbidden");
                require 'head.php';
                report_error ('Access denied');
                require 'foot.php';
                die ();
            }
        } else {
            $return_404 = true;
        }
    } else {
        $return_404 = true;
    }
}

if ($return_404) {
    header ("HTTP/1.1 404 Not Found");
    require 'head.php';
    report_error ('Invalid file ID');
    require 'foot.php';
}
?>
