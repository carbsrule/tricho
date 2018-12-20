<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Meta;

use DataValidationException;
use DOMDocument;
use DOMElement;

use Tricho\DataUi\Form;
use Tricho\Util\HtmlDom;

abstract class TemporalColumn extends Column {
    protected $has_date = false;
    protected $has_time = false;
    protected $min_year = 1000;
    protected $max_year = 9999;
    protected $year_required = true;
    protected $month_required = true;
    protected $day_required = true;
    
    
    static function non_neg($int) {
        $int = (int) $int;
        if ($int < 0) $int = 0;
        return $int;
    }
    
    function toXMLNode (DOMDocument $doc) {
        $node = parent::toXMLNode($doc);
        if ($this->has_date) {
            $node->setAttribute('min_year', $this->min_year);
            $node->setAttribute('max_year', $this->max_year);
        }
        return $node;
    }
    
    function applyXMLNode(DOMElement $node) {
        parent::applyXMLNode($node);
        if ($this->has_date) {
            $this->min_year = $node->getAttribute('min_year');
            $this->max_year = $node->getAttribute('max_year');
        }
    }
    
    
    function getConfigArray() {
        $config = parent::getConfigArray();
        if (!$this->has_date) return $config;
        
        $config['min_year'] = $this->min_year;
        $config['max_year'] = $this->max_year;
        return $config;
    }
    
    function applyConfig(array $config, array &$errors) {
        if (!$this->has_date) return;
        
        $config['max_year'] = trim($config['max_year']);
        $config['min_year'] = trim($config['min_year']);
        if ($config['max_year'] == '' or $config['min_year'] == '') {
            $errors[] = 'You must specify a year range for date columns';
            return;
        }
        
        $min_matches = preg_match('/^[-+]?\d+$/', $config['min_year']);
        $max_matches = preg_match('/^[-+]?\d+$/', $config['max_year']);
        if (!$min_matches or !$max_matches) {
            $errors[] = 'Invalid date range specified';
            return;
        }
        
        $max_year = (int) $config['max_year'];
        $min_year = (int) $config['min_year'];
        if (in_array($config['min_year'][0], array('+', '-'))) {
            $min_year += (int) date('Y');
        }
        if (in_array($config['max_year'][0], array('+', '-'))) {
            $max_year += (int) date('Y');
        }
        
        if ($min_year > $max_year) {
            $errors[] = 'Invalid date range; years appear to be in reverse';
            return;
        }
        
        $this->min_year = $config['min_year'];
        $this->max_year = $config['max_year'];
    }
    
    function collateInput ($input, &$original_value) {
        // Convert string (e.g. from database source) to expected array
        if (is_string($input)) {
            $str = trim($input);
            $input = [];
            if ($this->has_date and $this->has_time) {
                @list($date, $time) = explode(' ', $str);
            } else if ($this->has_date) {
                $date = $str;
            } else {
                $time = $str;
            }
            if ($this->has_date) {
                $parts = @explode('-', $date);
                $input['y'] = @$parts[0];
                $input['m'] = @$parts[1];
                $input['d'] = @$parts[2];
            }
            if ($this->has_time) {
                $parts = @explode(':', $time);
                $ap = 'AM';
                $hr = (int) @$parts[0];
                if ($hr >= 12) {
                    $ap = 'PM';
                    if ($hr > 12) $hr -= 12;
                } else if ($hr == 0) {
                    $hr = 12;
                }
                $min = (int) @$parts[1];
                $input['t'] = implode(':', [$hr, $min]);
            }
        }

        // Do actual validation/collation
        if ($this->has_date and $this->has_time) {
            $original_date = '';
            $original_time = '';
            $errors = [];
            try {
                $date = $this->collateDate($input, $original_date);
            } catch (ValidationException $ex) {
                $errors[] = $ex->getMessage();
            }
            try {
                $time = $this->collateTime($input, $original_time);
            } catch (ValidationException $ex) {
                $errors[] = $ex->getMessage();
            }

            if ($original_date === null and $original_time === null) {
                $original_value = null;
            } else {
                $original_value = "{$original_date} {$original_time}";
            }
            if (count($errors) > 0) {
                throw new ValidationException(implode('; ', $errors));
            }

            if ($date === null and $time === null) {
                $value = null;
            } else {
                if ($date === null) $date = '0000-00-00';
                if ($time === null) $time = '00:00:00';

                if ($this->sqltype == 'INT') {
                    // UN*X timestamp
                    @list($y, $m, $d) = explode('-', $time);
                    @list($hr, $min, $sec) = explode(':', $time);
                    $value = mktime($hr, $min, $sec, $m, $d, $y);
                } else {
                    $value = "{$date} {$time}";
                }
            }
        } else if ($this->has_date) {
            $value = $this->collateDate($input, $original_value);
        } else {
            $value = $this->collateTime($input, $original_value);
        }
        return [$this->name => $value];
    }


    /**
     * Collate a date field into either YYYY-MM-DD or NULL
     * @param array $input
     * @param string $original_value
     * @return string|null
     */
    protected function collateDate(array $input, &$original_value)
    {
        $y = @$input['y'];
        $m = @$input['m'];
        $d = @$input['d'];

        if ($y == '' and $m == '' and $d == '') {
            $original_value = null;

            if ($this->isMandatory()) {
                throw new DataValidationException('Required field');
            }
            if ($this->isNullAllowed()) {
                $value = null;
            } else {
                $value = '0000-00-00';
            }
            return $value;
        }

        $y = self::non_neg($y);
        $m = self::non_neg($m);
        $d = self::non_neg($d);

        $original_value = "{$y}-{$m}-{$d}";

        $min_year = $this->min_year;
        $max_year = $this->max_year;
        $mods = ['+', '-'];
        if (in_array($min_year[0], $mods)) $min_year += (int) date('Y');
        if (in_array($max_year[0], $mods)) $max_year += (int) date('Y');
        $min_year = (int) $min_year;
        $max_year = (int) $max_year;

        if ($y < $min_year or $y > $max_year) {
            if ($this->year_required or $y != 0) {
                $err = "Out of allowed year range";
                $err .= " ({$min_year} to {$max_year})";
                throw new DataValidationException($err);
            }
        }
        if ($m < 1 or $m > 12) {
            if ($m != 0 or $this->month_required) {
                throw new DataValidationException("Invalid month");
            }
        }

        if ($m == 0) {
            $max_d = 31;
        } else {
            $month_check = gmmktime(12, 0, 0, $m, 1, $y);
            $max_d = (int) gmdate('t', $month_check);

            // MySQL doesn't allow 0000-02-29
            if ($y == 0 and $m == 2) $max_d = 28;
        }
        if ($d < 1 or $d > $max_d) {
            if ($d != 0 or $this->day_required) {
                throw new DataValidationException("Invalid day");
            }
        }

        return str_pad($y, 4, '0', STR_PAD_LEFT) . '-' .
            str_pad($m, 2, '0', STR_PAD_LEFT) . '-' .
            str_pad($d, 2, '0', STR_PAD_LEFT);
    }


    /**
     * Collate a time field into either hh:mm:ss or NULL
     * @param array $input
     * @param string $original_value
     * @return string|null
     */
    protected function collateTime(array $input, &$original_value)
    {
        $hr_min = (string) $input['t'];
        $ap = @$input['ap'];

        $original_value = $hr_min;
        if ($hr_min == '') {
            $original_value = null;
            if ($this->isNullAllowed()) {
                return null;
            } else if ($this->isMandatory()) {
                throw new DataValidationException('Time required');
            }
            $hr_min = '00:00:00';
        }

        @list($hr, $min, $sec) = explode(':', $hr_min);

        $hr = (int) $hr;
        $min = (int) $min;
        $sec = (int) $sec;

        if ($ap != 'AM' and $ap != 'PM') {
            throw new DataValidationException('AM/PM not selected');
        }
        if ($hr < 0 or $hr > 23) {
            throw new DataValidationException('Invalid hour');
        }
        if ($min < 0 or $min > 59) {
            throw new DataValidationException('Invalid minute');
        }
        if ($sec < 0 or $sec > 59) {
            throw new DataValidationException('Invalid second');
        }

        if ($ap == 'PM' and $hr != 12) {
            $hr += 12;
        }

        $time = str_pad($hr, 2, '0', STR_PAD_LEFT) . ':' .
            str_pad($min, 2, '0', STR_PAD_LEFT) . ':' .
            str_pad($sec, 2, '0', STR_PAD_LEFT);

        $original_value = $time;
        return $time;
    }


    function attachInputField(Form $form, $input_value = '', $primary_key = null, $field_params = array()) {
        static $months = array(1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep',
            10 => 'Oct', 11 => 'Nov', 12 => 'Dec');
        $p = self::initInput($form);
        
        // Only applies to DatetimeColumns
        if ($this->sqltype == 'INT' and preg_match('/^[0-9]+$/', $input_value)) {
            $input_value = date('Y-m-d H:i:s', $input_value);
        }
        
        if ($this->has_date and $this->has_time) {
            @list($date, $time) = explode(' ', $input_value);
        } else if ($this->has_date) {
            $date = $input_value;
        } else {
            $time = $input_value;
        }
        $fieldname = $this->getPostSafeName();
        
        if ($this->has_date) {
            @list($y, $m, $d) = explode('-', $date);
            $y = (int) $y;
            $m = (int) $m;
            $d = (int) $d;
            
            $select = HtmlDom::appendNewChild($p, 'select');
            $select->setAttribute('class', 'day');
            $select->setAttribute('name', "{$fieldname}[d]");
            $select->setAttribute('onchange', "date_change(this);");
            $attrs = array('value' => '');
            $option = HtmlDom::appendNewChild($select, 'option', $attrs);
            HtmlDom::appendNewText($option, 'D');
            for ($i = 1; $i <= 31; ++$i) {
                $val = str_pad($i, 2, '0', STR_PAD_LEFT);
                $attrs = array('value' => $val);
                if ($i == $d) $attrs['selected'] = 'selected';
                $option = HtmlDom::appendNewChild($select, 'option', $attrs);
                HtmlDom::appendNewText($option, $i);
            }
            HtmlDom::appendNewText($p, ' ');
            
            $select = HtmlDom::appendNewChild($p, 'select');
            $select->setAttribute('class', 'month');
            $select->setAttribute('name', "{$fieldname}[m]");
            $select->setAttribute('onchange', "date_change(this);");
            $attrs = array('value' => '');
            $option = HtmlDom::appendNewChild($select, 'option', $attrs);
            HtmlDom::appendNewText($option, 'M');
            for ($i = 1; $i <= 12; ++$i) {
                $val = str_pad($i, 2, '0', STR_PAD_LEFT);
                $attrs = array('value' => $val);
                if ($i == $m) $attrs['selected'] = 'selected';
                $option = HtmlDom::appendNewChild($select, 'option', $attrs);
                HtmlDom::appendNewText($option, $months[$i]);
            }
            HtmlDom::appendNewText($p, ' ');
            
            $attrs = array(
                'type' => 'text',
                'name' => "{$fieldname}[y]",
                'class' => 'year',
                'maxlength' => 4,
                'placeholder' => 'YYYY',
                'onkeyup' => 'date_change(this);',
            );
            if ($input_value != '') {
                $attrs['value'] = str_pad($y, 4, '0', STR_PAD_LEFT);
                if ($attrs['value'] == '0000') $attrs['value'] = '';
            }
            HtmlDom::appendNewChild($p, 'input', $attrs);
            
            $weekday = '';
            if ($y > 0 and $m > 0 and $d > 0) {
                $weekday = '(' . date('l', mktime(12, 0, 0, $m, $d, $y)) . ')';
            }
            HtmlDom::appendNewText($p, ' ');
            $span = HtmlDom::appendNewChild($p, 'span', ['class' => 'weekday']);
            HtmlDom::appendNewText($span, $weekday);
        }
        
        if ($this->has_time) {
            @list($hr, $min, $sec) = explode(':', $time);
            $hr = (int) $hr;
            $min = (int) $min;
            $sec = (int) $sec;
            
            if ($input_value != '') {
                $ap = 'AM';
                if ($hr >= 12) {
                    $ap = 'PM';
                    if ($hr != 12) $hr -= 12;
                } else if ($hr == 0) {
                    $hr = 12;
                }
                $val = str_pad($hr, 2, '0', STR_PAD_LEFT) . ':' .
                    str_pad($min, 2, '0', STR_PAD_LEFT) . ':' .
                    str_pad($sec, 2, '0', STR_PAD_LEFT);
            } else {
                $val = '';
                $ap = '';
            }
            
            if ($this->has_date) {
                HtmlDom::appendNewText($p, ' at ');
            }
            $attrs = array(
                'type' => 'text',
                'name' => "{$fieldname}[t]",
                'class' => 'hour_min',
                'value' => $val
            );
            HtmlDom::appendNewChild($p, 'input', $attrs);
            
            foreach (array('AM', 'PM') as $half) {
                $attrs = array('for' => "{$fieldname}_{$half}");
                $label = HtmlDom::appendNewChild($p, 'label', $attrs);
                $attrs = array(
                    'type' => 'radio',
                    'name' => "{$fieldname}[ap]",
                    'id' => "{$fieldname}_{$half}",
                    'value' => $half
                );
                if ($ap == $half) $attrs['checked'] = 'checked';
                HtmlDom::appendNewChild($label, 'input', $attrs);
                HtmlDom::appendNewText($label, $half);
            }
        }
    }
    
    
    function hasDate() {
        return $this->has_date;
    }
    
    
    function getMinYear() {
        return $this->min_year;
    }
    
    
    function getMaxYear() {
        return $this->max_year;
    }
    
    
    function getInfo() {
        if (!$this->has_date) return '';
        return $this->min_year . ' &#8596; ' . $this->max_year;
    }
}
