<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * @package functions
 * @subpackage base
 */


// Check if PHP is misconfigured:
// 1) register globals must be turned off
if (ini_get('register_globals')) {
    header('Location: '. ROOT_PATH_WEB. 'system_error.php?err=glob');
    die();
}
// 2) magic_quotes_* must be turned off
if (get_magic_quotes_gpc() or get_magic_quotes_runtime()) {
    header('Location: '. ROOT_PATH_WEB. 'system_error.php?err=mq');
    die();
}

function tricho_exception_handler(Exception $ex) {
    if (!\tricho\Runtime::get('live')) {
        @header('Content-type: text/html');
        echo '<pre>';
        $trace = $ex->getTrace();
        $ex_class = get_class($ex);
        
        // Show where error was raised, not where ErrorException was thrown  
        if ($ex instanceof ErrorException) {
            if (isset($trace[0]['file'])) {
                $file = $trace[0]['file'];
                $line = $trace[0]['line'];
            } else {
                list($file, $line) = array_slice($trace[0]['args'], 2);
            }
            echo '<strong>', $ex_class, '</strong> in ', $file,
                ':', $line, "<br><i>", $ex->getMessage(), "</i><br>\n";
            die();
        }
        echo '<strong>', $ex_class, '</strong> in ', $ex->getFile(),
            ':', $ex->getLine (), "<br><i>", $ex->getMessage(), "</i><br>\n";
        foreach ($trace as &$step) {
            foreach ($step['args'] as $key => &$arg) {
                if (is_object($arg)) {
                    $step['args'][$key] = 'Object:' . get_class($arg);
                    continue;
                }
                
                if (!is_array($arg)) continue;
                foreach ($arg as $arg_key => $arg_part) {
                    if (is_object($arg_part)) {
                        $arg[$arg_key] = 'Object:' . get_class($arg_part);
                    } else if (is_array($arg_part)) {
                        $count = count($arg_part);
                        $placeholders = [];
                        for (; $count > 0; --$count) {
                            $placeholders[] = '...';
                        }
                        $keys = array_keys($arg_part);
                        $arg[$arg_key] = array_combine($keys, $placeholders);
                    }
                }
            }
        }
        echo print_r($trace, true);
        die();
    }
    if ($ex instanceof QueryException) {
        $err = 'q';
    } else {
        $err = 'sys';
    }
    header('Location: ' . ROOT_PATH_WEB . 'system_error.php?err=' . $err);
    throw $ex;
}
set_exception_handler('tricho_exception_handler');

function tricho_error_handler($code, $str, $file, $line) {
    if (error_reporting() == 0) return false;
    $type = '';
    switch ($code) {
    case E_USER_ERROR:
        $type = 'User error';
        break;
    
    case E_USER_WARNING:
        $type = 'User warning';
        break;
    
    case E_USER_NOTICE:
        $type = 'User notice';
        break;
    
    case E_ERROR:
        $type = 'Fatal error';
        break;
    
    case E_WARNING:
        $type = 'Warning';
        break;
    
    case E_NOTICE:
        $type = 'Notice';
        break;
    }
    if ($type == '') $type = "[{$code}] Unknown error";
    
    throw new ErrorException("{$type}: {$str}");
}
set_error_handler('tricho_error_handler');


/**
 * Masks private data in the fields named in the private_field_names Runtime
 * array, e.g. so that the private data will not be revealed in error e-mails
 *
 * @param array $user_data POST or SESSION data
 * @return array The resultant array with masked private data
 */
function mask_private_data($user_data) {
    foreach ($user_data as $key => $value) {
        if (is_array($value)) {
            $user_data[$key] = mask_private_data($value);
        } else {
            $short_key = str_replace ('_', '', $key);
            foreach (tricho\Runtime::get('private_field_names') as $field) {
                $field_name = str_replace('_', '', $field);
                if (strcasecmp($short_key, $field_name) == 0) {
                    $user_data[$key] = '???';
                    break;
                }
            }
        }
    }
    return $user_data;
}


/**
 * E-mails developers when something goes horribly wrong with the site
 * 
 * @param string $info The error to send
 */
function email_error ($info) {
    $site_error_emails = preg_split ('/,\s*/', SITE_EMAILS_ERROR);
    
    $message = $info. "\n\n";
    
    // Print SESSION data in the e-mail
    $user_session_data = mask_private_data ($_SESSION);
    $message .= "SESSION data: \n";
    $message .= print_r ($user_session_data, true). "\n\n";
    
    // Print POST data in the e-mail
    if (count ($_POST) > 0) {
        $user_post_data = mask_private_data ($_POST);
        $message .= "POST data: \n";
        $message .= print_r ($user_post_data, true). "\n\n";
    }
    
    // Get a backtrace and convert the backtrace to a string
    $backtrace = debug_backtrace ();
    
    //echo "<pre>Backtrace\n", print_r ($backtrace, true), "</pre>\n";
    
    $line = $backtrace[0]['line'];
    $file = $backtrace[0]['file'];
    // Remove the email_error call from backtrace
    array_shift ($backtrace);
    
    // If the error was produced by a call to execq, the error is really where execq
    // was called, not the code inside execq
    if ($backtrace[0]['function'] == 'execq') {
        $line = $backtrace[0]['line'];
        $file = $backtrace[0]['file'];
        array_shift ($backtrace);
    }
    
    
    $backtrace = create_backtrace_string ($backtrace);
    
    // Scripts run in a shell-type environment
    if ($_SERVER['SERVER_NAME'] == '') {
        
        $uname = posix_uname ();
        $message .= "This error occurred on {$uname['nodename']}, on line {$line} of\n{$file}";
        if ($_SERVER['PHP_SELF'] != $file) {
            $message .= "\nwhich was called by\n". getcwd (). '/'. $_SERVER['PHP_SELF'];
        }
        
    // Scripts run from the web
    } else {
        
        // Determine the full url of the script that called the file
        $proto_host = get_proto_host ();
        $full_url = $proto_host. $_SERVER['SCRIPT_NAME'].
            ($_SERVER['QUERY_STRING'] != ''? '?'. $_SERVER['QUERY_STRING']: '');
        
        // Output the message
        $message .= "This error occurred on line {$line} of";
        if ($_SERVER['SCRIPT_NAME'] != $file) {
            $message .= ' '. str_replace (ROOT_PATH_FILE, ROOT_PATH_WEB, $file). ', called by';
        }
        $message .= "\n{$full_url}";
        if (substr ($_SERVER['REQUEST_URI'], 0, strlen ($_SERVER['SCRIPT_NAME'])) != $_SERVER['SCRIPT_NAME']) {
            $message .= "\nwhich was accessed via:\n". $proto_host. $_SERVER['REQUEST_URI'];
        }
        
    }
    
    if ($backtrace) {
        $message .= "\n\nBACKTRACE:\n{$backtrace}";
    }
    
    $message .= "\n\n". get_email_footer_info ();
    
    $site_name = tricho\Runtime::get('site_name');
    foreach ($site_error_emails as $admin) {
        mail($admin, "Error on {$site_name}", $message, 'From: ' . SITE_EMAIL);
    }
}

/**
 * E-mails administrators
 * 
 * @param string $subject The subject to include in the e-mail
 * @param string $message The message to send in the e-mail
 * @param string $from The address from which to send the e-mail
 * @return bool True if at least one e-mail was recorded by PHP as sent
 */
function email_admins ($subject, $message, $from = '') {
    
    if ($from == '') $from = SITE_EMAIL;
    $sent = 0;
    $site_administrators = preg_split ('/,\s*/', SITE_EMAILS_ADMIN);
    foreach ($site_administrators as &$admin) {
        if (mail ($admin, $subject, $message, "From: {$from}")) $sent++;
    }
    if ($sent > 0) {
        return true;
    } else {
        return false;
    }
}


/**
 * Converts a backtrace (created using the debug_backtrace() function) into a string
 *
 * @param array $backtrace The backtrace
 * @return string A string representing the backtrace
 */
function create_backtrace_string (array $backtrace) {
    
    $fields = array('file', 'line');
    $output = '';
    $first_line = true;
    foreach ($backtrace as $entry) {
        foreach ($fields as $field) {
            if (!isset($entry[$field])) $entry[$field] = '';
        }
        $entry['file'] = str_replace (ROOT_PATH_FILE, ROOT_PATH_WEB, $entry['file']);
        
        if (!$first_line) {
            $output .= "\n\n";
        } else {
            $first_line = false;
        }
        
        $output .= "File:            {$entry['file']} (Line: {$entry['line']})";
        
        if ($entry['function']) {
            if ($entry['function'] == 'execq') $entry['args'] = null;
            
            $output .= "\nFunction:    ";
            if (@$entry['class']) {
                $output .= $entry['class']. $entry['type'];
            }
            $output .= $entry['function'];
            
            if (@count ($entry['args']) > 0) {
                foreach ($entry['args'] as $num => $arg) {
                    $entry['args'][$num] = backtrace_arg_to_string ($arg);
                }
                
                $args = implode (', ', $entry['args']);
                $output .= "\nArguments: {$args}";
            }
        }
    }
    
    return $output;
}


/**
 * Converts a function argument into a readable string
 * 
 * @author benno, 2009-05-16
 * @param mixed the argument passed in the function call referenced by the backtrace
 * 
 * @return string
 */
function backtrace_arg_to_string ($arg) {
    if (is_string ($arg)) {
        $arg = str_replace ("\n", '\n', $arg);
        $arg = str_replace ("\r", '\r', $arg);
        $arg = str_replace ("\t", '\t', $arg);
        $arg = str_replace ("\v", '\v', $arg);
        $arg = str_replace ("\f", '\f', $arg);
        return "'{$arg}'";
    } else if (is_object ($arg)) {
        return cast_to_string ($arg);
    } else if (is_array ($arg)) {
        
        // determine if array uses auto-numeric keys
        $expected_numeric_key = 0;
        $uses_auto_keys = true;
        foreach ($arg as $key => $val) {
            $expected_key = (string) $expected_numeric_key;
            if ($expected_key !== (string) $key) {
                $uses_auto_keys = false;
                break;
            }
            ++$expected_numeric_key;
        }
        
        $return = 'Array (';
        $key_num = 0;
        foreach ($arg as $key => $val) {
            if (++$key_num != 1) $return .= ', ';
            
            // if not using auto-numeric keys, display the key for each element, string quoted if necessary
            if (!$uses_auto_keys) {
                if (preg_match ('/^-?[0-9]+(\.[0-9]+)?$/', $key)) {
                    $return .= "{$key} => ";
                } else {
                    $return .= "'{$key}' => ";
                }
            }
            $return .= backtrace_arg_to_string ($val);
        }
        $return .= ')';
        return $return;
    } else if (is_null ($arg)) {
        return 'NULL';
    } else if (is_bool ($arg)) {
        return ($arg? 'TRUE': 'FALSE');
    } else {
        return $arg;
    }
}


/**
 * Gets text to include at the bottom of an e-mail including installed software versions, hostname etc.
 * 
 * @author benno, 2009-06-02
 */
function get_email_footer_info () {
    $footer = "SOFTWARE VERSIONS:\n".
        'PHP: '. phpversion (). ' ('. php_sapi_name (). ')';
    $pdo = ConnManager::get_active()->get_pdo();
    if ($mysql_version = @$pdo->getAttribute(PDO::ATTR_SERVER_VERSION)) {
        $footer .= "\nMySQL: {$mysql_version}";
    }
    $footer .= "\nTricho: " . TRICHO_VERSION;
    if (defined ('CMS_VERSION')) {
        $footer .= "\nCMS: ". CMS_VERSION;
    }
    
    if ($_SERVER['REMOTE_ADDR'] != '') {
        $hostname = gethostbyaddr ($_SERVER['REMOTE_ADDR']);
        if ($hostname != $_SERVER['REMOTE_ADDR']) $hostname .= " ({$_SERVER['REMOTE_ADDR']})";
        $footer .= "\n\nUSER DETAILS:\n".
            "Host: {$hostname}\n".
            "Referer: {$_SERVER['HTTP_REFERER']}\n".
            "User agent: {$_SERVER['HTTP_USER_AGENT']}";
    }
    
    return $footer;
}


/**
 * Displays session information (errors, confirmation messages, and warnings).
 * 
 * @param string $type err (error), msg (confirmation message), or warn (warning).
 * @param mixed $partitions a string or array of strings, each being the partition within the site
 *     e.g. setup, admin, or user
 * @param bool $return Whether to return or print the session info. Returns info on true;
 *     prints on false
 * @return mixed Returns a string if $return is true, otherwise no value is returned
 */
function report_session_info ($type, $partitions = array ('user', ''), $return = false) {
    
    $out = '';

    $type = strtolower ($type);
    
    $partitions = (array) $partitions;
    
    if (!@in_array('_tricho', $partitions)) {
        array_unshift($partitions, '_tricho');
    }
    
    switch ($type) {
        case 'err':
            $class = 'error';
            break;
        
        case 'msg':
            $class = 'confirmation';
            break;
        
        case 'warn':
            $class = 'warning';
            break;
        
        default:
            $class = 'unknown_message';
            break;
    }
    
    foreach ($partitions as $partition) {
        if ($partition == '') {
            $info =& $_SESSION[$type];
            if ((is_array ($info) and @count ($info) == 0) or $info == '') {
                $info =& $_SESSION[$type];
            }
        } else {
            $info =& $_SESSION[$partition][$type];
        }
        
        if (@is_array ($info)) {
            if (count ($info) != 0) {
                if (count ($info) == 1) {
                    $out .= "<p class=\"{$class}\">{$info[0]}</p>\n";
                
                } else {
                    $out .= "<p class=\"{$class}\">";
                    $initial = true;
                    foreach ($info as $val) {
                        if ($initial) {
                            $initial = false;
                        } else {
                            $out .= "<br>";
                        }
                        $out .= "{$val}\n";
                    }
                    $out .= "</p>\n";
                }
            }
            
        } else if ($info != '') {
            $out .= "<p class=\"{$class}\">{$info}</p>\n";
        }
        
        if ($partition == '') {
            unset ($_SESSION[$type]);
            unset ($_SESSION[$type]);
        } else {
            unset ($_SESSION[$partition][$type]);
        }
    }

    if ($return) {
        return $out;
    }

    echo $out;
}

/**
 * Displays any confirmation messages, warnings, and errors stored in the session (in that order),
 * then clears them. Note that this function is just a wrapper for {@link report_session_info},
 * which is called 3 times (once for each type of message).
 * @param mixed $partitions a string or array of strings, each being the partition within the site
 *     e.g. setup, admin, or user
 * @param bool $return Whether to return session response string or print it. Returns info on true;
 *     prints on false
 * @return mixed Returns a string if $return is true, otherwise no value is returned
 */
function check_session_response ($partitions = array ('user', ''), $return = false) {
    $out = report_session_info ('msg', $partitions, $return);
    $out .= report_session_info ('warn', $partitions, $return);
    $out .= report_session_info ('err', $partitions, $return);

    if ($return) {
        return $out;
    }

    echo $out;
}

/**
 * Report an error that is in a non-session variable.
 *
 * This used to provide a uniform visualisation of errors.
 * 
 * @param string $err The error to display
 * @param bool $return true to return the error as a string
 * @return mixed A string if the $return argument is true
 */
function report_error ($err, $return = false) {
    $error = "<p class=\"error\">{$err}</p>\n";
    if ($return) return $error;
    echo $error;
}


/**
 * Replaces newlines with HTML <<br>>s, so that stored data can easily be viewed via HTML output
 * @author benno, 2009-07-27 added XHTML parameter
 * @param string $string The string in which to add <<br>>s
 * @param bool $xhtml Whether the BRs should be XHTML formatted or not
 *             (if null, uses the 'xhtml' Runtime setting)
 * @return string The replacement string
 */
function add_br($string, $xhtml = null) {
    if ($xhtml === null) $xhtml = tricho\Runtime::get('xhtml');
    $br = ($xhtml? "<br />\n": "<br>\n");
    $string = str_replace(array("\r\n", "\r"), "\n", $string);
    return str_replace("\n", $br, $string);
}

/**
 * Remove newline and carriage return characters (ASCII 10 and 13).
 * 
 * For storing form data in a CSV/TSV file, for example.
 * 
 * @param string $string The string from which to remove nl and cr
 * @return string The replacement string
 */
function rem_nl ($string) {
    $string = str_replace ("\r\n", "\n", $string);
    $string = str_replace ("\r", "\n", $string);
    $string = str_replace ("\n", ' ', $string);
    return $string;
}

/**
 * Removes HTML <<br>>s and replaces them with newlines.
 * 
 * This is primarily used so that "<<br>>" doesn't appear in text forms
 * @author benno, 2009-07-27 added ability to remove XHTML formatted BRs
 * @param string $string The string in which to replace <<br>>\n with \n
 * @return string The replacement string
 */
function rem_br ($string) {
    return preg_replace ('#<br\s*/?\s*>(\r\n|\r|\n)?#', "\n", $string);
}



/** 
 * Makes a new image according to maximum vertical and/or horizontal dimensions.
 * 
 * This function requires GD 2.0.l or greater.
 * 
 * @param string $old_loc Location of original image.
 * @param string $new_loc Location in which to store the new image.
 * @param string $box_width Maximum horizontal dimension of new image.
 * @param string $box_height Maximum vertical dimension of new image.
 * @param string $out_format Format to use for new image.
 * By default, the image format of the new image will be the same as the old one.
 * @param int $jpeg_quality JPEG quality level to use (1-100). This parameter only applies when creating JPEGs.
 * @param bool $crop True if you want the image to be cropped to match the constraints (defaults to false)
 * @return bool True if the image was created successfully, false otherwise.
 */
function make_sized_image ($old_loc, $new_loc, $box_width = 0, $box_height = 0, $out_format = '', $jpeg_quality = DEFAULT_JPEG_QUALITY, $crop = false) {
    $debug = false;
    
    
    if ($debug) echo "Getting image size... ";
    $old_size = getimagesize ($old_loc);

    $old_width    = $old_size[0];
    $old_height    = $old_size[1];
    
    if ($debug) echo "{$old_width} &times; {$old_height}<br>\n";
    
    
    // try to get the old image, or throw an exception
    if ($debug) echo "Getting old image data... ";
    if ($old_img = @imagecreatefromjpeg ($old_loc)) {
        if ($debug) echo "JPEG<br>\n";
        $format = 'jpg';
    } else if ($old_img = @imagecreatefrompng ($old_loc)) {
        if ($debug) echo "PNG<br>\n";
        $format = 'png';
    } else if ($old_img = @imagecreatefromgif ($old_loc)) {
        if ($debug) echo "GIF<br>\n";
        $format = 'gif';
    } else {
        $gd_info = gd_info();
        $formats = array();
        if ($gd_info['PNG Support']) $formats[] = 'PNG';
        if ($gd_info['JPG Support']) $formats[] = 'JPG';
        if ($gd_info['GIF Create Support']) $formats[] = 'GIF';
        throw new exception ("Unrecognised image type; Supported types are " . implode(', ', $formats));
    }
    
    // see if the output format was overwritten, or use the input format
    $old_format = $format;
    if ($out_format != '') {
        $format = $out_format;
    }
    
    // if the size and format do not need to change, just copy the file
    if (($box_width == 0 or $old_width <= $box_width) and ($box_height == 0 or $old_height <= $box_height) and $format == $old_format) {
        @imagedestroy ($old_img);
        
        $result = @copy ($old_loc, $new_loc);
        if ($result) {
            apply_file_security ($new_loc);
        } else {
            throw new exception ("Unable to copy file {$old_loc} to {$new_loc}; check permissions");
        }
        
        
    } else {
        // Need to resize the file or change its format
        
        if ($debug) echo "Old image size: $old_width &times; $old_height<br>\n";
        if ($debug) echo "Parameters given: $box_width &times; $box_height<br>\n";
        
        // work out maximum dimension
        if ($box_width > 0) {
            $ratio_horiz = $box_width / $old_width;
        } else {
            $ratio_horiz = 1;
        }
        if ($box_height > 0) {
            $ratio_vert = $box_height / $old_height;
        } else {
            $ratio_vert = 1;
        }
        
        $chop_width = $old_width;
        $chop_height = $old_height;
        $vert_offset = 0;
        $horiz_offset = 0;
        // if not cropping, chop the image to fix in the box
        if ($crop) {
            
            if ($ratio_vert < $ratio_horiz) {
                // image may be too high and need vertical chop
                if ($debug) echo "Detected vertical image<br>\n";
                if ($box_height < $old_height) {
                    if ($debug) echo "Chopping<br>\n";
                    // define new size by resizing with horizontal ratio
                    $new_width = $old_width * $ratio_horiz;
                    $new_height = $old_height * $ratio_horiz;
                    // redefine new height by chopping
                    if ($new_height > $box_height) {
                        $chop_height = round ($box_height / $ratio_horiz);
                        $new_height = $box_height;
                        $vert_offset = floor (($old_height - $chop_height) / 2.0);
                    }
                    
                } else {
                    if ($debug) echo "Not chopping<br>\n";
                    // no need to chop, already smaller than or equal to the maximum vertical dimension
                    // note that we still need this when converting image type
                    $new_width = $old_width;
                    $new_height = $old_height;
                }
            } else {
                // image may be too wide and need horizontal chop
                if ($debug) echo "Detected horizontal image<br>\n";
                if ($box_width < $old_width) {
                    if ($debug) echo "Chopping<br>\n";
                    // define new size by resizing with vertical ratio
                    $new_height = $old_height * $ratio_vert;
                    $new_width = $old_width * $ratio_vert;
                    // redefine new width by chopping
                    if ($new_width > $box_width) {
                        $chop_width = round ($box_width / $ratio_vert);
                        $new_width = $box_width;
                        $horiz_offset = floor (($old_width - $chop_width) / 2.0);
                    }
                } else {
                    // no need to resize, already smaller than or equal to the maximum horizontal dimension
                    // note that we still need this when converting image type
                    $new_width = $old_width;
                    $new_height = $old_height;
                }
            }
            
        // if not cropping, resize image to fix in the box
        } else {
            
            if ($ratio_vert < $ratio_horiz) {
                // vertically resize if necessary
                if ($debug) echo "Detected vertical image<br>\n";
                if ($box_height < $old_height) {
                    if ($debug) echo "Resizing<br>\n";
                    // redefine new width, resized using vertical ratio
                    $new_width = $old_width * $ratio_vert;
                    // if a max height was specified, use it, otherwise proportionally resize height
                    if ($box_height == 0) {
                        $new_height = $old_height * $ratio_vert;
                    } else {
                        $new_height = $box_height;
                    }
                } else {
                    if ($debug) echo "Not resizing<br>\n";
                    // no need to resize, already smaller than or equal to the maximum vertical dimension
                    // note that we still need this when converting image type
                    $new_width = $old_width;
                    $new_height = $old_height;
                }
            } else {
                // horizontally resize if necessary
                if ($debug) echo "Detected horizontal image<br>\n";
                if ($box_width < $old_width) {
                    // redefine new height, resized using horizontal ratio
                    $new_height = $old_height * $ratio_horiz;
                    // if a max width was specified, use it, otherwise proportionally resize width
                    if ($box_width == 0) {
                        $new_width = $old_height * $ratio_horiz;
                    } else {
                        $new_width = $box_width;
                    }
                } else {
                    // no need to resize, already smaller than or equal to the maximum horizontal dimension
                    // note that we still need this when converting image type
                    $new_width = $old_width;
                    $new_height = $old_height;
                }
            }
        }
        
        // make sure image dimensions are whole numbers
        settype ($new_width,    'integer');
        settype ($new_height, 'integer');
        if ($debug) echo "Calculated size: $new_width &times; $new_height<br>\n";
        
        // make a new image in memory with calculated dimensions
        if ($debug) echo "Creating new image...<br>\n";
        $new_img = @imagecreatetruecolor ($new_width, $new_height);
        
        // resize
        if ($debug) echo "Resizing...<br>\n";
        @imagecopyresampled ($new_img, $old_img, 0, 0, $horiz_offset, $vert_offset, $new_width, $new_height,
            $chop_width, $chop_height);
        
        // save image
        if ($debug) echo "Saving...<br>\n";
        $result = false;
        if ($format == 'jpg') {
            if (@imagejpeg ($new_img, $new_loc, $jpeg_quality)) $result = true;
        } else if ($format == 'png') {
            @imagetruecolortopalette ($new_img, false, 256);
            if (@imagepng ($new_img, $new_loc)) $result = true;
        } else if ($format == 'gif') {
            @imagetruecolortopalette ($new_img, false, 256);
            if (@imagegif ($new_img, $new_loc)) $result = true;
        }
        
        // close our file handles
        @imagedestroy ($old_img);
        @imagedestroy ($new_img);
        
        // check the file save did not fail, and throw if it did
        if ($result == false) {
            throw new exception ("Unable to save resized image to {$new_loc}; check permissions");
            
        } else {
            apply_file_security ($new_loc);
        }
    }
    
    return $result;
}


/**
 * Parses a CSV (or other separated values) file into an array
 * 
 * @deprecated This function has been superseded by {@link parse_access_csv},
 * which handles files generated by MS Access and Excel.
 * 
 * @param string $csv_file Path to a CSV or similarly structured file (eg tab-separated, etc)
 * @param string $div Regular expression to divide the line into columns (eg. ',' for CSV, "\t" for TSV)
 * @param bool $row_as_headings Whether the first line in the file is a set of headings.
 * @param bool $col_as_headings Whether the first column is a set of headings.
 * @return array The parsed data.
 */
function parse_csv ($csv_file, $div, $row_as_headings, $col_as_headings) {
    
    $csv_lines = file ($csv_file);
    
    for ($i = 0; $i < count($csv_lines); $i++) {
        $csv_lines[$i] = rtrim ($csv_lines[$i], "\r\n");
    }
    
    $result_arr = array ();
    
    $first_row = $csv_lines[0];
    
    if ($row_as_headings) {
        $headings = split ($div, $first_row);
        $startrow = 1;
    } else {
        $temp_headings = split ($div, $first_row);
        for ($i = 0; $i < count($temp_headings); $i++) {
            $headings[$i] = $i;
        }
        $startrow = 0;
    }
    
    for ($i = $startrow; $i < count($csv_lines); $i++) {
        if ($csv_lines[$i] != '') {
            $row = split ($div, $csv_lines[$i]);
            if ($col_as_headings) {
                $row_heading = $row[0];
                $startcol = 1;
            } else {
                $row_heading = $i;
                $startcol = 0;
            }
            
            // ignore this row if using column headings and its first column is empty
            // if ($row_heading != '') {
                for ($col = $startcol; $col < count($row); $col++) {
                    $result_arr[$headings[$col]][$row_heading] = $row[$col];
                }
            // }
        }
    }
    
    return $result_arr;

}

/**
 * Transposes a 2D array.
 * 
 * For use with parse_csv.
 * 
 * @deprecated use PHP core function {@link array_flip} instead
 * 
 * @param array $in_arr The array to be transposed.
 * @return array The transposed array.
 */
// swap columns and rows in an array returned from a csv parse
function transpose_csv_array ($in_arr) {
    foreach ($in_arr as $rowname => $row) {
        foreach ($row as $col => $val) {
            $out_arr[$col][$rowname] = $val;
        }
    }
    return $out_arr;
}

/**
 * Parses a CSV file that is configured with multi-line entries (eg from MS-Access, Excel, ...) into an array
 * 
 * @TODO Add handling for headings as parse_csv used to do
 * @TODO Accept a file handle (resource) or file path (string)
 * @TODO Use fgetcsv
 * 
 * @param string $filename CSV or similarly structured file (eg tab-separated, etc)
 * @param string $delim Regular expression to divide the line into columns (',' for CSV, "\t" for TSV)
 * @param string $text_marker The extra delimiters put in by MS-Access/Excel/etc to mark text fields
 * 
 * @return array The data as an array. The first dimension corresponds to the lines in the file,
 * and the second dimension corresponds to the field data.
 */
function parse_access_csv ($filename, $delim = ",", $text_marker = '"') {
    global $num_lines_read;
    if ($file = fopen ($filename, 'r')) {
        $result = array ();
        // process lines in file
        while ($line = read_line ($file)) {
            while ($line[strlen ($line) - 1] != $text_marker and $line[strlen ($line) - 1] != $delim) {
                // if not the end of a record, need to read in the next line for further fields
                // error if this causes read past EOF
                
                // first, check that the last field is actually a text field encased by $text_marker
                $pos_text_marker = strrpos ($line, $text_marker);
                $pos_delim = strrpos ($line, $delim);
                if ($pos_text_marker === false) {
                    break;
                } else if ($pos_text_marker < $pos_delim) {
                    break;
                }
                
                $next_line = read_line ($file);
                if ($next_line === false) {
                    $broken_line = explode ($delim, $line);
                    $result = "Attempted to read past the end of file when adjoining lines " .
                        "(record {$broken_line[0]}, at line {$num_lines_read})";
                    break (2);
                }
                $line .= "<br>\n$next_line";
            }
            if (trim ($line) != '') {
                $new_line = explode ($delim, $line);
                foreach ($new_line as $key => $value) {
                    $value = trim ($value);
                    if ($value{0} == $text_marker) {
                        // remove " from start and end of text field
                        $value = substr ($value, 1, count($value) - 2);
                        // replace "" with "
                        $value = str_replace ('""', '"', $value);
                    }
                    $new_line[$key] = $value;
                }
                // store this line's records in a result array
                $result[] = $new_line;
            }
        }
        fclose ($file);
    } else {
        $result = "Unable to open file";
    }
    return $result;
}

/**
 * Reads a line from a file.
 * 
 * Used to trim lines in parse_access_csv.
 * 
 * @deprecated will be removed when TODOs for parse_access_csv are completed
 * 
 * @param resource The handle of the file you wish to read.
 * 
 * @return string The line of text, without trailing whitespace.
 */
function read_line ($file) {
    global $num_lines_read;
    $line = rtrim (fgets ($file), "\r\n ");
    if ($line !== false) {
        $num_lines_read++;
    }
    return $line;
}

/**
 * Draws a table from a 2D array.
 * 
 * Rows are the first dimension, columns the second
 * 
 * @TODO clean up code, add odd and even classes for striping support
 * 
 * @param array $array The array to use as a base for the table
 * @param int $num_cols The maximum number of columns allowed
 *    (Data beyond the maximum will be ignored; -1 means scroll indefinitely)
 * @param array $tbl_vars HTML options for the <table> tag
 * @param array $row_vars HTML options for each <tr> tag
 * @param array $col_vars HTML options for each <td> tag
 */
function array_table ($array, $num_cols = -1, $tbl_vars = array (), $row_vars = array (), $col_vars = array ()) {
    echo "<table";
    @reset ($tbl_vars);
    while (list ($key, $value) = @each ($tbl_vars)) {
        echo " $key=\"$value\"";
    }
    @reset ($row_vars);
    while (list ($key, $value) = @each ($row_vars)) {
        $row_params .= " $key=\"$value\"";
    }
    @reset ($col_vars);
    while (list ($key, $value) = @each ($col_vars)) {
        $col_params .= " $key=\"$value\"";
        if (strtolower ($key) != 'width') $col_final_params .= " $key=\"$value\"";
    }
    echo ">";
    
    $col = 0;
    foreach ($array as $data) {
        // end row if necessary
        if ($col == $num_cols) {
            echo "</tr>\n";
            $col = 0;
        }
        // start row if necessary
        if ($col == 0) {
            echo "    <tr$row_params>\n";
        }
        echo "        <td$col_params>$data</td>\n";
        $col++;
    }
    $cols_remaining = $num_cols - $col;
    if ($cols_remaining > 0) {
        echo "        <td{$col_final_params} colspan=\"{$cols_remaining}\">&nbsp;</td>";
    }
    echo "    </tr>\n</table>\n";
}


/**
 * Replaces elements in one array with values from another.
 * So, if the input contains a value that matches a key in the replacements array,
 * it will be replaced with the corresponding value from the replacements array.
 *
 * Example:
 * <code>// Gives: $arr = array ('A', 'Banana', 'C')
 *$arr = array ('A', 'B', 'C');
 *$replacements = array ('B' => 'Banana');
 *$arr = array_value_replace ($arr, $replacements);</code>
 *
 * @param array $input The array to search for matching values
 * @param array $replacements The array that holds the replacement values
 * @return array The resultant array
 */
function array_value_replace ($input, $replacements) {
    $output = array ();
    foreach ($input as $key => $value) {
        $replacement = $replacements[$value];
        if ($replacement == null) {
            $output[$key] = $value;
        } else {
            $output[$key] = $replacement;
        }
    }
    return $output;
}


/**
 * Gets the first part (i.e. protocol and hostname) of a URL that has been used to access the current page.
 * e.g. https://woot.com:77
 * 
 * @author benno, 2008-06-26
 * 
 * @return string
 */
function get_proto_host () {
    if (is_https ()) {
        $protocol = 'https://';
        $port = ($_SERVER['SERVER_PORT'] == 443? '': ':'. $_SERVER['SERVER_PORT']);
    } else {
        $protocol = 'http://';
        $port = ($_SERVER['SERVER_PORT'] == 80? '': ':'. $_SERVER['SERVER_PORT']);
    }
    return "{$protocol}{$_SERVER['SERVER_NAME']}{$port}";
}

/**
 * Determines whether HTTPS is on, which is a non-trivial operation since IIS sets $_SERVER['HTTPS'] in a
 * manner that is basically the opposite of what Apache does.
 * 
 * @author benno, 2008-06-26
 * 
 * @return bool true if the current page was accessed via HTTPS, false if it wasn't,
 *     and NULL if it can't be determined by the current algorithm
 */
function is_https () {
    
    // Apache specifies '' (blank) if HTTPS is off, and 'on' when HTTPS is on
    // However, when IIS specifies '', that (cryptically) means that HTTPS is on
    // -- This hasn't been tested, it's assumed that $_SERVER['SERVER_SOFTWARE'] contains 'IIS' somewhere
    
    $https = @$_SERVER['HTTPS'];
    if ($https == 'on') {
        return true;
    } else if ($https == 'off') {
        return false;
    } else if ($https == '') {
        if (stripos ($_SERVER['SERVER_SOFTWARE'], 'iis') !== false) {
            return true;
        } else {
            return false;
        }
    } else {
        
        // null equates to false, but it also indicates a severe problem, as it means that we can't
        // determine if the server is in HTTPS mode
        return null;
    }
}

/**
 * Forces the browser to a different URL
 * 
 * @param string $uri The URI to send the browser to, which should be a baseful URI
 * (i.e. it should include ROOT_PATH_WEB) and will be converted to an absolute URI, which is a URL
 */
function redirect ($uri) {
    
    // auto-determine URL when a relative URI is specified
    if (strpos ($uri, '://') === false) {
        
        $original_uri = $uri;
        
        $uri = str_replace ('//', '/', $uri);
        
        // automatically determine baseful URI
        if ($uri[0] != '/') {
            
            if ($uri[strlen ($uri) - 1] == '/') {
                $trailing_slash = true;
            } else {
                $trailing_slash = false;
            }
            
            // work out the relative path of the current directory from ROOT_PATH_WEB
            // first deal with symlinks below the document root
            $filename_called = $_SERVER['SCRIPT_FILENAME'];
            $filename_real = realpath($filename_called);
            if ($filename_called != $filename_real) {
                $parts = explode ('/', trim ($filename_called, '/'));
                $base_dir_real = '';
                $base_dir_called = '';
                $match = false;
                while ($part = array_shift ($parts)) {
                    $base_dir_called .= "/{$part}";
                    $base_dir_real = $base_dir_called;
                    if (is_link ($base_dir_called)) {
                        $base_dir_real = rtrim (realpath ($base_dir_called), '/');
                    }
                    if (starts_with ($filename_real, $base_dir_real)) {
                        $filename_called = str_replace ($base_dir_called, $base_dir_real, $filename_called);
                        break;
                    }
                }
            }
            
            // next, ignore fake roots
            // a fake root is what we call it when we have a .htaccess controlled internal URL rewrite,
            // that re-routes, for example, a request for /index.php onto /version1/index.php
            $current_dir = dirname ($filename_called);
            $current_relative_path = substr ($current_dir, strlen (ROOT_PATH_FILE));
            
            if ($current_relative_path == '') {
                $current_relative_path = array ();
            } else {
                $current_relative_path = explode ('/', $current_relative_path);
            }
            
            // remove self-references and deal with .. references
            $uri_parts = explode ('/', $uri);
            foreach ($uri_parts as $id => $part) {
                if ($part == '.') {
                    unset ($uri_parts[$id]);
                } else if ($part == '..') {
                    array_pop ($current_relative_path);
                    unset ($uri_parts[$id]);
                }
            }
            
            // rebuild redirect uri
            if (count ($current_relative_path) == 0) {
                $current_relative_path = '';
            } else {
                $current_relative_path = implode ('/', $current_relative_path). '/';
            }
            
            $uri = ROOT_PATH_WEB. $current_relative_path. implode ('/', $uri_parts);
            
            // if the original URI had a trailing slash, it is now re-appended, since it was stripped by
            // the explode and implode process
            if ($trailing_slash and $uri[strlen ($uri) - 1] != '/') $uri .= '/';
            
        }
        $uri = get_proto_host (). $uri;
        
        // Some debug info
        /*
        echo "<pre>Redirect to {$uri}\nwas {$original_uri}";
        echo "\n\$_SERVER['SCRIPT_FILENAME']: {$_SERVER['SCRIPT_FILENAME']}";
        echo "\nrealpath: ", realpath ($_SERVER['SCRIPT_FILENAME']);
        echo "\nROOT_PATH_FILE: ", ROOT_PATH_FILE, "\nROOT_PATH_WEB:    ", ROOT_PATH_WEB,
            "\nCurrent relative path: {$current_relative_path}\n", print_r ($_SERVER, true);
        debug_print_backtrace ();
        echo "</pre>\n";
        die ();
        // */
    }
    
    header ('Location: '. $uri);
    die ();
}

/**
 * Shows a variable in a human readable format.
 * 
 * This is basically a more configurable replacement for PHP's internal print_r function.
 * Note: this function is recursive.
 * 
 * @TODO Add support for objects that have a __show () method -- must return a string or an array
 * 
 * @param mixed &$var The variable to display
 * @param int $maxlen The maximum length string to display. Longer strings will be truncated.
 * @param int $tab The number of tabs to begin at. 0 is recommended for the first recursion.
 * @param int $tabwidth How many spaces to use for each tab (soft tabs are used).
 */
function show_var (&$var, $maxlen = 20, $tab = 0, $tabwidth = 2) {
    if (is_array ($var)) {
        for ($i = 0; $i < $tab; $i++) echo ' ';
        echo "Array (\n";
        $tab += $tabwidth;
        foreach ($var as $row) {
            for ($i = 0; $i < $tab; $i++) echo ' ';
            echo "[{$key}] => ";
            show_var ($row, $maxlen, $tab, $tabwidth);
        }
        $tab -= $tabwidth;
        for ($i = 0; $i < $tab; $i++) echo ' ';
        echo ")\n";
    } else {
        // for ($i = 0; $i < $tab; $i++) echo ' ';
        echo substr (htmlspecialchars ($var), 0, $maxlen), "\n";
    }
}

/**
 * Forces the browser to visit the current or specified page using HTTPS instead of HTTP
 * This function is incompatible with POST data, since it is not specified on the query string.
 * If the constant HTTPS_PORT is defined, this function will use that port instead of the default (443)
 * If the constant HTTPS_PORT is defined, and is 80, then this function will do nothing
 *
 * @param string $page The relative URL to a specific page (defaults to current page).
 *     This parameter is included so that you could, for example, send the user to the first page of a multi-page
 *     form if they attempt to jump out of HTTPS and into HTTP.
 */
function force_https ($page = '') {
    
    if (!is_https ()) {
        if (defined ('HTTPS_PORT')) {
            $port = (int) HTTPS_PORT;
        } else {
            $port = 443;
        }
        
        if ($port == 80) return;
        if ($port == 443) {
            $port = '';
        } else {
            $port = ':'. $port;
        }
        
        if ($page == '') {
            $url = 'https://'. $_SERVER['SERVER_NAME']. $port. $_SERVER['REQUEST_URI'];
            
        } else {
            if ($page[0] != '/') $page = ROOT_PATH_WEB. $page;
            $url = 'https://'. $_SERVER['SERVER_NAME']. $port. $page;
        }
        redirect ($url);
    }
}

/**
 * Gets the n-th position of a search term inside a string
 * 
 * @param string $haystack The string to search.
 * @param string $needle The search parameter.
 * @param int $n The desired position (e.g. 1 for first, 2 for second, etc.).
 * @param int $offset The position at which to begin the search
 * @return mixed The position, or false if the search parameter was not found
 */
function strpos_n ($haystack, $needle, $n = 1, $offset = 0) {
    $pos = false;
    $count = 0;
    while ($count < $n) {
        $pos = strpos ($haystack, $needle, $offset);
        if ($pos === false) {
            break;
        }
        $count++;
        $offset = $pos + 1;
    }
    return $pos;
}

/**
 * Concatenates all the items in an array, separating them with the specified separator
 * and separating the second-to-last item and the last item with the and separator.
 *
 * @param string $separator The separator to join the items with
 * @param array $array The array to concatenate
 * @param string $and_separator The separator to use for the last and second-to-last items. Defauts to ' and '
 * @return string The concatenated array
 */
function implode_and ($separator, $array, $and_separator = ', and ') {
    if (! is_array($array)) {
        throw new Exception ("Provided value for concatenation is not an array");
    }
    
    if (count($array) == 1) {
        reset ($array);
        $each = each ($array);
        return $each[1];
    }
    
    $last_item = array_pop ($array);
    $return = implode ($separator, $array). $and_separator. $last_item;
    
    return $return;
}

/**
 * Generates a randomised code, e.g. for checking a transaction is valid.
 * For passwords, it would be better to use generate_password() instead.
 *
 * @param int $length The length of the code to generate
 * @param string $valid_chars The list of characters that are valid for this code
 * @return string The randomised code
 */
function generate_code ($length,
        $valid_chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789') {
    
    $str = '';
    $max = strlen($valid_chars) - 1;
    if ($length > 0 and $length <= 2048) {
        for ($i = 0; $i < $length; $i++) {
            $str .= $valid_chars[rand(0, $max)];
        }
    }
    
    return $str;
}

/**
 * Generates a randomised password, which (in theory) has a better memorability than randomly generated codes.
 *
 * @param int $length How long the password should be. If not specified, a random length between 8 and 10 is used.
 * @return string The generated password
 */
function generate_password ($length = null) {
    
    if ($length === null) {
        $length = rand (8, 10);
    }
    $length = abs ((int) $length);
    
    if (defined ('PASSWORD_MIN_LENGTH') and $length < PASSWORD_MIN_LENGTH) {
        $length = PASSWORD_MIN_LENGTH;
    }
    
    $freq_number = 7;
    $freq_punc = 3;
    
    $valid_chars = array (
        'a' => 73,
        'b' => 9,
        'c' => 30,
        'd' => 44,
        'e' => 130,
        'f' => 28,
        'g' => 16,
        'h' => 35,
        'i' => 74,
        'j' => 2,
        'k' => 3,
        'l' => 35,
        'm' => 25,
        'n' => 78,
        'o' => 74,
        'p' => 27,
        'q' => 3,
        'r' => 77,
        's' => 63,
        't' => 93,
        'u' => 27,
        'v' => 13,
        'w' => 16,
        'x' => 5,
        'y' => 19,
        'z' => 1,
        '0' => $freq_number,
        '1' => $freq_number,
        '2' => $freq_number,
        '3' => $freq_number,
        '4' => $freq_number,
        '5' => $freq_number,
        '6' => $freq_number,
        '7' => $freq_number,
        '8' => $freq_number,
        '9' => $freq_number,
        '~' => $freq_punc,
        '!' => $freq_punc,
        '@' => $freq_punc,
        '#' => $freq_punc,
        '$' => $freq_punc,
        '%' => $freq_punc,
        '^' => $freq_punc,
        '&' => $freq_punc,
        '*' => $freq_punc,
        '_' => $freq_punc,
        '-' => $freq_punc,
        '+' => $freq_punc,
        '=' => $freq_punc,
        ':' => $freq_punc,
        ';' => $freq_punc,
        ',' => $freq_punc,
        '.' => $freq_punc,
        '?' => $freq_punc
    );
    
    
    $choices = array ();
    
    $total_freq = 0;
    foreach ($valid_chars as $char => $freq) {
        $total_freq += $freq;
        $choices[] = array (
            'freq_sum' => $total_freq,
            'item' => $char
        );
    }
    
    $str = '';
    if ($length > 0 and $length <= 2048) {
        for ($i = 0; $i < $length; $i++) {
            $rand = rand (1, $total_freq);
            
            foreach ($choices as $choice_id => $choice) {
                if ($rand <= $choice['freq_sum']) {
                    
                    if (preg_match ('/[a-z]/', $choice['item'])) {
                        $change_case_chance = rand (1, 100);
                        
                        if ($change_case_chance <= 30) {
                            $choice['item'] = strtoupper ($choice['item']);
                        }
                    }
                    
                    $str .= $choice['item'];
                    break;
                }
            }
        }
    }
    
    return $str;
}

/**
 * Extracts the extension from a file name
 * 
 * @param string $filename The file name
 * @return string The (last) extension of the file, or an empty string if it doesn't have an extension.
 */
function get_file_extension ($filename) {
    $ext = '';
    $dot = strrpos ($filename, '.');
    if ($dot !== false) {
        $ext = substr ($filename, $dot + 1);
    }
    return $ext;
}

/**
 * Does chmod on a file or a directory, according to the settings defined in tricho_config_*.php.
 * If there are errors, an error email is sent.
 *
 * @param $filename Filename to apply settings to
 */
function apply_file_security ($filename) {
    
    $errors = array ();
    $debug = false;
    
    // permissions for a file
    if (is_file($filename) and defined('FILE_PERMISSIONS_FILE')) {
        
        if (is_string (FILE_PERMISSIONS_FILE)) {
            $perms = intval (FILE_PERMISSIONS_FILE, 8);
        } else {
            $perms = FILE_PERMISSIONS_FILE;
        }
        $perms_human = '0'. decoct ($perms);
        
        if ($debug) echo "<br>chmod {$filename} {$perms_human}\n";
        $status = @chmod ($filename, $perms);
        if ($status == false) {
            $errors[] = "Unable to chmod file to {$perms_human}";
        }
        
    // permissions for a directory
    } else if (is_dir($filename) and defined('FILE_PERMISSIONS_DIR')) {
        
        if (is_string (FILE_PERMISSIONS_DIR)) {
            $perms = intval (FILE_PERMISSIONS_DIR, 8);
        } else {
            $perms = FILE_PERMISSIONS_DIR;
        }
        $perms_human = '0'. decoct ($perms);
        
        if ($debug) echo "<br>chmod {$filename} {$perms_human}\n";
        $status = @chmod ($filename, $perms);
        if ($status == false) {
            $errors[] = "Unable to chmod dir to {$perms_human}";
        }
    }
    
    if (!is_file ($filename) and !is_dir ($filename)) {
        $errors[] = "{$filename} is neither a file nor a directory";
    }
    
    // errors
    if (count ($errors) > 0) {
        // create message
        $message = "Errors occurred while applying file security.\n\n";
        $message .= "Filename: {$filename}\n";
        $message .= "Errors:\n";
        foreach ($errors as $error) {
            $message .= "    {$error}\n";
        }
        
        // email admins
        if ($debug) {
            echo '<pre>' . $message . '</pre><hr>';
        } else {
            email_error ($message);
        }
    }
    
}

/**
 * Gets the page name, including the query string (if one exists) from a URL
 * 
 * @param string $url The URL
 * @return string The page name
 */
function base_url ($url) {
    
    // do not search past querystring separator
    $pos = strpos ($url, '?');
    if ($pos === false) {
        $offset = 0;
    } else {
        $offset = $pos - strlen ($url);
    }
    
    // echo "Offset: {$offset}<br>\n";
    
    $slash_pos = strrpos ($url, '/', $offset);
    
    if ($slash_pos === false) {
        return $url;
    } else {
        return substr ($url, $slash_pos + 1);
    }
    
}

/**
 * Outputs the <option>s that should go in a <select> for a mysql result set, or an array.
 *
 * @param mixed $res A MySQL result set, or an array of [ [ field_name => val, field_name => val ], ... ]
 * @param mixed $selected_value The value to select
 * @param string $id_field The name of the identification field in the result set. Defaults to 'ID'
 * @param string $name_field The name of the name field in the result set. Defaults to 'Name'
 */
function draw_dropdown_items ($res, $selected_value = null, $id_field = 'ID', $name_field = 'Name') {
    if (is_resource ($res)) {
        // is a MySQL result set
        while ($row = fetch_assoc($res)) {
            if ($row[$id_field] == $selected_value) {
                echo "<option value=\"{$row[$id_field]}\" selected>{$row[$name_field]}</option>\n";
            } else {
                echo "<option value=\"{$row[$id_field]}\">{$row[$name_field]}</option>\n";
            }
        }
        
    } else if (is_array ($res)) {
        // is an array
        foreach ($res as $row) {
            if ($row[$id_field] == $selected_value) {
                echo "<option value=\"{$row[$id_field]}\" selected>{$row[$name_field]}</option>\n";
            } else {
                echo "<option value=\"{$row[$id_field]}\">{$row[$name_field]}</option>\n";
            }
        }
    }
}


/**
 * Converts an object to a string if it has a __toString method.
 * This function exists since PHP prior to 5.2 doesn't call an object's __toString method when casting to
 * a string.
 * 
 * @param mixed $object The object to convert
 * 
 * @return string
 * @TODO make all tricho objects extend from a base class that has a __toString
 *       method involving sql_object_hash, then remove this function
 */
function cast_to_string ($object) {
    if (is_object ($object)) {
        if (method_exists ($object, '__toString')) {
            return $object->__toString ();
        } else {
            $val = 'Object['. get_class ($object). ']';
            if (version_compare (PHP_VERSION, '5.2.0') >= 0) {
                $val .= '#'. spl_object_hash ($object);
            }
            return $val;
        }
    } else {
        return (string) $object;
    }
}

/**
 * Works much like PHP's print_r function, but uses an object's __printHuman method (if one exists) to
 * display objects in a compact and human-readable fashion that isn't necessarily the same as __toString
 * 
 * @param mixed $var the variable to print
 * @param int $indent_tab the number of tabs to indent
 * @param bool $indent_self whether or not to indent this value (this should be false if the variable
 *     is an array element)
 */
function print_human ($var, $indent_tab = 0, $indent_self = true) {
    
    if (defined ('PRINT_HUMAN_INDENT_WIDTH')) {
        $indent_width = PRINT_HUMAN_INDENT_WIDTH;
    } else {
        $indent_width = 4;
    }
    
    $indent = str_repeat (' ', $indent_width * $indent_tab);
    
    if (is_object ($var)) {
        
        if (method_exists ($var, '__printHuman')) {
            if ($indent_self) echo $indent;
            echo get_class ($var), ': ', $var->__printHuman ($indent_tab, false);
        } else {
            echo get_class($var);
            $members = (array) $var;
            if ($indent_tab <= 4 and count($members) > 0) {
                echo " {\n";
                $member_count = 0;
                foreach ($members as $key => $member) {
                    if ($member_count++ > 0) echo ",\n";
                    $null_pos = strrpos($key, "\0");
                    if ($null_pos !== false) {
                        $key = substr($key, $null_pos + 1);
                    }
                    echo $indent, str_repeat(' ', $indent_width), $key, ': ';
                    print_human($member, $indent_tab + 1, false);
                }
                echo "\n{$indent}}";
            }
        }
        
    } else if (is_array ($var)) {
        
        if ($indent_self) echo $indent;
        
        echo "Array (\n";
        foreach ($var as $key => $val) {
            echo $indent, str_repeat (' ', $indent_width), "[{$key}] => ",
                print_human ($val, $indent_tab + 1, false), "\n";
        }
        echo $indent, ")\n";
        
    } else if (is_bool ($var)) {
        
        if ($indent_self) echo $indent;
        echo ($var? 'TRUE': 'FALSE');
        
    } else if (is_null($var)) {
        if ($indent_self) echo $indent;
        echo 'NULL';
        
    } else {
        
        if ($indent_self) echo $indent;
        echo $var;
    }
    
}

/**
 * Logs major actions taken that affect the site's database architecture, so that actions recorded on a test
 * server can easily be played back on a live server, thereby preventing issues with site feature upgrades.
 * 
 * @author benno, 2008-07-07
 * 
 * @param Database $db Database meta-data, which is used to automatically create the logging table
 *     and define it in the tables.xml if it doesn't exist.
 * @param string $action A description of the action taken (e.g. "Created table X").
 * @param string $sql The SQL query used (if any) to perform the action.
 */
function log_action (Database $db, $action, $sql = '') {
    
    if (defined ('SETUP_LOG_ACTIONS') and SETUP_LOG_ACTIONS) {
        
        // store SQL queries with trailing ; to support easy import/export
        $sql = trim ($sql);
        if ($sql != '' and $sql[strlen ($sql) - 1] != ';') $sql .= ';';
        
        // check that the logging table exists
        $error = false;
        $res = execq("SHOW TABLES LIKE '_tricho_log'");
        if ($res->rowCount() != 1) {
            $error = "Logging table doesn't exist\n\n".
                "Was attempting to log the following action:\n{$action}";
        }
        
        if (!$error) {
            
            $field_setters = array (
                '`DateLogged` = NOW()',
                '`User` = '. sql_enclose ((string) $_SESSION['setup']['id']),
                '`Action` = '. sql_enclose ($action),
                '`SQL` = '. sql_enclose ($sql)
            );
            $q = "INSERT INTO _tricho_log SET ". implode (', ', $field_setters);
            if (execq($q)) {
                return;
            } else {
                $error = "Failed to log the following action:\n{$action}";
            }
        }
        
        // all errors are delayed until this point to they can all be handled in the same way
        if ($sql != '') {
            $error .= "\n\nSQL used for this action:\n{$sql}";
        }
        email_error ($error);
    }
}

/**
 * Converts a cache length (e.g. 14d) into its constituent parts.
 * 
 * @author benno, 2008-09-12
 * 
 * @param string $cache_string the cache period.
 *     This is made up of a number, followed by a letter.
 *     Valid letters are stored in $image_cache_scales
 * 
 * @return array The cache length determined. Note that if the input doesn't make sense,
 *     the default settings (7 days) will be returned.
 *     [0] The period for which the file should be cached by the browser
 *     [1] The scale of the period (e.g. minute, hour, day)
 */
function determine_cache_length ($cache_string) {
    
    // Global vars are evil, but there's no nice way to define a constant array in PHP 5
    global $image_cache_scales;
    
    $matches = array ();
    preg_match ('/([0-9]+)(['. implode ('', array_keys ($image_cache_scales)) .'])/', $cache_string, $matches);
    if ($matches[1] == '' or $matches[2] == '') {
        $cache_length = 7;
        $cache_scale = 'd';
    } else {
        list ($junk, $cache_length, $cache_scale) = $matches;
    }
    return array ($cache_length, $cache_scale);
}


/**
 * Checks that a user session has not been hijacked, by checking if the IP address or user agent
 * has changed since the last access. If a problem is detected, a new blank session is generated.
 */
function validate_session () {
    
    if (!defined ('SESSION_NEW_IP')) define ('SESSION_NEW_IP', true);
    if (!defined ('SESSION_NEW_AGENT')) define ('SESSION_NEW_AGENT', true);
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $agent = $_SERVER['HTTP_USER_AGENT'];
    $host_name = null;
    $ip_changed = false;
    $agent_changed = false;
    
    // Whether a new blank session needs to be generated
    $generate_blank_session = false;
    
    if (@$_SESSION['_tricho']['ip_addr'] != $ip) $ip_changed = true;
    if (@$_SESSION['_tricho']['user_agent'] != $agent) $agent_changed = true;
    
    // If nothing has changed, there's no need to waste any effort in further processing
    if (!$ip_changed and !$agent_changed) return;
    
    
    // If IP was already set, it has really changed: this is a possible security problem
    if ($ip_changed and @$_SESSION['_tricho']['ip_addr'] != '') {
        if (SESSION_NEW_IP) {
            
            // At present, a new blank session will not be created for non-admin users,
            // due to likely problems with users on internet connections with dynamic IP addresses.
            // For now, just record the IP change, so that patterns can be observed.
            if (isset ($_SESSION[ADMIN_KEY]['id'])
                    and $_SESSION[ADMIN_KEY]['id'] != '')
            {
                $generate_blank_session = true;
            }
        }
    }
    
    // If agent was already set, it has really changed: this is a possible security problem
    if ($agent_changed and @$_SESSION['_tricho']['user_agent'] != '') {
        if (SESSION_NEW_AGENT) $generate_blank_session = true;
    }
    
    if ($generate_blank_session) {
        // This user is probably hijacking the original session, so leave the original session alone
        // and don't annoy the legitimate user
        session_regenerate_id (false);
        session_unset ();
        $_SESSION = array ();
        $_SESSION['_tricho']['err'] = array (
            'Security problem detected; your session has been reset'
        );
    }
    
    // Store the user's details in the session for comparison next time they visit a page
    $_SESSION['_tricho']['ip_addr'] = $ip;
    $host_name = gethostbyaddr ($ip);
    $_SESSION['_tricho']['host_name'] = $host_name;
    $_SESSION['_tricho']['user_agent'] = $agent;
}


/**
 * Converts a size specified in bytes to the appropriate order of magnitude, e.g. KB or MiB.
 * 
 * Examples:
 * - bytes_to_human (2000): '2 KiB'
 * - bytes_to_human (2000, 1, 1000): '2 KB'
 * - bytes_to_human (2000, 3, 1024): '1.953 KiB'
 * 
 * @param int $size The number to process. This figure should be in bytes.
 * @param int $precision The maximum number of digits after the decimal that should be shown. Defaults to 1.
 * @param int $factor either 1024 (the default) or 1000 - for IEC or SI units, respectively
 * @return string The value, in a human friendly format.
 */
function bytes_to_human ($size, $precision = 1, $factor = BYTES_TO_HUMAN_FACTOR) {
    $size = (int) $size;
    
    if ($factor == 1000) {
        $units = array ('B', 'KB', 'MB', 'GB', 'TB');
    } else {
        
        // Only allow a factor of 1000 or 1024, anything else is nonsense
        $factor = 1024;
        $units = array ('B', 'KiB', 'MiB', 'GiB', 'TiB');
    }
    
    $unit = 0;
    
    $max_unit = count ($units) - 1;
    
    while ($size >= $factor) {
        $size = $size / $factor;
        $unit++;
        if ($unit == $max_unit) break;
    }
    
    return round ($size, $precision). " {$units[$unit]}";
}

/**
 * Clean up HTML input from TinyMCE (or other rich text editor).
 *
 * @param string $data                 Raw rich text input
 * @param string $tags_allow     List of tags and associated attributes to allow
 * @param string $tags_replace List of tags to replace
 * @param string $tags_deny        List of tags to deny (remove)
 *
 * @return string Cleaned rich text
 */
function clean_rich_text_input (
        $data,
        $tags_allow = '',
        $tags_replace = '',
        $tags_deny = ''
) {

    /* this is just to help out calling code */
    if (trim ($tags_allow) == '')     $tags_allow     = HTML_TAGS_ALLOW;
    if (trim ($tags_replace) == '') $tags_replace = HTML_TAGS_REPLACE;
    if (trim ($tags_deny) == '')        $tags_deny        = HTML_TAGS_DENY;
    
    if (trim ($data) == '') {
        return '';
    }

    // fix line endings with UNIX style
    $data = preg_replace ('/\r[\n]?/', "\n", $data);
    
    $text_doc = new DOMDocument ('1.0', 'utf-8');
    $text_doc->preserveWhiteSpace = false;
    $text_doc->formatOutput = true;
    $text_doc->loadHTML (
        '<html><head><meta http-equiv="content-type" content="text/html; charset=utf-8">'.
        '</head><body>'. $data. '</body></html>'
    );

    // push nodes from the <head> portion into <body>
    // this is done because the loadHTML method puts any script tags in the <head>
    
    $body_node = $text_doc->getElementsByTagName ('body')->item (0);
    
    // make sure there is a body node!
    if (!$body_node) {
        $html_node = $text_doc->getElementsByTagName ('html')->item (0);
        $body_node = $text_doc->createElement ('body');
        $html_node->appendChild ($body_node);
    }
    
    $head_node = $text_doc->getElementsByTagName ('head')->item (0);
    
    while ($last_head_child = $head_node->lastChild) {
        /* ignore these... */
        switch ($last_head_child->nodeName) {
        case 'meta':
        case 'title':
        case 'link':
        case 'script':
            continue;
        }

        if ($first_body_child = $body_node->firstChild) {
            $body_node->insertBefore ($last_head_child, $first_body_child);
        } else {
            $body_node->appendChild ($last_head_child);
        }
    }
    
    HtmlDom::removeUnwantedNodes (
        $body_node, true, $tags_allow, $tags_replace, $tags_deny
    );
    
    $leaf_nodes = HtmlDom::getLeafNodes ($body_node);
    
    $data = '{';
    $first_leaf_node = true;
    foreach ($leaf_nodes as $leaf_node) {
        if (!$first_leaf_node) {
            $data .= ', ';
        } else {
            $first_leaf_node = false;
        }
        
        if ($leaf_node instanceof DOMElement) {
            $data .= '&lt;'. $leaf_node->tagName. '&gt;';
        } else {
            $data .= $leaf_node->data;
        }

        if (trim ($leaf_node->data) != '') {
            $node = HtmlDom::forceParagraph ($leaf_node);
        }
        
        if ($node instanceof DOMElement) {
            $data .= ': &lt;'. $node->tagName. '&gt;';
        } else {
            $data .= ': '. $node->data;
        }
    }
    
    $data .= "}";
    
    // clear data unless we're testing and want to see what
    // nodes were forced to have an ancestor P
    $data = '';
    
    $children = $body_node->childNodes;
    for ($j = 0; $j < $children->length; $j++) {
        $data .= HtmlDom::getNodeText ($children->item ($j));
    }

    return $data;
}


/**
 * Checks a user uploaded file is alright.
 * N.B.: this function is intended for use with generated forms.
 *
 * @param string $field_name The field name of the file to check
 * @param string $english The english name of the file to check
 * @param bool $is_mandatory True to perform mandatory checking, false otherwise
 * @param bool $is_image True if the uploaded file is to be an image, false otherwise
 * @param string $error_group Form group in which the the field belongs
 * @return string The value to put into the session
 */
function validate_file ($field_name, $english_name, $is_mandatory, $is_image,
        $error_group = 'default')
{
    global $temp_errs;
    
    $post_field_name = str_replace (' ', '_', $field_name);
    
    if ($_FILES[$post_field_name]['error'] == UPLOAD_ERR_INI_SIZE or
            $_FILES[$post_field_name]['error'] == UPLOAD_ERR_FORM_SIZE) {
        $temp_errs[$error_group][] = "Maximum file size exceeded for field <em>{$english_name}</em>";
    } else if ($_FILES[$post_field_name]['error'] != UPLOAD_ERR_OK) {
        if ($is_mandatory) {
            $temp_errs[$error_group][] = "No value for required field <em>{$english_name}</em>";
        } else {
            if ($_FILES[$field_name]['name'] != '') {
                $temp_errs[$error_group][] = "Upload error for <em>{$english_name}</em>";
            }
        }
    } else if (!is_uploaded_file ($_FILES[$post_field_name]['tmp_name'])) {
        $temp_errs[$error_group][] = "Upload error for <em>{$english_name}</em>";
    } else if ($is_image) {
        if (substr ($_FILES[$post_field_name]['type'], 0, 6) != 'image/') {
            $file_name_parts = explode ('.', $value['name']);
            $extn = $file_name_parts[count ($file_name_parts) - 1];
            if (!in_array ($extn, array ('jpg', 'png', 'gif', 'jpeg'))) {
                $temp_errs[$error_group][] = "Invalid file type for <em>{$english_name}</em>";
            }
        } else {
            $type = substr ($_FILES[$post_field_name]['type'], 6);
            if (!in_array ($type, array ('jpeg', 'png', 'gif', 'pjpeg'))) {
                $temp_errs[$error_group][] = "Invalid file type for <em>{$english_name}</em>";
            }
        }
    }
    
    return $_FILES[$post_field_name]['name'];
}


/**
 * Uploads a file into a database-driven directory.
 * N.B.: this function is intended for use with generated forms.
 *
 * @param string $field_name The name of the field to upload the file for
 * @param string $storage_location Where to put the file
 * @param integer $id The ID of the field
 * @param string $mask The mask for the field
 * @param array $resize_details An array of thumbnail and main image resizes
 *     possible keys: 'resize' = 'WIDTHxHEIGHT', 'medium', 'small'
 */
function upload_file ($field_name, $storage_location, $id, $mask, $resize_details = null) {
    $post_field_name = str_replace (' ', '_', $field_name);
    
    if ($_FILES[$post_field_name]['name'] == '') return;
    
    if (substr($storage_location, -1) != '/') $storage_location .= '/';
    
    $temp_filename = $_FILES[$post_field_name]['tmp_name'];
    $new_filename = ROOT_PATH_FILE . $storage_location . $mask . '.' . $id;
    
    // resize if necessary
    if (is_array ($resize_details)) {
        $resize = $resize_details['resize'];
        if ($resize != null) {
            list ($width, $height) = explode ('x', $resize, 2);
            make_sized_image ($temp_filename, $new_filename, $width, $height);
            unset ($resize_details['resize']);
        } else {
            copy ($temp_filename, $new_filename);
        }
        
        // generate thumbnails
        foreach ($resize_details as $key => $val) {
            list ($width, $height) = explode ('x', $val, 2);
            make_sized_image ($temp_filename, $new_filename . '.' . $key, $width, $height);
        }
    } else {
        copy ($temp_filename, $new_filename);
    }
}


/**
 * Shortcut for htmlspecialchars
 * @param string $str
 * @return string
 * @author benno 2010-10-25 initial version with just 1 parameter
 * @author benno 2011-08-17 added extra params, default to utf8, specify
 *                 double encoding if PHP version is at least 5.2.3
 */
function hsc ($str, $flags = ENT_COMPAT, $charset = '', $double_encode = true) {
    if ($charset == '') $charset = 'UTF-8';
    if (version_compare (PHP_VERSION, '5.2.3') >= 0) {
        return htmlspecialchars ($str, $flags, $charset, $double_encode);
    } else {
        return htmlspecialchars ($str, $flags, $charset);
    }
}


/**
 * Determines if a file can be written (overwritten or created) at a particular path
 * @param string $path the path to the file to be written
 * @return bool true if a file can be written at the specified path
 * @author benno 2011-02-25
 */
function file_writeable ($path) {
    settype ($path, 'string');
    if (file_exists ($path) and is_file ($path) and is_writeable ($path)) {
        return true;
    } else if (file_exists ($path)) {
        return false;
    }
    $path = dirname ($path);
    if (file_exists ($path) and is_dir ($path) and is_writeable ($path)) {
        return true;
    }
    return false;
}


/**
 * Trims a string pattern from the beginning of a string, if possible
 * @param string $str the string to trim
 * @param string $beginning the string pattern to remove
 * @return string the trimmed string if the starting pattern matched, or the original string if not
 * @author benno 2011-03-08
 */
function trim_start ($str, $beginning) {
    if (substr ($str, 0, strlen ($beginning)) == $beginning) {
        return substr ($str, strlen ($beginning));
    }
    return $str;
}


/**
 * Determines if a string starts with a string pattern
 * @param string $str the string to check
 * @param string $beginning the string pattern to find at the beginning of $str
 * @return bool true if $str starts with $beginning
 * @author benno 2011-03-08
 */
function starts_with ($str, $beginning) {
    if (substr ($str, 0, strlen ($beginning)) == $beginning) return true;
    return false;
}


/**
 * Determines if a string ends with a string pattern
 * @param string $str the string to check
 * @param string $ending the string pattern to find at the end of $str
 * @return bool true if $str ends with $ending
 * @author benno 2011-03-08
 */
function ends_with ($str, $ending) {
    if (substr ($str, -strlen ($ending)) == $ending) return true;
    return false;
}


/**
 * Checks the system configuration
 * @author benno 2013-09-16
 */
function check_config() {
    if (tricho\Runtime::get('master_salt') == '') {
        error_log('Tricho: a unique master_salt value is required');
        redirect(ROOT_PATH_WEB . 'system_error.php?err=conf');
    }
}


/**
 * Finds and removes all matching elements from an array.
 * Note that the array keys are not modified.
 * 
 * @author benno 2013-10-14
 * @param mixed $needle The target value
 * @param array $haystack The array
 * @param bool $strict If true, compare using ===, otherwise use ==
 * @return void
 */
function array_remove($needle, array &$haystack, $strict = false) {
    while (true) {
        $key = array_search($needle, $haystack, $strict);
        if ($key === false) return;
        unset($haystack[$key]);
    }
}


function url_append_param($url, $name, $value) {
    if (strpos($url, '?') !== false) {
        $url .= '&';
    } else {
        $url .= '?';
    }
    $url .= urlencode($name) . '=' . urlencode($value);
    return $url;
}


/**
 * Runs glob within a directory, giving the file names relative to it
 * @param string $path
 * @param string $glob_pattern
 * @return array
 */
function path_glob($path, $glob_pattern) {
    $files = [];
    if (!ends_with($path, '/')) $path .= '/';
    $res = glob($path . $glob_pattern);
    foreach ($res as $file) {
        $file = trim_start($file, $path);
        $files[] = $file;
    }
    return $files;
}
?>
