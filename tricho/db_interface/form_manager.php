<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use tricho\Runtime;

class FormManager {
    static function loadAll() {
        $dir = Runtime::get('root_path');
        $dir .= 'tricho/data/';
        $raw_files = glob($dir . '*.form.xml');
        $files = [];
        foreach ($raw_files as $file) {
            $files[] = basename($file, '.form.xml');
        }
        return $files;
    }
    
    
    /**
     * Load a form file
     * @return mixed A Form instance, or null on failure
     */
    static function load($file) {
        $file = (string) $file;
        if (!ends_with($file, '.form.xml')) $file .= '.form.xml';
        
        // Convert relative paths
        if (!preg_match('#^([a-z]:)?/#i', $file)) {
            $file = Runtime::get('root_path') . 'tricho/data/' . $file;
        }
        
        if (!file_exists($file) or !is_file($file)) return null;
        
        $form = new Form();
        $form->load($file);
        return $form;
    }
}
