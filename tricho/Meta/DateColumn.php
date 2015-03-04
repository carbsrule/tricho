<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

use Tricho\Util\HtmlDom;

class DateColumn extends TemporalColumn {
    protected $has_date = true;
    
    
    static function getAllowedSqlTypes() {
        return array('DATE');
    }
    
    
    static function getDefaultSqlType() {
        return 'DATE';
    }
    
    
    static function getConfigFormFields(array $config, $class) {
        $fields = parent::getConfigFormFields($config, $class);
        
        $fields .= '<p>Year range: ';
        $fields .= "<input type=\"text\" name=\"{$class}_min_year\" ";
        $fields .= 'style="width:2.5em;" value="' . hsc(@$config['min_year']) .
            '"> to ';
        $fields .= "<input type=\"text\" name=\"{$class}_max_year\" ";
        $fields .= 'style="width:2.5em;" value="' . hsc(@$config['max_year']) .
            '">';
        return $fields;
    }
}
