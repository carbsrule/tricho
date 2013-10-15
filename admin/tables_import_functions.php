<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

function get_xml_table (DOMDocument $doc, $table_name) {
    $tables = $doc->getElementsByTagName ('table');
    for ($table_num = 0; $table_num < $tables->length; $table_num++) {
        $table = $tables->item ($table_num);
        if ($table->getAttribute ('name') == $table_name) return $table;
    }
    return null;
}

function get_xml_columns (DOMNode $table) {
    $return_columns = array ();
    $columns = $table->getElementsByTagName ('column');
    for ($column_num = 0; $column_num < $columns->length; $column_num++) {
        $column = $columns->item ($column_num);
        $return_columns[$column->getAttribute ('name')] = $column;
    }
    return $return_columns;
}

/**
 * @return array the indexes. The keys are the index names, and the values are arrays containing the
 *     columns in the index, in order.
 */
function get_xml_indexes (DOMNode $table) {
    $return_indexes = array ();
    $indexes = $table->getElementsByTagName ('indices')->item (0)->getElementsByTagName ('index');
    for ($index_num = 0; $index_num < $indexes->length; $index_num++) {
        $index = $indexes->item ($index_num);
        $return_indexes[strtoupper ($index->getAttribute ('name'))] = preg_split ('/,\s*/', $index->getAttribute ('columns'));
    }
    return $return_indexes;
}

/**
 * Replaces a table property in an existing XML definition with an updated property definition of the same node from a new XML file. This function is used to import views (main, add_edit, export), ordering (vieworder), indices, and the row_identifier of a table.
 * 
 * @param DOMElement $extant_table The table defined in the existing XML
 * @param DOMElement $new_table The table defined in the new XML, the properties of which will be imported
 *     into the existing table definition
 * @param string $node_name The name of the node that describes the property that will be imported
 *     (e.g. main, add_edit, ...)
 * 
 * @return bool true if the replacement was successful, false otherwise.
 * 
 * @author benno, 2008-11-09
 */
function import_and_overwrite_node (DOMElement $extant_table, DOMElement $new_table, $node_name) {
    
    $new_nodes = $new_table->getElementsByTagName ($node_name);
    if (!$new_node = $new_nodes->item (0)) return false;
    
    $new_node = $extant_table->ownerDocument->importNode ($new_node, true);
    
    $old_nodes = $extant_table->getElementsByTagName ($node_name);
    if ($old_node = $old_nodes->item (0)) {
        if ($extant_table->replaceChild ($new_node, $old_node)) return true;
    } else if ($extant_table->appendChild ($new_node)) {
        return true;
    }
    
    return false;
    
}

/**
 * Imports a node used for setting up alternate behaviour, i.e. alt_page and alt_button nodes
 * 
 * @param DOMElement $extant_table The table defined in the existing XML
 * 
 * @return bool true if the replacement was successful, false otherwise.
 * 
 * @author benno, 2008-11-09
 */
function set_alt (DOMElement $extant_table, DOMElement $new_node) {
    
    $node_name = $new_node->nodeName;
    $original_value = $new_node->getAttribute ('old');
    $alternate_value = $new_node->getAttribute ('new');
    
    $old_nodes = $extant_table->getElementsByTagName ($node_name);
    foreach ($old_nodes as $old_node) {
        if ($old_node->getAttribute ('old') == $original_value) {
            $old_node->setAttribute ('new', $alternate_value);
            return true;
        }
    }
    
    $new_node = $extant_table->ownerDocument->importNode ($new_node, true);
    if ($extant_table->appendChild ($new_node)) {
        return true;
    } else {
        return false;
    }
    
}

?>
