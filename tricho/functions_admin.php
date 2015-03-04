<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Meta\Table;


/**
 * Pulls apart the specified parent data (or the GET or POST specified data), and returns an array of Table => Key
 *
 * @param string $parent_data The parent data. If not specified, attempts to use GET, and then looks in POST.
 * @return array The parent values in the format [ table_name => primary_key, table_name => primary_key, ... ]
 */
function get_ancestor_pks ($parent_data = null) {
    // if no parent data has been specified, get if from the GET or POST
    if ($parent_data == null) {
        if ($_GET['p'] != null) {
            $parent_data = $_GET['p'];
        } else if ($_POST['_p'] != null) {
            $parent_data = $_POST['_p'];
        }
    }
    
    // if no parent data, return an empty array
    if ($parent_data == '') {
        return array();
    }
    
    // build the return data
    $return = array();
    $tables_keys = explode (',', $parent_data);
    foreach ($tables_keys as $table_key) {
        list ($table, $key) = explode ('.', $table_key, 2);
        $return[$table] = $key;
    }
    
    return $return;
}

/**
 * Redirects an administrator to an appropriate alternate page if one exists
 * 
 * This function is used to ensure that the extra steps to ensure security and/or data integrity which are
 * added in the alternate pages cannot be subverted by going to the default URLS. For example, if a site
 * has an alternate main page for the table Users (users_main.php) then if the administrator types the URL
 * main.php?t=Users, it will redirect them to users_main.php?t=Users. Since action pages require POST data
 * that cannot be passed on through a redirect, they will redirect to their form page instead, with a session
 * error. Note that if the requested table doesn't exist, the user will be redirected to home.php with a
 * session error.
 * 
 * @param mixed $table The table the user is accessing (a Table object, or null).
 * @param string $page The page the user is accessing (e.g. 'main', 'main_add', 'main_edit_action', etc.)
 */
function alt_page_redir($table, $page) {
    
    if (!$table instanceof Table) {
        $_SESSION[ADMIN_KEY]['err'] = 'Invalid table';
        redirect ('home.php');
    }
    
    $table_name = $table->getName ();
    
    list ($urls, $seps) = $table->getPageUrls ();
    
    $current_page = base_url ($_SERVER['REQUEST_URI']);
    $check_current_page = substr ($current_page, 0, strlen ($urls[$page]));
    
    if ($check_current_page != $urls[$page]) {
        
        switch ($page) {
            
            // redirect to main with error
            case 'main_action':
            case 'main_search_action':
                $_SESSION[ADMIN_KEY]['err'] = 'Invalid URL';
                $redir_url = "{$urls['main']}{$seps['main']}t={$table_name}";
                break;
            
            // redirect to add form with error
            case 'main_add_action':
                $_SESSION[ADMIN_KEY]['err'] = 'Invalid URL';
                $redir_url = "{$urls['main_add']}{$seps['main_add']}t={$table_name}";
                break;
            
            // ordering requires a PK reference and an order number
            case 'main_order':
                $redir_url = "{$urls['main_order']}{$seps['main_order']}t={$table_name}".
                    "&d={$_GET['d']}&id={$_GET['id']}";
                break;
            
            // edit page requires a PK reference
            case 'main_edit':
                $redir_url = "{$urls['main_edit']}{$seps['main_edit']}t={$table_name}&id={$_GET['id']}";
                break;
            
            // edit action requires a PK reference, redirect to form with error
            case 'main_edit_action':
                $_SESSION[ADMIN_KEY]['err'] = 'Invalid URL';
                $redir_url = "{$urls['main_edit']}{$seps['main_edit']}t={$table_name}&id={$_POST['_id']}";
                break;
            
            default:
                $redir_url = "{$urls[$page]}{$seps[$page]}t={$table_name}";
        }
        
        redirect ($redir_url);
    }
    
}


/**
 * Makes a string safe for use as an id (i.e. html node identifiers)
 * Note: This function does not check that the id is not already in use
 *
 * @param string $input The input string
 * @return string A safe value for an id, or an empty if none could be found
 */
function string_to_id ($input) {
    $output = trim ($input);
    if ($output == '') return '';
    
    $num = 1;
    while ($num == 1) {
        $output = preg_replace ('/^[^A-Za-z]/', '', $output, -1, $num);
        if ($num == 1) {
            $output = substr ($output, 0);
        }
    }
    if ($output == '') return '';
    
    $output = str_replace (' ', '_', $output);
    $output = preg_replace ('/[^A-Za-z0-9\-_:\.]/', '', $output);
    
    return $output;
}
?>
