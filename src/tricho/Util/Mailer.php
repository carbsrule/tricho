<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Util;

use \DOMDocument;
use \Mail;
use \Mail_mime;

use Tricho\Runtime;
use Tricho\Meta\Database;

require_once 'Mail.php';
require_once 'Mail/mime.php';

/**
 * Used to send e-mails with variable content, driven by a plain-text or html
 * template. The template contains special tags which refer to a parameter.
 * When the email is sent, the tags get replaced with the value of the
 * parameter.
 * 
 * The parameter should be specified in the template in one of the following
 * formats: {{param_name}} or {{param_name|default_value}}
 * 
 * The parameter name is case insensitive. If the specified parameter does not
 * exist, it will be replaced with the default value, if specified, or throw an
 * error if no default value was specified.
 * 
 * @author Josh, 2007-12-04, 2008-12-15
 * @author Benno, 2008-04-22, 2009-06-23, 2014-01-22
 */
class Mailer {
    const DISPLAY_ONLY = 1;
    const DISPLAY_AND_SEND = 2;
    const SEND_ONLY = 3;
    
    const TEXT = 1;
    const HTML = 2;
    
    private $params;
    private $mode = self::SEND_ONLY;
    private $message = null;
    private $new_params = false;
    private $normal_attachments = array();
    private $inline_attachments = array();
    
    
    /**
     * The constructor sets up the following default parameters:
     * - WEB_ROOT: URL to the root of the site, e.g. http://example.com/pub/
     * - SITE_NAME: The name of the site
     */
    public function __construct() {
        $this->params = array(
            'WEB_ROOT' => get_proto_host() . ROOT_PATH_WEB,
            'SITE_NAME' => Runtime::get('site_name')
        );
        
        $this->message = array(
            Mailer::TEXT => false,
            Mailer::HTML => false
        );
    }
    
    
    /**
     * Loads a message template from a file
     *
     * @param string $filename The filename of the mail file, relative to the
     *        website root. e.g. 'mail/join.txt'
     * @param mixed $type The type of template. Should be one of:
     *        - Mailer::TEXT
     *        - Mailer::HTML
     *        - null (for autodetection): filenames ending in '.htm' or '.html'
     *          are interpreted as HTML files.
     * @return bool True if the file was read successfully
     */
    public function loadTemplateFile($filename, $for = null) {
        if (!$filename) return false;
        
        if ($for == null) {
            $for = Mailer::TEXT;
            if (preg_match('/\.html?$/i', $filename)) $for = Mailer::HTML;
        }
        
        if ($message = @file_get_contents(ROOT_PATH_FILE . $filename)) {
            $this->message[$for] = $message;
            $this->applyParams(true);
            return true;
        } else {
            return false;
        }
    }
    
    
    /**
     * Uses an existing string as the template for the message
     *
     * @param string $str The template to use
     * @param integer $for The context of the template. Should be one of:
     *        - Mailer::TEXT
     *        - Mailer::HTML
     *        - null (for autodetection): templates containing a body tag are
     *          interpreted as HTML.
     */
    public function setTemplate($str, $for = null) {
        if ($for == null) {
            $for = Mailer::TEXT;
            if (preg_match('/<body/i', $str)) $for = Mailer::HTML;
        }
        $this->message[$for] = (string) $str;
        $this->applyParams(true);
    }
    
    
    /**
     * Gets the HTML or plain text message that is to be sent out
     * 
     * @author benno, 2009-01-08
     * @param int $type The type of text to get, Mailer::TEXT or Mailer::HTML
     * @return mixed The text (as a string), or false if no text has been set
     */
    public function getMessage($type) {
        return $this->message[$type];
    }
    
    
    /**
     * Sets a named parameter to the specified value
     *
     * @param string $name the parameter name, e.g. SUPPORT_EMAIL.
     *        N.B. all parameter names are converted to uppercase.
     * @param string $value the value, e.g. support@example.com
     */
    public function setParam($name, $value) {
        $this->params[$name] = (string) $value;
        $this->new_params = true;
    }
    
    
    /**
     * Gets a named parameter
     * 
     * @param string $name the name of the parameter to get
     * @return Mixed A string containing the parameter, or null if it doesn't
     *         exist
     */
    public function getParam($name) {
        foreach ($this->params as $param_name => $param_value) {
            if (strcasecmp($param_name, $name) == 0) {
                return $param_value;
            }
        }
        return null;
    }
    
    
    /**
     * Adds multiple parameters.
     * This is useful for adding all of the session data from a just-submitted
     * form, for example.
     *
     * @param array $params An array of key-value pairs for the params to add
     *        to the the current list of params
     */
    public function addParams(array $params) {
        foreach ($params as $name => $value) {
            $this->params[$name] = (string) $value;
        }
        $this->new_params = true;
    }
    
    
    /**
     * Clears the parameters. Note that any parameters that have already been
     * applied cannot be undone.
     */
    public function clearParams() {
        $this->params = array();
    }
    
    
    /**
     * Sets the mode, which determines the behaviour of the {@link send} method
     * 
     * @param int $mode one of the following: Mailer::DISPLAY_ONLY,
     *        Mailer::DISPLAY_AND_SEND, or Mailer::SEND_ONLY. In the case of
     *        DISPLAY_ONLY or DISPLAY_AND_SEND, the message will be displayed
     *        via a call to {@link echo} when the {@link send} function is
     *        called
     * @return bool true if the specified mode was set
     */
    public function setMode($mode) {
        $mode = (int) $mode;
        switch ($mode) {
            case self::DISPLAY_ONLY:
            case self::DISPLAY_AND_SEND:
            case self::SEND_ONLY:
                $this->mode = $mode;
                return true;
                break;
            
            default:
                return false;
        }
    }
    
    
    /**
     * Apply standard parameters - to be used before calling send() if you are
     * planning to send multiple individualised emails with the same Mailer
     * object.
     * 
     * @param bool $force if true, the parameters will always be applied. By
     *        default, the parameters are only applied if new parameters have
     *        been added since applyParams was last called.
     */
    public function applyParams($force = false) {
        if ($force or $this->new_params) {
            foreach ($this->message as $for => $message) {
                $this->message[$for] = bind_message_params($this->message[$for], $this->params);
            }
        }
    }
    
    
    /**
     * Adds an attachment
     */
    public function addAttachment(MailerAttachment $attachment, $inline) {
        if ($inline) {
            $this->inline_attachments[] = $attachment;
        } else {
            $this->normal_attachments[] = $attachment;
        }
    }
    
    
    /**
     * Converts relative image URLs in the HTML message into absolute
     * URLs, e.g. images/example.png will be changed to
     * http://example.com/images/example.png
     */
    public function linkInlineImages() {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML($this->message[Mailer::HTML]);
        
        // Process IMG tags in the HTML
        $images = $doc->getElementsByTagName('img');
        for ($i = 0; $i < $images->length; ++$i) {
            $src = $images->item($i)->getAttribute('src');
            
            if (substr($src, 0, 7) != "http://") {
                $url = $_SERVER['SERVER_NAME'] . ROOT_PATH_WEB . $src;
                $url = "http://" . $url;
            } else {
                $url = $src;
            }
            $images->item($i)->setAttribute('src', $url);
        }
        
        // Process url(...) references to images in any inline CSS
        $styles = $doc->getElementsByTagName('style');
        $pattern = '/url\s*\(([^)]+?\.(?:png|jpg|jpeg|gif))\)/i';
        $mode = PREG_OFFSET_CAPTURE;
        for ($i = 0; $i < $styles->length; ++$i) {
            $style_text = $styles->item($i)->firstChild->data;
            $matches = array();
            if (preg_match_all($pattern, $style_text, $matches, $mode)) {
                
                // Need to reverse the matches, as doing substring replacement
                // in order would make the match offsets wrong (unless the
                // length of the replacement is the same as the pattern, which
                // is almost guaranteed not to happen), and thus the resultant
                // string would be garbled
                $matches[1] = array_reverse($matches[1]);
                foreach ($matches[1] as $match_info) {
                    list($src_filename, $match_offset) = $match_info;
                    
                    // Ensure link starts with 'http://'
                    if (substr($src_filename, 0, 7) != "http://") {
                        $url = "http://" . $_SERVER['SERVER_NAME'] .
                            ROOT_PATH_WEB . $src_filename;
                    } else {
                        $url = $src_filename;
                    }
                    $style_text = substr_replace($style_text, $url, $match_offset, strlen($src_filename));
                }
            }
            
            $styles->item($i)->firstChild->data = $style_text;
        }
        $this->message[Mailer::HTML] = $doc->saveHTML();
    }
    
    
    /**
     * Converts relative image URLs in the HTML message into references to the
     * CIDs of the matching attachments. Any missing files will automatically
     * be attached.
     */
    public function attachInlineImages() {
        if ($this->message[Mailer::HTML] == false) return;
        
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->loadHTML($this->message[Mailer::HTML]);
        
        // Process IMG tags
        $images = $doc->getElementsByTagName('img');
        for ($i = 0; $i < $images->length; ++$i) {
            $src = $images->item($i)->getAttribute('src');
            
            // Determines the filename, cname and MIME type of a file specified
            // in a src attribute. The cname is determined in this
            // createFromURI() rather than later on in attachInlineImages() so
            // that if the src is a file.php reference, the code for looking up
            // the real filename can be stored elsewhere.
            $new_attachment = MailerAttachment::createFromURI($src);
            if ($new_attachment == null) continue;
            
            // Check that the file is not already attached, to prevent
            // duplicate attachments. Also get the cname of the attached file
            // and use that, just in case createFromURI returns multiple
            // different cnames for the same file
            $file_found = false;
            foreach ($this->inline_attachments as $attachment) {
                if ($attachment->getServerFilename() == $new_attachment->getServerFilename()) {
                    $cname = $attachment->getAttachFilename();
                    $file_found = true;
                    break;
                }
            }
            
            if (!$file_found) {
                $cname = $new_attachment->getAttachFilename();
                $this->inline_attachments[] = $new_attachment;
            }
            $images->item($i)->setAttribute('src', $cname);
        }
        
        // Process url(...) references to images in any inline CSS
        $styles = $doc->getElementsByTagName('style');
        $pattern = '/url\s*\(([^)]+?\.(?:png|jpg|jpeg|gif))\)/i';
        for ($i = 0; $i < $styles->length; ++$i) {
            $style_text = $styles->item($i)->firstChild->data;
            $matches = array();
            if (preg_match_all($pattern, $style_text, $matches, PREG_OFFSET_CAPTURE)) {
                
                // Need to reverse the matches, as doing substring replacement
                // in order would make the match offsets wrong (unless the
                // length of the replacement is the same as the pattern, which
                // is almost guaranteed not to happen), and thus the resultant
                // string would be garbled
                $matches[1] = array_reverse($matches[1]);
                foreach ($matches[1] as $match_info) {
                    list($src_filename, $match_offset) = $match_info;
                    
                    $new_attachment = MailerAttachment::createFromURI($src);
                    if ($new_attachment == null) continue;
                    
                    // Check that the file is not already attached, to prevent
                    // duplicate attachments. Also get the cname of the
                    // attached file and use that, just in case createFromURI
                    // returns multiple different cnames for the same file
                    $file_found = false;
                    foreach ($this->inline_attachments as $attachment) {
                        if ($attachment->getServerFilename() == $new_attachment->getServerFilename()) {
                            $cname = $attachment->getAttachFilename();
                            $file_found = true;
                            break;
                        }
                    }
                    
                    // If the file was not found, add it to the list of attachments
                    if (!$file_found) {
                        $cname = $new_attachment->getAttachFilename();
                        $this->inline_attachments[] = $new_attachment;
                    }
                    
                    $style_text = substr_replace($style_text, $cname, $match_offset, strlen($src_filename));
                }
            }
            
            $styles->item($i)->firstChild->data = $style_text;
        }
        
        $this->message[Mailer::HTML] = $doc->saveHTML();
    }
    
    
    /**
     * Converts (X)HTML code to plain text, for plain text emails
     */
    function createPlaintextFromHTML() {
        if ($this->message[Mailer::HTML] == false) return;
        
        // Replace newlines and tabs with spaces
        $plain = str_replace(array("\r\n", "\r", "\n", "\t"), ' ', $this->message[Mailer::HTML]);
        
        // Replace images with their alt text
        $plain = preg_replace('/<img[^>]* alt="([^"]+?)"[^>]*\/?>/i', '$1', $plain);
        
        // Replace links with the name of the link, followed by the URL in
        // brackets, e.g. <a href="http://example.com">Wow</a> would become
        // "Wow [LINK: http://example.com]".
        $plain = preg_replace('/<a[^>]* href="([^"]+?)"[^>]*>(.*?)<\/a>/i', '$2 [LINK: $1]', $plain);
        
        // Convert line breaks to plain text
        $plain = preg_replace('/<br\s*\/?>/i', "\n", $plain);
        $plain = preg_replace('/<\/p>/i', "\n\n", $plain);
        $plain = preg_replace('/<\/h[1-6]>/i', "\n\n", $plain);
        
        // Remove HTML tags
        $plain = strip_tags($plain);
        
        // Replace multiple spaces with a single space,
        $plain = preg_replace('/[ \t]+/', ' ', $plain);
        
        // Replace non-breaking spaces (represented by a unicode character, not
        // &nbsp;)
        $plain = str_replace("\xC2\xA0", ' ', $plain);
        
        // remove whitespace from the start of the line
        $plain = preg_replace('/^ +/m', '', $plain);
        
        // Don't allow more than two blank lines in a row
        $plain = preg_replace('/\n{3,}/', "\n\n", $plain);
        
        // Convert entities &gt; back to the appropriate symbols, e.g. >
        $plain = htmlspecialchars_decode($plain);
        
        $this->message[Mailer::TEXT] = trim($plain);
    }
    
    
    /**
     * Sends an email, with message created by replacing the template text with
     * the set parameters
     * 
     * @param string $email The email address to send the message to
     * @param string $subject The subject to use for the e-mail message
     * @return mixed True if the send was a success, an array containing error
     * messages otherwise.
     */
    public function send($email, $subject = '') {
        if (!($this->message[Mailer::TEXT] or $this->message[Mailer::HTML])) {
            return array('No message text provided');
        }
        
        $errs = array();
        $this->applyParams();
        foreach ($this->message as $message) {
            if ($message == false) continue;
            
            $unmatched = get_unmatched_params($message);
            if (count($unmatched) > 0) {
                foreach ($unmatched as $match) {
                    $errs[] = 'Unmatched parameter: ' . $match;
                }
            }
        }
        if (count($errs) != 0) return $errs;
        
        if ($subject == '') {
            if (!$this->message[Mailer::HTML]) {
                return array('No HTML message set, and no subject specified');
            }
            $doc = new \DOMDocument();
            $doc->loadHTML($this->message[Mailer::HTML]);
            $head = $doc->getElementsByTagName('head')->item(0);
            $title = false;
            if ($head) {
                $title = $head->getElementsByTagName('title')->item(0);
                if ($title) $title = $title->firstChild->data;
            }
            if (!$head or !$title) {
                return array('Unable to extract subject from HTML message');
            }
            $subject = $title;
        }
        
        // Determine if the Mail mime is needed
        $need_mail_mime = false;
        if ($this->message[Mailer::HTML]) $need_mail_mime = true;
        if (count($this->normal_attachments)) $need_mail_mime = true;
        if (count($this->inline_attachments)) $need_mail_mime = true;
        
        // Send the email using the selected sending mechanism
        if ($need_mail_mime) {
            return $this->sendMailMime($email, $subject);
        } else {
            return $this->sendTraditional($email, $subject);
        }
    }
    
    
    /**
     * Sends an email, using the traditional method, which uses the PHP mail()
     * function.
     * 
     * @param string $email The email address to send the message to
     * @param string $subject The subject to use for the e-mail message
     * @return mixed True if the send was a success, an array containing error
     *         messages otherwise.
     */
    private function sendTraditional($email, $subject) {
        if (!$this->message[Mailer::TEXT]) {
            return array('No message text provided');
        }
        
        $errs = array();
        $message = $this->message[Mailer::TEXT];
        if ($this->mode == self::SEND_ONLY or $this->mode == self::DISPLAY_AND_SEND) {
            // send the message
            if (!mail($email, $subject, $message, 'From: ' . SITE_EMAIL)) {
                $errs[] = "Mail failed to send to {$email}";
                return $errs;
            }
        }
        
        if ($this->mode == self::DISPLAY_ONLY or $this->mode == self::DISPLAY_AND_SEND) {
            echo "<pre>Email: {$email}\nSubject: {$subject}\n\n{$message}</pre>";
        }
        
        return true;
    }
    
    
    /**
     * Sends an email, with message created by replacing the template text with
     * the set parameters
     * 
     * @param string $email The email address to send the message to
     * @param string $subject The subject to use for the e-mail message. By
     *        default, the title in the HTML of the message will be used.
     * @return mixed True if the send was a success, an array containing error
     *         messages otherwise.
     */
    private function sendMailMime($email, $subject) {
        $errs = array();
        $headers = array(
            'From' => SITE_EMAIL,
            'Subject' => $subject
        );
        
        if (!$this->message[Mailer::TEXT]) $this->createPlaintextFromHTML();
        
        $mime = new Mail_mime("\n");
        if ($this->message[Mailer::HTML]) {
            $mime->setHTMLBody($this->message[Mailer::HTML]);
        }
        $mime->setTXTBody($this->message[Mailer::TEXT]);
        
        // Attach normal attachments
        foreach ($this->normal_attachments as $attachment) {
            $file = @file_get_contents($attachment->getServerFilename());
            
            if ($file != false) {
                $mime->addAttachment(
                    $file,
                    $attachment->getMimetype(),
                    $attachment->getAttachFilename(),
                    false
                );
            } else {
                $err = 'Failed to attach file ' .
                    $attachment->getAttachFilename() . ' (' .
                    $attachment->getServerFilename() . ')';
                $errs[] = $err;
            }
        }
        
        // Attach inline attachments
        foreach ($this->inline_attachments as $attachment) {
            $file = @file_get_contents($attachment->getServerFilename());
            
            if ($file != false) {
                $mime->addHTMLImage(
                    $file,
                    $attachment->getMimetype(),
                    $attachment->getAttachFilename(),
                    false
                );
            } else {
                $err = 'Failed to attach file ' .
                    $attachment->getAttachFilename() . ' (' .
                    $attachment->getServerFilename() . ')';
                $errs[] = $err;
            }
        }
        
        if (count($errs) > 0) return $errs;
        
        $body = $mime->get(
            array('html_charset' => 'UTF-8', 'text_charset' => 'UTF-8')
        );
        $hdrs = $mime->headers($headers);
        
        // Not actually a static method, since Mail was written for PHP 4 :(
        $mail = @Mail::factory('mail');
        
        if ($this->mode == self::SEND_ONLY or $this->mode == self::DISPLAY_AND_SEND) {
            if (!$mail->send($email, $hdrs, $body)) {
                $errs[] = "Mail failed to send to {$email}";
                return $errs;
            }
        }
        
        if ($this->mode == self::DISPLAY_ONLY or $this->mode == self::DISPLAY_AND_SEND) {
            $body = hsc($body);
            echo "<pre>Email: {$email}\nSubject: {$subject}\n\n{$body}</pre>";
        }
        
        return true;
    }
    
    
    /**
     * Sends this message to all of the administrators
     * 
     * @param string $subject The subject to use for the e-mail message
     * @return mixed True if the send was a success, an array containing error
     *         messages otherwise.
     */
    public function sendAdmins($subject) {
        $errs = array();
        $admins = preg_split('/,\s*/', SITE_EMAILS_ADMIN);
        foreach ($admins as $email) {
            $result = $this->send($email, $subject);
            if ($result !== true) {
                $errs = array_merge($errs, $result);
            }
        }
        
        if (count($errs) == 0) return true;
        return $errs;
    }
}


/**
 * An attachment
 * 
 * Each attachment has the following details:
 * <b>Server filename</b>
 * The full path of the file on the server.
 * e.g. /home/bob/dog.png
 * 
 * <b>Attachment filename</b>
 * To be used as the name for the attached file. If the attachment is going to
 * be an inline attachment, for the CID replacements to work, the source HTML
 * should use this filename in the src attributes of the image tags, e.g.
 * dog.png
 * 
 * <b>Mime type</b>
 * The mime type of the attachment, e.g. image/png
 *
 * @author Josh, 2008-12-16
 */
class MailerAttachment {
    private $server_filename;
    private $attach_filename;
    private $mime_type;
    
    
    public function __construct($server_filename, $attach_filename, $mime_type) {
        $this->server_filename = $server_filename;
        $this->attach_filename = $attach_filename;
        $this->mime_type = $mime_type;
    }
    
    
    public function getServerFilename() {
        return $this->server_filename;
    }
    
    public function getAttachFilename() {
        return $this->attach_filename;
    }
    
    public function getMimeType() {
        return $this->mime_type;
    }
    
    
    public function setServerFilename($value) {
        $this->server_filename = $value;
    }
    
    public function setAttachFilename($value) {
        $this->attach_filename = $value;
    }
    
    public function setMimeType($value) {
        $this->mime_type = $value;
    }
    
    
    /**
     * Creates an attachment for a specified URI.
     * The specified URI, $src, can be in one of the following formats:
     *     - Relative path (e.g. blah/happy.gif)
     *     - Absolute path (e.g. /images/cool.jpg)
     *     - file.php reference (e.g. file.php?f=sdsn3a.sfdnj4.12.small)
     *
     * @param string $src The URI to get the information about
     * @return MailerAttachment The new attachment, or null if there was an
     *         error
     */
    public static function createFromURI($src) {
        global $db;
        
        // Absolute URLs get left just the way they are - unless they are HTTP
        // and point to this server, in which case the hostname is removed and
        // they are turned into relative URLs
        if (preg_match('/[A-Za-z0-9]+:\/\//', $src)) {
            $url_components = parse_url($src);
            if ($url_components['scheme'] == 'http') {
                
                // Determine www and non-www hostnames
                if (strncasecmp($_SERVER['SERVER_NAME'], 'www.', 4) == 0) {
                    $host_www = $_SERVER['SERVER_NAME'];
                    $host_no_www = substr($_SERVER['SERVER_NAME'], 4);
                } else {
                    $host_www = 'www.' . $_SERVER['SERVER_NAME'];
                    $host_no_www = $_SERVER['SERVER_NAME'];
                }
                
                // check if the target host is one of the two
                // if it is, remove the http://HOST portion
                $host = $url_components['host'];
                if ($host == $host_www or $host == $host_no_www) {
                    $src = substr($url_components['path'], 1);
                    if ($url_components['query']) {
                        $src .= '?' . $url_components['query'];
                    }
                    if (@$url_components['fragment']) {
                        $src .= '#' . $url_components['fragment'];
                    }
                    
                } else {
                    return null;
                }
                
            } else {
                return null;
            }
        }
        
        
        // file.php URLs
        if (preg_match('#^/?file\.php#', $src)) {
            if (preg_match('/f=([a-zA-Z0-9\~\_\-\.]+)/', $src, $matches)) {
                
                if (!($db instanceof Database)) $db = Database::parseXML();
                
                $mask = $matches[1];
                list($table_mask, $column_mask, $record_id) = explode('.', $mask, 3);
                
                $table = $db->getTableByMask($table_mask);
                if ($table == null) return null;
                
                $column = $table->getColumnByMask($column_mask);
                if ($table == null) return null;
                
                $storage_loc = $column->getStorageLocation();
                $filename = ROOT_PATH_FILE . $storage_loc . '/' . $mask;
                
                $q = "SELECT `{$column->getName()}`
                    FROM `{$table->getName()}`
                    WHERE ";
                $cols = $table->getPKnames();
                $id_parts = explode(',', $record_id);
                $j = 0;
                foreach ($cols as $index => $col) {
                    if (++$j != 1) $q .= ', ';
                    $id_parts[$index] = sql_enclose($id_parts[$index]);
                    $q .= "`{$col}` = {$id_parts[$index]}";
                }
                
                $res = execq($q);
                $row = fetch_assoc($res);
                $cname = $row[$column->getName()];
                
            } else {
                return null;
            }
            
        // URLs with an absolute path (e.g. /dbfiles/whee)
        } else if (strncmp($src, ROOT_PATH_WEB, strlen(ROOT_PATH_WEB)) == 0) {
            $filename = ROOT_PATH_FILE . substr($src, strlen(ROOT_PATH_WEB));
            $cname = basename($filename);
            
        // Relative URLs (e.g. whee.gif). Base is ROOT_PATH_FILE
        } else {
            $filename = ROOT_PATH_FILE . $src;
            $cname = basename($filename);
        }
        
        // Determine MIME type from cname
        $dotted_parts = explode('.', $cname);
        $ext = array_pop($dotted_parts);
        switch ($ext) {
            case 'gif': $mimetype = 'image/gif'; break;
            case 'jpg': $mimetype = 'image/jpeg'; break;
            case 'jpeg': $mimetype = 'image/jpeg'; break;
            case 'png': $mimetype = 'image/png'; break;
            case 'tif': $mimetype = 'image/tiff'; break;
            case 'tiff': $mimetype = 'image/tiff'; break;
            default: $mimetype = 'application/octet-stream'; break;
        }
        
        return new MailerAttachment($filename, $cname, $mimetype);
    }
}




/**
 * Takes an input string and replaces parameter definitions with their values.
 * 
 * The actual value replacement is done in the process_param() function. This
 * function just searches for params and then calls it each time it finds one.
 * 
 * Replacements are case-insensitive, thus {{FirstName}} and {{firstname}} and
 * {{FIRSTNAME}} are equivalent.
 * Default values can be specified in the following manner:
 * {{Key|Default value}}. If a default is not specified, the parameter is
 * ignored.
 * 
 * For example, if $params contains ['FirstName' => 'Fred']:
 * - {{FirstName}} would match the 'FirstName' key in the params array
 * - {{fIrStNaMe}} would match the 'FirstName' key in the params array
 * - {{Name}} would not match anything, so would not be replaced, and would
 *   remain as {{Name}} in the output
 * - {{LastName|Nothing}} would not match anything, so would be replaced with
 *   'Nothing'
 * 
 * @param string $input The input string
 * @param array $params The parameters to perform substitutions with
 * @return string The substituted string
 */
function bind_message_params($input, $params) {
    if (count ($params) == 0) return $input;
    
    $bind = function($matches) use ($params) {
        return bind_message_param($params, $matches[1], @$matches[3]);
    };
    
    $output = preg_replace_callback(
        '/{{([A-Za-z0-9\_\-]+)(\|(.*?))?}}/',
        $bind,
        $input
    );
    if ($output == null) return $input;
    
    return $output;
}


/**
 * Looks up, in a case-insensitive manner, a key in the $params array, and
 * returns the value associated with that key. If the key is not found,
 * $default_value is returned.
 *
 * This function is called by bind_message_params().
 *
 * @param $params array The parameters passed to bind_message_params().
 * @param $param_name string The parameter name to match.
 * @param $default_value string The default value to use. An empty string
 *        indicates that there is no default value.
 */
function bind_message_param($params, $param_name, $default_value) {
    foreach ($params as $key => $val) {
        if (strcasecmp ($key, $param_name) == 0) {
            return $params[$key];
        }
    }
    
    if ($default_value != '') {
        return $default_value;
    } else {
        return '{{' . $param_name . '}}';
    }
}


/**
 * Finds any unmatched parameters in the specified string.
 *
 * @param string $input The string to check
 * @return array The names of all unmatched parameters
 */
function get_unmatched_params($input) {
    $matches = array();
    $result = preg_match_all('/{{([A-Za-z0-9\_\-]+)(\|(.*?))?}}/', $input, $matches, PREG_PATTERN_ORDER);
    
    if ($result == 0) return [];
    return array_unique($matches[1]);
}
