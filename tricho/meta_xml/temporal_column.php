<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

abstract class TemporalColumn extends Column {
    protected $has_date = false;
    protected $has_time = false;
    protected $min_year = 1000;
    protected $max_year = 9999;
    protected $year_required = false;
    protected $month_required = false;
    protected $day_required = false;
    
    
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
        $y = $m = $d = $hr = $min = $sec = 0;
        if (is_string($input)) {
            $input = trim($input);
            if ($this->has_date and $this->has_time) {
                @list($date, $time) = explode(' ', $input);
            } else if ($this->has_date) {
                $date = $input;
            } else {
                $time = $input;
            }
            if ($this->has_date) {
                @list($y, $m, $d) = @explode('-', $date);
            }
            if ($this->has_time) {
                @list($hr, $min, $sec) = @explode(':', $time);
                $ap = 'AM';
                $int_hr = (int) $hr;
                if ($int_hr >= 12) {
                    $ap = 'PM';
                    if ($int_hr > 12) $hr -= 12;
                } else if ($int_hr == 0) {
                    $hr = 12;
                }
            }
        } else {
            $y = @$input['y'];
            $m = @$input['m'];
            $d = @$input['d'];
            $ap = @$input['ap'];
            @list($hr, $min, $sec) = explode(':', @$input['t']);
        }
        $y = self::non_neg($y);
        $m = self::non_neg($m);
        $d = self::non_neg($d);
        $hr = self::non_neg($hr);
        $min = self::non_neg($min);
        $sec = self::non_neg($sec);
        if ($this->has_date) {
            $date = str_pad($y, 4, '0', STR_PAD_LEFT) . '-' .
                str_pad($m, 2, '0', STR_PAD_LEFT) . '-' .
                str_pad($d, 2, '0', STR_PAD_LEFT);
        }
        if ($this->has_time) {
            if ($ap == 'PM') {
                if ($hr < 12) $hr += 12;
            } else if ($ap == 'AM') {
                if ($hr == 12) $hr = 0;
            }
            $time = str_pad($hr, 2, '0', STR_PAD_LEFT) . ':' .
                str_pad($min, 2, '0', STR_PAD_LEFT) . ':' .
                str_pad($sec, 2, '0', STR_PAD_LEFT);
        }
        
        if ($this->has_date and $this->has_time) {
            $value = "{$date} {$time}";
        } else if ($this->has_date) {
            $value = $date;
        } else {
            $value = $time;
        }
        $original_value = $value;
        
        if ($this->has_date) {
            $min_year = $this->min_year;
            $max_year = $this->max_year;
            $mods = array('+', '-');
            if (in_array($min_year[0], $mods)) $min_year += (int) date('Y');
            if (in_array($max_year[0], $mods)) $max_year += (int) date('Y');
            $min_year = (int) $min_year;
            $max_year = (int) $max_year;
            
            if ($y < $min_year or $y > $max_year) {
                $err = "Out of allowed year range ({$min_year} to {$max_year})";
                throw new DataValidationException($err);
            }
            if ($m < 1 or $m > 12) {
                if ($m != 0 and $this->month_required) {
                    throw new DataValidationException("Invalid month");
                }
            }
        }
        if ($this->has_time) {
            if ($ap != 'AM' and $ap != 'PM') {
                throw new DataValidationException('AM/PM not selected');
            }
            if ($hr < 0 or $hr > 23) {
                throw new DataValidationException("Invalid hour");
            }
            if ($min < 0 or $min > 59) {
                throw new DataValidationException("Invalid minute");
            }
            if ($sec < 0 or $sec > 59) {
                throw new DataValidationException("Invalid second");
            }
        }
        
        // Un*x timestamp (only valid for DatetimeColumn)
        if ($this->sqltype == SQL_TYPE_INT) {
            $value = mktime($hr, $min, $sec, $m, $d, $y);
        }
        
        return array($this->name => $value);
    }
    
    
    function getInputField (Form $form, $input_value = '', $primary_key = null, $field_params = array ()) {
        static $months = array(1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep',
            10 => 'Oct', 11 => 'Nov', 12 => 'Dec');
        
        // Only applies to DatetimeColumns
        if ($this->sqltype == SQL_TYPE_INT and $input_value > 0) {
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
            $date = "<select name=\"{$fieldname}[d]\">\n";
            $date .= "<option value=\"\">D</option>\n";
            for ($i = 1; $i <= 31; ++$i) {
                $val = str_pad($i, 2, '0', STR_PAD_LEFT);
                $date .= '    <option value="' . $val . '"';
                if ($i == $d) $date .= ' selected="selected"';
                $date .= ">{$i}</option>\n";
            }
            $date .= "</select>\n";
            
            $date .= "<select name=\"{$fieldname}[m]\">\n";
            $date .= "<option value=\"\">M</option>\n";
            for ($i = 1; $i <= 12; ++$i) {
                $val = str_pad($i, 2, '0', STR_PAD_LEFT);
                $date .= '    <option value="' . $val . '"';
                if ($i == $m) $date .= ' selected="selected"';
                $date .= ">{$months[$i]}</option>\n";
            }
            $date .= "</select>\n";
            
            $date .= "<input type=\"text\" name=\"{$fieldname}[y]\" " .
                'size="3" maxlength="4"';
            if ($input_value != '') {
                $val = str_pad($y, 4, '0', STR_PAD_LEFT);
                $date .= " value=\"{$val}\"";
            }
            $date .= ">\n";
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
            $time = "<input type=\"text\" name=\"{$fieldname}[t]\" " .
                " size=\"6\" value=\"{$val}\"> ";
            foreach (array('AM', 'PM') as $half) {
                $time .= "<label for=\"{$fieldname}_{$half}\">" .
                    "<input type=\"radio\" name=\"{$fieldname}[ap]\" " .
                    "id=\"{$fieldname}_{$half}\" value=\"{$half}\"";
                if ($ap == $half) $time .= ' checked="checked"';
                $time .= ">{$half}</label>";
            }
            $time .= "\n";
        }
        
        if ($this->has_date) {
            if ($this->has_time) return "{$date} at {$time}";
            return $date;
        } else {
            return $time;
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
}
