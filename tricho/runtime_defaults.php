<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use tricho\Runtime;
$data = array(
    'site_name' => 'Unknown site',
    'xhtml' => false,
    'per_page_min' => 5,
    'per_page_max' => 5000,
    'per_page_default' => 15,
    'sql_field_break' => 50,
    'sql_error_log' => true,
    'sql_error_email' => true,
    'sql_error_email_wait' => 300,
    'sql_slow_log' => true,
    'sql_slow_email' => true,
    'sql_slow_email_wait' => 60,
    'sql_max_time_select_normal' => 1000,
    'sql_max_time_select_unoptimised' => 500,
    'sql_max_time_insert' => 200,
    'sql_max_time_update' => 200,
    'sql_max_time_delete' => 200,
    'max_failures_before_ip_lockout' => 5,
    'ip_lockout_duration' => 1440,
    'kilobyte_factor' => 1024,
    'mysql_engines' => array('InnoDB', 'MyISAM', 'ARCHIVE'),
    'sql_charsets' => array('utf8', 'latin1'),
    'sql_default_collation' => array('utf8_unicode_ci'),
    'uppercase_ampm' => false,
    'log_setup_actions' => true,
    'min_password_length' => 8,
    'max_upload_size' => 10 * 1024 * 1024,
    'clear_session_on_ip_change' => true,
    'default_jpeg_quality' => 90,
    'tinymce_plugins' => 'safari,spellchecker,table,advimage,advlink,' .
        'searchreplace,print,paste,fullscreen,noneditable,visualchars,' .
        'nonbreaking',
    'tinymce_default_buttons' => 'bold,italic,underline,strikethrough,|,' .
        'justifyleft,justifycenter,justifyright,|,' .
        'formatselect,styleselect,|,spellchecker,code' .
        '/link,unlink,anchor,image,|,bullist,numlist,outdent,indent,|,' .
        'undo,redo,cut,copy,paste,pastetext,cleanup,|,search,replace,charmap' .
        '/table,|,row_props,cell_props,|,row_before,row_after,delete_row,|,' .
        'col_before,col_after,delete_col,|,split_cells,merge_cells,|,' .
        'sub,sup,hr,removeformat,visualaid,|,fullscreen',
    'html_tags.allow' => array('a:href;hreflang;type', 'blockquote', 'br',
        'code', 'del', 'em', 'hr', 'img:src;alt', 'li', 'ol', 'p', 'strong',
        'sub', 'sup', 'table', 'tbody', 'td', 'th', 'thead', 'tr', 'ul'),
    'html_tags.replace' => array('b' => 'strong', 'i' => 'em'),
    'html_tags.deny' => array('script'),
    'master_salt' => '',
    'install_pw' => 'Tricho!',
    
    // The following fields (when found in the $_POST or $_SESSION arrays)
    // may contain sensitive information such as passwords or credit card
    // details, and thus should be masked in error report e-mails
    'private_field_names' => array(
        'askhd',
        'password', 'passwordold', 'oldpassword', 'password2',
        'confirmpassword', 'newpassword', 'newpassword2',
        'pass', 'passold', 'oldpass', 'pass2', 'confirmpass',
        'newpass', 'newpass2', 'old', 'new', 'new2',
        'creditcard', 'creditcardnumber',
        'expirymonth', 'expiryyear', 'securitycode'
    )
);

foreach ($data as $key => $value) {
    if (!Runtime::is_set($key)) {
        $read_only = true;
        if ($key == 'private_field_names') $read_only = false;
        Runtime::set($key, $value, $read_only);
    }
}
unset($data, $key, $value, $read_only);
