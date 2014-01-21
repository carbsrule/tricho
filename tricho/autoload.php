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
    
    if (substr($class_name, 0, 7) == 'tricho\\') {
        $file = $root_path . str_replace('\\', '/', $class_name) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
    
    $file_name = class_name_to_file_name($class_name);
    
    $is_column = false;
    if (substr ($class_name, -6) == 'Column') {
        $is_column = true;
        if (strpos ($class_name, 'AliasedColumn') !== false) $is_column = false;
        if (strpos ($class_name, 'OrderColumn') !== false) $is_column = false;
        if (strpos ($class_name, 'QueryColumn') !== false) $is_column = false;
    }
    if ($is_column) {
        require_once $root_path . 'tricho/data_objects.php';
        
        foreach ($extensions as $ext) {
            $ext_path = "{$root_path}tricho/ext/{$ext}/{$file_name}";
            if (file_exists($ext_path)) {
                require_once $ext_path;
                tricho\Runtime::add_column_class($class_name);
                return;
            }
        }
        
        $tricho_path = $root_path . 'tricho/meta_xml/' . $file_name;
        if (!file_exists($tricho_path)) return;
        require_once $tricho_path;
        tricho\Runtime::add_column_class($class_name);
        return;
    }
    
    switch ($class_name) {
        case 'Database':
        case 'Table':
        case 'Column':
        case 'Link':
            // TODO: remove this one day
            require_once $root_path . 'tricho/data_objects.php';
            // there is no break here for a reason
            
        case 'ViewItem':
        case 'HeadingViewItem':
        case 'ColumnViewItem':
        case 'FunctionViewItem':
        case 'IncludeViewItem':
        case 'UploadedFile':
        case 'UploadedImage':
            require_once $root_path . 'tricho/meta_xml/' . $file_name;
            break;
        
        case 'Form':
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
        
        case 'AliasedColumn':
        case 'AliasedField':
        case 'AliasedTable':
        case 'LogicConditionNode':
        case 'LogicOperatorNode':
        case 'LogicTreeNode':
        case 'LogicTree':
        case 'OrderColumn':
        case 'QueryFieldList':
        case 'QueryFieldLiteral':
        case 'QueryField':
        case 'QueryFunction':
        case 'QueryJoin':
        case 'QueryTableList':
        case 'QueryTable':
        case 'DateTimeQueryColumn':
        case 'QueryModifier':
            require_once $root_path . 'tricho/query/' . $file_name;
            break;
        
        case 'UploadFailedException':
        case 'ImageResizeException':
        case 'InvalidSizeException':
        case 'FileNotWriteableException':
        case 'InvalidColumnConfigException':
        case 'DataValidationException':
        case 'QueryException':
            require_once $root_path . 'tricho/custom_exceptions.php';
            break;
        
        case 'StringNumber':
        case 'HtmlDom':
            require_once $root_path . 'tricho/'. $file_name;
            break;
        
        case 'ConnManager':
            require_once $root_path . 'tricho/db/conn_manager.php';
    }
    
    if (ends_with($class_name, 'Conn')) {
        require_once $root_path . 'tricho/db/' . $file_name;
    }
    if (ends_with($class_name, 'Query')) {
        require_once $root_path . 'tricho/query/' . $file_name;
    }
    
    foreach ($extensions as $ext) {
        $ext_path = "{$root_path}tricho/ext/{$ext}/{$file_name}";
        if (file_exists($ext_path)) {
            require_once $ext_path;
            return;
        }
    }
}


function class_name_to_file_name ($name) {
    settype ($name, 'string');
    return strtolower ($name[0]). preg_replace ('/([A-Z])/e', "'_'.strtolower('$1')", substr ($name, 1)). '.php';
}

function file_name_to_class_name ($name) {
    settype ($name, 'string');
    $dot_pos = strpos ($name, '.');
    if ($dot_pos !== false) {
        $name = substr ($name, 0, $dot_pos);
    }
    return strtoupper ($name[0]). preg_replace ('/(_[a-z])/e', "strtoupper(substr('$1',1))", substr ($name, 1));
}

spl_autoload_register('tricho_autoload');
?>
