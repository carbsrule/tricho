<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\DataUi;

use \DOMDocument;
use \Exception;

use Tricho\Runtime;
use Tricho\Util\HtmlDom;

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
    
    
    /**
     * @param Form $form
     * @param bool $return True to return a string instead of saving to a file
     */
    static function save(Form $form, $return = false) {
        $doc = new DOMDocument();
        $doc->preserveWhiteSpace = false;
        
        
        $comments = [];
        $file = $form->getFile();
        if ($file[0] != '/') {
            $file = Runtime::get('root_path') . 'tricho/data/' . $file;
        }
        $file .= '.form.xml';
        if (file_exists($file)) {
            $doc->load($file, true);
            $forms = $doc->getElementsByTagName('form');
            $form_el = $forms->item(0);
            if (!$form_el) throw new Exception('Invalid extant file');
            $items = $form_el->getElementsByTagName('items');
            if ($items->length > 0) {
                $items = $items->item(0);
                while ($items->hasChildNodes()) {
                    // Preserve comments
                    $last = $items->lastChild;
                    $last_name = $last->getAttribute('name');
                    if ($last->hasChildNodes()) {
                        $last_child = $last->firstChild;
                        if ($last_child->nodeType == XML_COMMENT_NODE) {
                            $comments[$last_name] = $last_child->data;
                        }
                    }
                    $items->removeChild($items->lastChild);
                }
            }
        } else {
            $form_el = HtmlDom::appendNewChild($doc, 'form');
            $items = HtmlDom::appendNewChild($form_el, 'items');
        }
        
        $form_el->setAttribute('table', $form->getTable()->getName());
        $modifier = $form->getModifier();
        if ($modifier !== null) {
            $form_el->setAttribute('modifier', get_class($modifier));
        } else {
            $form_el->removeAttribute('modifier');
        }
        
        foreach ($form->getItems() as $item) {
            if (!($item instanceof ColumnFormItem)) {
                throw new Exception("Can't process " . get_class($item));
            }
            
            $col = $item->getColumn();
            $attrs = ['name' => $col->getName()];
            $label = $item->getLabel();
            if ($label) $attrs['label'] = $label;
            $attrs['apply'] = $item->getApply();
            $item_el = HtmlDom::appendNewChild($items, 'field', $attrs);
            
            $comment = @$comments[$col->getName()];
            if ($comment) $item_el->appendChild($doc->createComment($comment));
        }
        
        $doc->formatOutput = true;
        $contents = @$doc->saveXML();
        
        // use 4 spaces instead of 2 for indenting
        if (strpos($contents, "\n  <items") !== false) {
            $contents = preg_replace('/^( +)</m', '$1$1<', $contents);
        }
        
        if ($return) return $contents;
        
        $bytes_written = file_put_contents($file, $contents);
        return $bytes_written;
    }
    
    
    static function delete(Form $form) {
        $file = $form->getFile() . '.form.xml';
        if (!file_exists($file)) return;
        if (!unlink($file)) {
            throw new Exception('Failed to delete ' . basename($file));
        }
    }
}
