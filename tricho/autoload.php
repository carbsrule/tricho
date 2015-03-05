<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

function tricho_autoload ($class_name) {
    static $root_path = null;
    if ($root_path == null) $root_path = tricho\Runtime::get('root_path');
    static $extensions = null;
    if ($extensions == null) {
        $extensions = array();
        $ext_dir = $root_path . 'tricho/ext/';
        $exts = scandir($ext_dir);
        foreach ($exts as $ext) {
            if ($ext[0] == '.') continue;
            if (is_dir($ext_dir . $ext)) $extensions[] = $ext;
        }
    }
    
    if (substr($class_name, 0, 7) == 'Tricho\\') {
        $file_name = str_replace('\\', '/', substr($class_name, 7)) . '.php';
        $file = __DIR__ . '/' . $file_name;
        if (file_exists($file)) {
            require_once $file;
            if (ends_with($class_name, 'Column')) {
                tricho\Runtime::add_column_class($class_name);
            }
            return;
        }
    }
    
    $file_name = class_name_to_file_name($class_name);
    
    switch ($class_name) {
        case 'Form':
        case 'FormManager':
        case 'FormModifier':
        case 'MainFilter':
        case 'MainOrderColumn':
        case 'MainRow':
        case 'MainTable':
        case 'MainUrlSet':
        case 'MenuHolder':
        case 'RandomString':
            require_once $root_path . 'tricho/db_interface/' . $file_name;
            break;
        
        case 'StringNumber':
            require_once $root_path . 'tricho/'. $file_name;
            break;
        
        case 'ConnManager':
            require_once $root_path . 'tricho/db/conn_manager.php';
    }
    
    if (ends_with($class_name, 'Conn')) {
        require_once $root_path . 'tricho/db/' . $file_name;
    }
    if (ends_with($class_name, 'FormItem')) {
        require_once $root_path . 'tricho/db_interface/' . $file_name;
        return;
    }
    
    if (ends_with($class_name, 'Exception')) {
        require_once $root_path . 'tricho/custom_exceptions.php';
    }
    
    foreach ($extensions as $ext) {
        $ext_path = "{$root_path}tricho/ext/{$ext}/{$file_name}";
        if (file_exists($ext_path)) {
            require_once $ext_path;
            return;
        }
    }
}


function class_name_to_file_name($name) {
    settype($name, 'string');
    $decapitalised = preg_replace_callback(
        '/[A-Z]/',
        function($matches) {
            return '_' . strtolower($matches[0]);
        },
        substr($name, 1)
    );
    return strtolower($name[0]) . $decapitalised . '.php';
}

function file_name_to_class_name($name) {
    settype($name, 'string');
    $dot_pos = strpos($name, '.');
    if ($dot_pos !== false) {
        $name = substr($name, 0, $dot_pos);
    }
    $capitalised = preg_replace_callback(
        '/_[a-z]/',
        function($matches) {
            return strtoupper($matches[0][1]);
        },
        substr($name, 1)
    );
    return strtoupper($name[0]) . $capitalised;
}

spl_autoload_register('tricho_autoload');
?>
