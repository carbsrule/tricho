<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

use \DOMDocument;
use \DOMElement;

use Tricho\DataUi\Form;

/**
 * Stores meta-data about a column that stores some kind of string data
 * @package meta_xml
 */
abstract class StringColumn extends InputColumn {
    protected $text_filters = array();
    protected $known_filters = array('trim', 'multispace', 'tags');
    
    
    function getMaxLength() {
        return $this->sql_size;
    }
    
    
    /**
     * @author benno, 2011-08-25
     */
    function toXMLNode (DOMDocument $doc) {
        $node = parent::toXMLNode ($doc);
        $param = HtmlDom::appendNewChild ($node, 'param');
        $param->setAttribute ('name', 'filters');
        $param->setAttribute ('value', implode (',', $this->text_filters));
        return $node;
    }
    
    
    /**
     * @author benno 2011-08-15
     */
    function applyXMLNode (DOMElement $node) {
        parent::applyXMLNode ($node);
        $params = $node->getElementsByTagName ('param');
        foreach ($params as $param) {
            $name = $param->getAttribute ('name');
            if ($name == 'filters') {
                $filters = preg_split ('/, */', $param->getAttribute ('value'));
                foreach ($filters as $filter) {
                    $filter = trim ($filter);
                    if ($filter == '') continue;
                    $this->setTextFilter ($filter, true);
                }
            }
        }
    }
    
    
    /**
     * Check whether or not a filter is set for a Column.
     * @param string $filter_name Name of filter to check
     * @return bool True of filter is set; false otherwise
     */
    function isTextFilterSet ($filter_name) {
        $filter_name = strtolower ($filter_name);
        return (in_array ($filter_name, $this->text_filters));
    }
    
    
    /**
     * Set whether or not a filter will be applied to a Column.
     * @param string $filter_name Name of filter to set
     * @param bool $set True to enable filter; false to disable
     */
    function setTextFilter ($filter_name, $set) {
        $filter_name = strtolower ($filter_name);
        if (!in_array ($filter_name, $this->known_filters)) {
            throw new DataValidationException ('Unknown filter: '. $filter_name);
        }
        $filters = $this->text_filters;
        $key = array_search ($filter_name, $filters);
        
        if ($set and $key === false) {
            $filters[] = $filter_name;
        } else if (!$set and $key !== false) {
            unset ($filters[$key]);
        }
        
        $this->text_filters = $filters;
    }
    
    
    /**
     * Get an array of currently set filters.
     * @return array Names of filters set
     */
    function getTextFilters () {
        return $this->text_filters;
    }
    
    
    /**
     * Unsets all filters
     */
    function clearTextFilters () {
        $this->text_filters = array ();
    }
    
    
    /**
     * Apply text filters/conversions to a given string.
     * @param string $text String to filter/convert
     * @return string Filtered text
     */
    protected function applyTextFilters ($text) {
        $filtered = (string) $text;
        $filtered = str_replace("\t", ' ', $filtered);
        $filtered = str_replace(array("\r\n", "\r"), "\n", $filtered);
        
        if ($this->isTextFilterSet('tags')) {
            $filtered = strip_tags($filtered);
        }
        
        if ($this->isTextFilterSet('multispace')) {
            $filtered = preg_replace('/  +/', ' ', $filtered);
        }
        
        if ($this->isTextFilterSet('trim')) {
            $filtered = trim($filtered);
        }
        
        return $filtered;
    }
    
    
    /**
     * Gets the data posted from a form.
     * N.B. the filters of a StringColumn are all fairly innocuous, so the
     * original value should be updated as well. e.g. if a user submits ' bob '
     * as their name, if they are redirected back to the form, the field should
     * contain 'bob' (the filtered value), not ' bob ' (what they really typed).
     * @author benno 2011-08-29
     */
    function collateInput ($input, &$original_value) {
        $filtered = $this->applyTextFilters ($input);
        $original_value = $filtered;
        return array ($this->name => $filtered);
    }
    
    
    /**
     * @author benno 2011-08-25
     */
    function applyConfig(array $config, array &$errors) {
        $this->setCollation($config['collation']);
        $this->clearTextFilters();
        foreach ($this->known_filters as $filter) {
            if (@$config[$filter]) $this->setTextFilter($filter, true);
        }
    }
    
    
    /**
     * @author benno 2011-08-25
     */
    function getConfigArray () {
        $config = parent::getConfigArray ();
        foreach ($this->text_filters as $filter) {
            $config[$filter] = true;
        }
        return $config;
    }
    
    
    static function getConfigFormFields(array $config, $class) {
        $fields = "<label for=\"trim\"><input type=\"checkbox\" name=\"{$class}_trim\" id=\"trim\" value=\"1\"";
        if (@$config['trim']) $fields .= ' checked="checked"';
        $fields .= ">Trim leading &amp; trailing whitespace</label><br>\n";
        
        $fields .= "<label for=\"multispace\"><input type=\"checkbox\" name=\"{$class}_multispace\" id=\"multispace\" value=\"1\"";
        if (@$config['multispace']) $fields .= ' checked="checked"';
        $fields .= ">Replace repeated spaces with a single space</label><br>\n";
        
        $fields .= "<label for=\"tags\"><input id=\"tags\" type=\"checkbox\" name=\"{$class}_tags\" value=\"1\"";
        if (@$config['tags']) $fields .= ' checked="checked"';
        $fields .= ">Strip tags</label><br>\n";
        
        return $fields;
    }
}
?>
