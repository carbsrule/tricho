<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * @package constants
 */

/**
 * Version
 *
 * X._._ Major version: Major change in system architecture, or rewrite from scratch.<br>
 * _.X._ Minor version: Feature changes or additions. Upgrades may require tuning, but generally shouldn't.<br>
 * _._.X Bug fixes, spelling errors, setup or tool features, etc. Should almost always be directly upgradeable.
 */
define ('TRICHO_VERSION', '0.1.0-dev');

// Use standard settings when values haven't been explicitly specified.
if (!defined ('ADMIN_DIR')) define ('ADMIN_DIR', 'admin/');
if (!defined ('SQL_ENGINES')) define ('SQL_ENGINES', 'InnoDB, MyISAM, ARCHIVE');
if (!defined ('SQL_CHARSETS')) {
    define('SQL_CHARSETS', 'utf8mb4, utf8mb3, utf8, latin1');
}
if (!defined ('SQL_DEFAULT_COLLATION')) {
    define ('SQL_DEFAULT_COLLATION', 'utf8_unicode_ci');
}


// Table display types
define ('TABLE_DISPLAY_STYLE_ROWS', 1);
define ('TABLE_DISPLAY_STYLE_TREE', 2);

// Setup access levels - used for test_setup_login to determine authentication
define ('SETUP_ACCESS_LIMITED', 1);
define ('SETUP_ACCESS_FULL', 2);

// Table access levels - used on a per-table basis on main, main_edit, etc. to determine whether
// an admin or setup user has rights to view or edit the data stored in each table
define ('TABLE_ACCESS_ADMIN', 1);
define ('TABLE_ACCESS_SETUP_LIMITED', 2);
define ('TABLE_ACCESS_SETUP_FULL', 3);

// IP lockouts (after multiple login failures) apply for 24 hrs by default
define ('DEFAULT_LOCKOUT_PERIOD', 1440);

// Logic condition types
define ('LOGIC_TREE_COND', 1);
define ('LOGIC_TREE_AND', 2);
define ('LOGIC_TREE_OR', 3);

// Query ordering
define ('ORDER_DIR_ASC', 1);
define ('ORDER_DIR_DESC', 2);

// Query joins
define ('SQL_JOIN_TYPE_INNER', 1);
define ('SQL_JOIN_TYPE_LEFT', 2);

// options for finding query select fields from a partially built query
define ('FIND_SELECT_TYPE_COLUMN', 1);
define ('FIND_SELECT_TYPE_LITERAL', 2);
define ('FIND_SELECT_TYPE_FUNCTION', 4);
define ('FIND_SELECT_TYPE_ANY', 7);

// Different ways to display data in a <TD> on main.php or similar
// Standard display, left aligned
define ('MAIN_COL_TYPE_DEFAULT', 1);
// Right aligned
define ('MAIN_COL_TYPE_NUMERIC', 2);
// Right aligned with $
define ('MAIN_COL_TYPE_CURRENCY', 3);
// File name with possible icon in previous or next column
define ('MAIN_COL_TYPE_FILE', 4);
// Image name with possible thumbnail in previous or next column
define ('MAIN_COL_TYPE_IMAGE', 5);
// Ordering arrows (possibly, depending on previous and next rows)
define ('MAIN_COL_TYPE_ORDER', 6);
// Yes or No
define ('MAIN_COL_TYPE_BINARY', 7);

// Picture in a column
define ('MAIN_PIC_NONE', 1);
define ('MAIN_PIC_LEFT', 2);
define ('MAIN_PIC_RIGHT', 3);
define ('MAIN_PIC_ONLY_IMAGE', 4);

// Actions
define ('MAIN_PAGE_MAIN', 1);
define ('MAIN_PAGE_ACTION', 2);
define ('MAIN_PAGE_ADD', 3);
define ('MAIN_PAGE_ADD_ACTION', 4);
define ('MAIN_PAGE_EDIT', 5);
define ('MAIN_PAGE_EDIT_ACTION', 6);
define ('MAIN_PAGE_SEARCH_ACTION', 7);
define ('MAIN_PAGE_ORDER', 8);
define ('MAIN_PAGE_JOINER_ACTION', 9);
define ('MAIN_PAGE_INLINE_SEARCH', 10);
define ('MAIN_PAGE_EXPORT', 11);

// What is allowed
define ('MAIN_OPTION_ALLOW_ADD', 1);
define ('MAIN_OPTION_ALLOW_DEL', 2);
define ('MAIN_OPTION_CONFIRM_DEL', 3);
define ('MAIN_OPTION_CSV', 4);

// Alternate buttons
define ('MAIN_TEXT_ADD_BUTTON', 1);
define ('MAIN_TEXT_DEL_BUTTON', 2);
define ('MAIN_TEXT_DEL_POPUP', 3);
define ('MAIN_TEXT_CSV_BUTTON', 4);
define ('MAIN_TEXT_NO_RECORDS', 5);
define ('MAIN_TEXT_NOT_FOUND', 6);
define ('MAIN_TEXT_ADD_CONDITION', 7);
define ('MAIN_TEXT_APPLY_CONDITIONS', 8);
define ('MAIN_TEXT_CLEAR_CONDITIONS', 9);

// Logic conditions
/* These values must match the JavaScript equivalents (search_functions.js) for the filtering system to work */
define ('LOGIC_CONDITION_LIKE', 1);
define ('LOGIC_CONDITION_EQ', 2);
define ('LOGIC_CONDITION_STARTS_WITH', 3);
define ('LOGIC_CONDITION_ENDS_WITH',     4);
define ('LOGIC_CONDITION_BETWEEN', 5);
define ('LOGIC_CONDITION_LT', 6);
define ('LOGIC_CONDITION_GT', 7);
define ('LOGIC_CONDITION_LT_OR_EQ', 8);
define ('LOGIC_CONDITION_GT_OR_EQ', 9);
define ('LOGIC_CONDITION_NOT_LIKE', 10);
define ('LOGIC_CONDITION_NOT_EQ', 11);
define ('LOGIC_CONDITION_IS', 12);
define ('LOGIC_CONDITION_IN', 13);    // Not (yet) implemented on the JavaScript side
define ('LOGIC_CONDITION_NOT_BETWEEN', 14);

// Columns that link to another table will show the other table's records as either a select list
// or radio buttons
define ('LINK_FORMAT_SELECT', 1);
define ('LINK_FORMAT_RADIO', 2);
define ('LINK_FORMAT_INLINE_SEARCH', 3);

// Ordering methods for linked columns
define ('ORDER_DESCRIPTORS', 1);
define ('ORDER_LINKED_TABLE', 2);

// Default rules for HTML tags received from users via rich text input fields
if (!defined ('HTML_TAGS_ALLOW')) {
    define (
        'HTML_TAGS_ALLOW',
        'a:href;hreflang;type,blockquote,br,code,del,em,hr,img:src;alt,li,ol,'.
        'p,strong,sub,sup,table,tbody,td:align,th:align,thead,tr:valign,ul'
    );
}
if (!defined ('HTML_TAGS_REPLACE')) define ('HTML_TAGS_REPLACE', 'b=strong,i=em');
if (!defined ('HTML_TAGS_DENY'))        define ('HTML_TAGS_DENY', 'script');

// sub-action types
define ('SA_DEL_ENTIRE', 1);
define ('SA_DEL_ONE_RECORD', 2);

// Validation result status codes
define ('VALIDATION_NOT_CHANGED', 1);
define ('VALIDATION_CHANGED', 2);
define ('VALIDATION_RUBBISH', 3);

// export types
define ('EXPORT_TYPE_CSV', 1);
define ('EXPORT_TYPE_TSV', 2);

// number of records to be displayed in admin/main.php
if (!defined ('MAIN_VIEW_PER_PAGE_MIN')) define ('MAIN_VIEW_PER_PAGE_MIN', 5);
if (!defined ('MAIN_VIEW_PER_PAGE_MAX')) define ('MAIN_VIEW_PER_PAGE_MAX', 5000);

// Determine maximum uploadable file size from INI settings
$_ini_file_size_settings = array(
    ini_get('upload_max_filesize'),
    ini_get('post_max_size')
);
$_ini_file_size_values = array();

// The ini settings for upload sizes are saved as strings;
// convert to number of bytes
foreach ($_ini_file_size_settings as $_ini_max_upload) {
    $_matches = array ();
    preg_match ('/^([0-9]+)([kmg])$/i', $_ini_max_upload, $_matches);
    list($_junk, $_ini_max_upload, $_type) = $_matches;
    $_ini_max_upload = (int) $_ini_max_upload;
    if ($_ini_max_upload > 0) {
        switch (strtolower ($_type)) {
        case 'g':
            $_ini_max_upload *= 1024;
        case 'm':
            $_ini_max_upload *= 1024;
        case 'k':
            $_ini_max_upload *= 1024;
        }
    }
    $_ini_file_size_values[] = $_ini_max_upload;
}
define ('INI_MAX_UPLOAD_SIZE', min($_ini_file_size_values));
unset($_ini_file_size_settings, $_ini_file_size_values, $_ini_max_upload);
unset($_matches, $_type, $_junk);

$enforceable_data_types = array (
    'any',
    'alpha',
    'alphanum',
    'alphanum_space',
    'binary',
    'currency',
    'date_time',
    'decimal',
    'email',
    'filename',
    'integer',
    'person_name',
    'person_title',
    'phone',
    'title',
    'url',
    'username',
);
// also 'eval .*', 'person_name_part'

$recognised_SQL_types = array (
    'Integer types' => array (
        'INT',
        'TINYINT',
        'SMALLINT',
        'MEDIUMINT',
        'BIGINT',
        'BIT'
    ),

    'Decimal types' => array (
        'DECIMAL',
        'FLOAT',
        'DOUBLE'
    ),

    'Text types' => array (
        'CHAR',
        'VARCHAR',

        'TEXT',
        'TINYTEXT',
        'MEDIUMTEXT',
        'LONGTEXT'
    ),

    'Binary types' => array (
        'BINARY',
        'VARBINARY',
        'BLOB',
        'TINYBLOB',
        'MEDIUMBLOB',
        'LONGBLOB'
    ),

    'Date and time types' => array (
        'DATE',
        'DATETIME',
        'TIME'
    )
);

$image_cache_scales = array (
    'm' => array ('name' => 'Minutes', 'seconds' => 60),
    'h' => array ('name' => 'Hours',     'seconds' => 3600),
    'd' => array ('name' => 'Days',        'seconds' => 86400)
);
