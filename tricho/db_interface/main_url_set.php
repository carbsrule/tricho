<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * @package main_system
 */

/**
 * Stores a list of URLs to use for a particular table when accessing the main
 * interface
 * 
 * @package main_system
 */
class MainUrlSet {
    
    private $urls;
    
    function __construct () {
        $this->urls = array (
            MAIN_PAGE_MAIN => array ('main.php', '?'),
            MAIN_PAGE_ACTION => array ('main_action.php', '?'),
            MAIN_PAGE_ADD => array ('main_add.php', '?'),
            MAIN_PAGE_ADD_ACTION => array ('main_add_action.php', '?'),
            MAIN_PAGE_EDIT => array ('main_edit.php', '?'),
            MAIN_PAGE_EDIT_ACTION => array ('main_edit_action.php', '?'),
            MAIN_PAGE_SEARCH_ACTION => array ('main_search_action.php', '?'),
            MAIN_PAGE_ORDER => array ('main_order.php', '?'),
        );
    }
    
    /**
     * Sets the URL and query string separator (& or ?) for an alternate page
     * 
     * @param int $page_id the constant definition of the page (e.g.
     *        MAIN_PAGE_EDIT)
     * @param string $page_url the URL that will be used when the administrator
     *        goes to the page
     * @param string $query_string_separator the separator that will be used
     *        for the first query string parameter. If the URL contains a
     *        question mark, this should be "&", otherwise it should be "?".
     *        If this parameter isn't provided, it is automatically determined
     *        from $page_url
     */
    function set ($page_id, $page_url, $query_string_separator = '') {
        $errors = array ();
        switch ($page_id) {
            case MAIN_PAGE_MAIN:
            case MAIN_PAGE_ACTION:
            case MAIN_PAGE_ADD:
            case MAIN_PAGE_ADD_ACTION:
            case MAIN_PAGE_EDIT:
            case MAIN_PAGE_EDIT_ACTION:
            case MAIN_PAGE_SEARCH_ACTION:
            case MAIN_PAGE_ORDER:
                $page_id = (int) $page_id;
                break;
                
            default:
                $errors[] = "Invalid page identifier {$page_id}";
        }
        
        if ($query_string_separator == '') {
            if (strpos ($page_url, '?') !== false) {
                $query_string_separator = '&';
            } else {
                $query_string_separator = '?';
            }
        }
        if ($query_string_separator != '?' and $query_string_separator != '&') {
            $errors[] = "Invalid query string separator {$query_string_separator}";
        }
        
        if (count($errors) == 0) {
            $this->urls[$page_id] = array ($page_url, $query_string_separator);
        } else {
            throw new Exception (implode ("\n", $errors));
        }
        
    }
    
    /**
     * returns the URL and separator to be used with a specific page
     * 
     * @param int $page_id the constant definition of the page (e.g.
     *        MAIN_PAGE_ORDER)
     * @return array Element 0 is the page URL, element 1 is the separator
     */
    function get ($page_id) {
        if (isset ($this->urls[$page_id])) {
            return $this->urls[$page_id];
        } else {
            throw new Exception ("Invalid page identifier {$page_id}");
        }
    }
    
}

?>
