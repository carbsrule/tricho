<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

function show_setup_user_option ($english, $opt) {
    
    $current_status = ($_SESSION['setup']["view_{$opt}"]? 'y': 'n');
    $new_status = ($current_status == 'y'? 'n': 'y');
    
    $url = $_SERVER['REQUEST_URI'];
    $replacements = array (
        "?{$opt}={$current_status}&" => '?',
        "?{$opt}={$current_status}" => '',
        "&{$opt}={$current_status}" => ''
    );
    
    $url = htmlspecialchars (str_replace (array_keys ($replacements), $replacements, $url));
    $url .= ((strpos ($url, '?') === false)? "?{$opt}={$new_status}": "&amp;{$opt}={$new_status}");
    
    echo "<label class=\"label_plain\" for=\"admin_opt_{$opt}\">";
    echo "<input id=\"admin_opt_{$opt}\" type=\"checkbox\" value=\"y\" onclick=\"window.location = '",
        get_proto_host (), $url, "';\"";
    if ($current_status == 'y') {
        echo ' checked="checked"';
    }
    echo ">{$english}</label>\n";
}

if (test_setup_login (false, SETUP_ACCESS_FULL)) {
    
    if ($_GET['q'] == 'y') {
        $_SESSION['setup']['view_q'] = true;
    } else if ($_GET['q'] == 'n') {
        $_SESSION['setup']['view_q'] = false;
    }
    
    if ($_GET['c'] == 'y') {
        $_SESSION['setup']['view_c'] = true;
    } else if ($_GET['c'] == 'n') {
        $_SESSION['setup']['view_c'] = false;
    }
}

if (test_setup_login (false, SETUP_ACCESS_LIMITED)) {
    
    if ($_GET['h'] == 'y') {
        $_SESSION['setup']['view_h'] = true;
    } else if ($_GET['h'] == 'n') {
        $_SESSION['setup']['view_h'] = false;
    }
    
}
?>
