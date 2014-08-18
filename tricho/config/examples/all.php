<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use tricho\Runtime;

// See /tricho/runtime_defaults.php
$data = array(
    'master_salt' => '',
);
foreach ($data as $key => $val) {
    $read_only = true;
    if ($key == 'private_field_names') $read_only = false;
    Runtime::set($key, $val, $read_only);
}
unset($data, $key, $val, $read_only);

// TODO: remove all the kludges below
if (strpos($_SERVER['PHP_SELF'], '/user_admin/') !== false) {
    define('ADMIN_KEY', 'user_admin');
    define('ADMIN_DIR', 'user_admin/');
} else {
    define('ADMIN_KEY', 'admin');
    define('ADMIN_DIR', 'admin/');
}

// The root path of this site, from the file system's perspective
// N.B. This is the same as the root_path Runtime property
define('ROOT_PATH_FILE', realpath(__DIR__ . '/../..') . '/');


# SITE DETAILS
// Default number of records to display on admin pages
define('RECORDS_PER_PAGE', 15);

// The number of characters to show in a cell for a TEXT-type field on admin/sql.php.
// Use 0 to turn off expando behaviour.
define('SQL_FIELD_BREAK', 50);

// Block IP address from logging in after 5 failures
define('IP_LOCKOUT_NUM_FAILURES', 5);

// IPs that are blocked due to failed logins remain blocked for 24 hours
define('IP_LOCKOUT_PERIOD', 1440);

// Maximum file size allowed (in bytes): can be less than or equal to, but not greater than,
// the max file upload size specified in the PHP ini file.
//define ('MAX_UPLOAD_SIZE', 2097152);

// The default factor used to convert a size specified in bytes to the appropriate order of
// magnitude, e.g. KB or MiB.
define('BYTES_TO_HUMAN_FACTOR', 1024);

// The number of records to be displayed in admin/main.php
define('MAIN_VIEW_PER_PAGE_MIN', 5);
define('MAIN_VIEW_PER_PAGE_MAX', 5000);

// Engines allowed for use in MySQL tables (comma separated list,
// in order of preference)
define('SQL_ENGINES', 'InnoDB, MyISAM, ARCHIVE');

// Charsets allowed for use in SQL tables (comma separated list,
// in order of preference)
define('SQL_CHARSETS', 'utf8, latin1');

// Default collation to use for new SQL tables
define('SQL_DEFAULT_COLLATION', 'utf8_unicode_ci');

// whether to use uppercase AM and PM in formatted times
define('UPPER_CASE_AM_PM', false);

// Whether to log setup actions or not
define('SETUP_LOG_ACTIONS', true);


#SECURITY
// generate a new blank session if the user's IP address changes
define('SESSION_NEW_IP', true);

// generate a new blank session if the user agent changes
define('SESSION_NEW_AGENT', true);

// minimum length required for password fields
define('PASSWORD_MIN_LENGTH', 8);


#MISC
// The default image quality (0% worst - 100% best) for jpeg recompression
define('DEFAULT_JPEG_QUALITY', 85);

// Which plugins to load for TinyMCE
// These are only specified once - if you don't need a particular plugin for a single field, just don't load the buttons it provides
define('TINYMCE_PLUGINS', "spellchecker,table,searchreplace,print,paste,' +\n".
    "        'fullscreen,noneditable,visualchars,nonbreaking");

// Which buttons to use by default on TinyMCE rich text fields
// Prefix a button name with # if you only want it to be shown to the setup user
define('TINYMCE_DEFAULT_BUTTONS',
    'bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,|,formatselect,styleselect'.
        ',|,spellchecker,#code'.
    '/link,unlink,anchor,image,|,bullist,numlist,outdent,indent,|,undo,redo,cut,copy,paste,pastetext,cleanup,|,'.
        'search,replace,charmap'.
    '/table,|,row_props,cell_props,|,row_before,row_after,delete_row,|,col_before,col_after,delete_col,|,'.
        'split_cells,merge_cells,|,sub,sup,hr,removeformat,visualaid,|,fullscreen'
);

// Default rules for HTML tags received from users via rich text input fields
define('HTML_TAGS_ALLOW', 'a:href;hreflang;type,blockquote,br,code,del,em,hr,img:src;alt,li,ol,'.
    'p,strong,sub,sup,table,tbody,td:align,th:align,thead,tr:valign,ul');
define('HTML_TAGS_REPLACE', 'b=strong,i=em');
define('HTML_TAGS_DENY', 'script,iframe');
