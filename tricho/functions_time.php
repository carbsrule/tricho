<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * @package functions
 * @subpackage time
 */


/**
 * Creates HTML for a set of 3 drop-down boxes, for day, month and year.
 *
 * @param array $params An array containing any of the following options:<br>
 * <b>TextYear</b>: Whether to use a text input field instead of a drop-down list for the year.<br>
 * <b>MinYear</b>, <b>MaxYear</b>: The minimum/maximum year to display on the year drop-down.
 * -x and +x may be used to indicate x years from now. Both default to the current year.<br>
 * <b>DateSelected</b>: The MySQL formatted date that will be pre-selected on the drop-down lists.
 * By default, no date is selected.<br>
 * <b>Calendar</b>: Whether to include the calendar pop-up for date selection (default is 1 = on).<br>
 * <b>DateDivider</b>: The divider used between the 3 drop-downs. Defaults to /<br>
 * <b>DateTimeDivider</b>: The divider used between the date part (DD/MM/YYYY) and the time part (HH:mm:ss).
 * Defaults to ' &nbsp; '<br>
 * <b>TimeDivider</b>: The divider to use between the hour, minute and second text fields.
 * Defaults to :<br>
 * <b>Prefix</b>: The prefix to use for all the POST variable names (eg the prefix 'MyDate' would generate
 * fields 'MyDate[d]', 'MyDate[m]', etc.). By default, the prefix 'TmogDate' is used.<br>
 * <b>IncludeTime</b>: Whether or not to include text fields for time data (ie: is this a DATETIME field?)
 * Values are 0 for no, 1 for yes. Defaults to no.<br>
 * <b>IncludeSeconds</b>: Whether or not to include a text field for the second data in the time field.
 * Values are 0 for no, 1 for yes. Defaults to no.<br>
 * <b>TimeOnly</b>: This parameter should be set to 1 if and only if you are using a TIME field,
 * rather than DATE or DATETIME<br>
 * <b>OnChange</b>: a single JavaScript function to call when any of the drop-down lists or text boxes change
 *
 * @return string HTML for the necessary drop-down lists and/or text boxes.
 * @deprecated Use a TemporalColumn instead
 */
function tricho_date_select ($params) {
    
    /* $month = array ('Unknown', 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September',
        'October', 'November', 'December'); */
    $month = array ('Unknown', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'June', 'July', 'Aug', 'Sept',
        'Oct', 'Nov', 'Dec');
    
    if ($params['Prefix'] == '') {
        $params['Prefix'] = 'TmogDate';
    }
    
    if ($params['MinYear'] == '' or $params['MinYear'] == '0') {
        $params['MinYear'] = date ('Y');
    } else if (substr ($params['MinYear'], 0, 1) == '+') {
        $modifier = substr ($params['MinYear'], 1);
        $params['MinYear'] = date ('Y') + $modifier;
    } else if (substr ($params['MinYear'], 0, 1) == '-') {
        $modifier = substr ($params['MinYear'], 1);
        $params['MinYear'] = date ('Y') - $modifier;
    }
    
    if ($params['MaxYear'] == '' or $params['MaxYear'] == '0') {
        $params['MaxYear'] = date ('Y');
    } else if (substr ($params['MaxYear'], 0, 1) == '+') {
        $modifier = substr ($params['MaxYear'], 1);
        $params['MaxYear'] = date ('Y') + $modifier;
    } else if (substr ($params['MaxYear'], 0, 1) == '-') {
        $modifier = substr ($params['MaxYear'], 1);
        $params['MaxYear'] = date ('Y') - $modifier;
    }
    
    $params['d'] = '';
    $params['m'] = '';
    $params['y'] = '';
    $params['hr'] = '';
    $params['mn'] = '';
    $params['sc'] = '';
    
    if ($params['DateSelected'] == 'NOW') {
        $params['d'] = date ('d');
        $params['m'] = date ('m');
        $params['y'] = date ('Y');
    } else if ($params['DateSelected'] == 'NOW+') {
        $params['d'] = date ('d');
        $params['m'] = date ('m');
        $params['y'] = date ('Y');
        $params['hr'] = date ('G');
        $params['mn'] = date ('i');
        $params['sc'] = date ('s');
    } else if ($params['DateSelected'] != '') {
        if ($params['TimeOnly'] != 1) {
            list ($date, $time) = explode (' ', $params['DateSelected']);
        } else {
            $time = $params['DateSelected'];
        }
        list ($params['y'], $params['m'], $params['d']) = @explode ('-', $date);
        list ($params['hr'], $params['mn'], $params['sc']) = @explode (':', $time);
    }
    
    if ($params['Calendar'] != '0') $params['Calendar'] = 1;
    if ($params['DateDivider'] == '') $params['DateDivider'] = '/';
    if ($params['DateTimeDivider'] == '') $params['DateTimeDivider'] = ' &nbsp; ';
    if ($params['TimeDivider'] == '') $params['TimeDivider'] = ':';
    
    if ($params['TimeOnly'] != 1) {
        $out_txt = "<select name=\"{$params['Prefix']}[d]\"";
        if ($params['OnChange'] != '') $out_txt .= " onchange=\"{$params['OnChange']}\"";
        $out_txt .= ">\n";
        $out_txt .= "<option value=\"\">D</option>\n";
        for ($i = 1; $i <= 31; $i++) {
            $out_txt .= '<option value="' . str_pad ($i, 2, '0', STR_PAD_LEFT) . '"';
            if ($params['d'] == $i) $out_txt .= ' selected="selected"';
            $out_txt .= "> {$i}</option>\n";
        }
        $out_txt .= "</select>";
        
        $out_txt .= $params['DateDivider'];
        
        $out_txt .= "<select name=\"{$params['Prefix']}[m]\"";
        if ($params['OnChange'] != '') $out_txt .= " onchange=\"{$params['OnChange']}\"";
        $out_txt .= ">\n";
        $out_txt .= "<option value=\"\">M</option>\n";
        for ($i = 1; $i <= 12; $i++) {
            $out_txt .= '<option value="' . str_pad ($i, 2, '0', STR_PAD_LEFT) . '"';
            if ($params['m'] == $i) $out_txt .= ' selected="selected"';
            $out_txt .= "> {$month[$i]}</option>\n";
        }
        $out_txt .= "</select>";
        
        $out_txt .= $params['DateDivider'];
        
        if ($params['TextYear'] == 1) {
            $out_txt .= "<input type=\"text\" size=\"4\" maxlength=\"4\" name=\"{$params['Prefix']}[y]\" value=\"{$params['y']}\"";
            if ($params['OnChange'] != '') $out_txt .= " onchange=\"{$params['OnChange']}\"";
            $out_txt .= " />\n";
        } else {
            $out_txt .= "<select name=\"{$params['Prefix']}[y]\"";
            if ($params['OnChange'] != '') $out_txt .= " onchange=\"{$params['OnChange']}\"";
            $out_txt .= ">\n";
            $out_txt .= "<option value=\"\">Y</option>\n";
            if ($params['ReverseYears'] == 1) {
                for ($i = $params['MaxYear']; $i >= $params['MinYear']; $i--) {
                    $out_txt .= '<option value="' . str_pad ($i, 4, '0', STR_PAD_LEFT) . '"';
                    if ($params['y'] == $i) $out_txt .= ' selected="selected"';
                    if ($params['ShortYear'] == 1) {
                        $out_txt .= "> ". substr ($i, strlen ($i) - 2). "\n";
                    } else {
                        $out_txt .= "> {$i}</option>\n";
                    }
                }
            } else {
                for ($i = $params['MinYear']; $i <= $params['MaxYear']; $i++) {
                    $out_txt .= '<option value="' . str_pad ($i, 4, '0', STR_PAD_LEFT) . '"';
                    if ($params['y'] == $i) $out_txt .= ' selected="selected"';
                    if ($params['ShortYear'] == 1) {
                        $out_txt .= "> ". substr ($i, strlen ($i) - 2). "\n";
                    } else {
                        $out_txt .= "> {$i}</option>\n";
                    }
                }
            }
            $out_txt .= "</select>\n";
        }
        
        if ($params['Calendar'] == 1) {
            // calendar popup
            $out_txt .= "<img src=\"". ROOT_PATH_WEB. "tricho/images/calendar.png\" ".
                "style=\"cursor: pointer; vertical-align: text-bottom;\" ".
                "onclick=\"var form_el = find_form (this); cal_popup ('". ROOT_PATH_WEB.
                "tricho/date_select.php?f=' + form_el.attributes.name.value + '&amp;pre={$params['Prefix']}'";
            if ($params['OnChange'] != '') {
                $out_txt .= ", '". addslashes ($params['OnChange']). "'";
            }
            $out_txt .= ");\" alt=\"[ Choose date from calendar ]\" title=\"Choose date from calendar\" />\n";
        }
    }
    
    if ($params['IncludeTime'] == 1 or $params['TimeOnly'] == 1) {
        if (!is_numeric ($params['mn'])) $params['mn'] = '00';
        
        if ($params['TimeOnly'] != 1) $out_txt .= $params['DateTimeDivider'];
        
        $out_txt .= "<select name=\"{$params['Prefix']}[hr]\">\n";
        for ($i = 1; $i <= 12; $i++) {
            $out_txt .= '<option value="' . str_pad ($i, 2, '0', STR_PAD_LEFT) . '"';
            if ($params['hr'] == $i or $params['hr'] - 12 == $i or ($params['hr'] == 0 and $i == 12)) {
                $out_txt .= ' selected="selected"';
            }
            $out_txt .= "> {$i}</option>\n";
        }
        $out_txt .= "</select>";
        $out_txt .= $params['TimeDivider'];
        $out_txt .= "<input type=\"text\" size=\"2\" maxlength=\"2\" name=\"{$params['Prefix']}[mn]\" value=\"{$params['mn']}\">";
        if ($params['IncludeSeconds']) {
            $out_txt .= $params['TimeDivider'];
            if (!is_numeric ($params['sc'])) $params['sc'] = '00';
            $out_txt .= "<input type=\"text\" size=\"2\" maxlength=\"2\" name=\"{$params['Prefix']}[sc]\" value=\"{$params['sc']}\">";
        } else {
            $out_txt .= "<input type=\"hidden\" name=\"{$params['Prefix']}[sc]\" value=\"00\">";
        }
        
        $out_txt .= " <select name=\"{$params['Prefix']}[AP]\">\n";
        $out_txt .= '<option value="AM"';
        if ($params['hr'] < 12) $out_txt .= ' selected="selected"';
        $out_txt .= ">AM</option>\n";
        $out_txt .= '<option value="PM"';
        if ($params['hr'] >= 12) $out_txt .= ' selected="selected"';
        $out_txt .= ">PM</option>\n";
        $out_txt .= "</select>\n";
        
    }
    
    return $out_txt; // " (Date selected - {$params['y']}-{$params['m']}{$params['d']} " .
        // "{$params['hr']}:{$params['mn']}:{$params['sc']} from {$params['DateSelected']})";
    
}

/**
 * Builds a date (in MySQL's DATE or DATETIME format, ie. YYYY-MM-DD or YYYY-MM-DD HH:MM:SS)
 * from data posted by tricho_date_select.
 * 
 * @deprecated Use a TemporalColumn instead
 * 
 * @param string $prefix The prefix that is attached to the date-specific POST data.
 * @param array $alternate_data Allows the specification of an alternate array thay holds the data
 *     The array should contain all the regular date keys used in the post data (such as 'y', 'd' and 'm').
 *     When this parameter is used, the $prefix parameter is ignored.
 * @param string $explicit_type The function attempts to auto-detect the type (date, time, or datetime).
 *     In some instances you may wish to explicitly specify the type you want for it to work as you'd expect.
 * @return string The date selected by the user (may be null if invalid/incomplete data provided)
 */
function tricho_date_build ($prefix, $alternate_data = null, $explicit_type = null) {
    
    if ($alternate_data != null) {
        $data = $alternate_data;
        
    } else {
        if (strpos ($prefix, '[') !== false) {
            $components = explode ('[', $prefix);
            for ($i = 1; $i < count($components); $i++) {
                while ($components[$i]{strlen ($components[$i]) - 1} == ']') {
                    $components[$i] = substr ($components[$i], 0, strlen ($components[$i]) - 1);
                }
            }
            $post_area = '$_POST';
            for ($i = 0; $i < count($components); $i++) {
                $post_area .= "['{$components[$i]}']";
            }
            // echo "\$data = {$post_area};<br>\n";
            eval ("\$data = {$post_area};");
        } else {
            $data = $_POST[$prefix];
        }
    }
    
    // echo "tricho_date_build ($prefix) got data: ", print_r ($data, true), "<br>\n";
    
    $date = '';
    if (strcasecmp (substr ($explicit_type, 0, 4), 'date') == 0 or
            isset ($data['y']) or isset ($data['m']) or isset ($data['d'])) {
        // Mangle width of data, or construct valid date
        // Only positive dates are allowed
        $data['y'] = preg_replace ('/[^0-9]/', '', $data['y']);
        if ($data['y'] == '') {
            $data['y'] = '?';
        } else {
            $data['y'] = str_pad ($data['y'], 4, '0', STR_PAD_LEFT);
        }
        
        $data['m'] = preg_replace ('/[^0-9]/', '', $data['m']);
        if ($data['m'] == '') {
            $data['m'] = '?';
        } else {
            $data['m'] = str_pad ($data['m'], 2, '0', STR_PAD_LEFT);
        }
        
        $data['d'] = preg_replace ('/[^0-9]/', '', $data['d']);
        if ($data['d'] == '') {
            $data['d'] = '?';
        } else {
            $data['d'] = str_pad ($data['d'], 2, '0', STR_PAD_LEFT);
        }
        $date = "{$data['y']}-{$data['m']}-{$data['d']}";
    }
    if (stripos ($explicit_type, 'time') !== false or isset ($data['hr'])) {
        if ($date != '') $date .= ' ';
        
        $data['hr'] = (int) $data['hr'];
        $data['mn'] = (int) $data['mn'];
        $data['sc'] = (int) $data['sc'];
        
        // constrain to sensible values
        $data['hr'] = min ($data['hr'], 12);
        $data['hr'] = max ($data['hr'], 0);
        $data['mn'] = min ($data['mn'], 59);
        $data['mn'] = max ($data['mn'], 0);
        $data['sc'] = min ($data['sc'], 59);
        $data['sc'] = max ($data['sc'], 0);
        
        if ($data['AP'] == 'PM' and $data['hr'] != 12) {
            $data['hr'] += 12;
        } else if ($data['AP'] == 'AM' and $data['hr'] == 12) {
            $data['hr'] = '00';
        }
        
        $data['hr'] = str_pad ($data['hr'], 2, '0', STR_PAD_LEFT);
        $data['mn'] = str_pad ($data['mn'], 2, '0', STR_PAD_LEFT);
        $data['sc'] = str_pad ($data['sc'], 2, '0', STR_PAD_LEFT);
        
        $date .= "{$data['hr']}:{$data['mn']}:{$data['sc']}";
    }
    
    // null date
    if ($date == '?-?-?') {
        $date = null;
    
    // null datetime
    } else if ($date == '?-?-? 00:00:00') {
        $date = null;
    }
    return $date;
}

/**
 * Adds/subtracts days and/or months to/from a given date.
 * 
 * Usage:<br>
 * $today = date ('Y-m-d');<br>
 * // One year from now<br>
 * $new_date = iso_date_add ($today, 12);<br>
 * // Three days ago<br>
 * $new_date = iso_date_add ($today, 0, -3);<br>
 * // One month and a week from now<br>
 * $new_date = iso_date_add ($today, 1, 7);<br>
 * 
 * @param string $date the date, in ISO 8601 format (YYYY-MM-DD)
 * @param int $months the number of months
 * @param int $days the number of days
 * 
 * @return string
 */
function iso_date_add ($date, $months, $days = 0) {
    settype ($months, 'integer');
    settype ($days, 'integer');
    list ($y, $m, $d) = explode ('-', $date);
    settype ($y, 'integer');
    settype ($m, 'integer');
    settype ($d, 'integer');
    // echo "Using $months month(s) and $days day(s)<br>\n";
    // define max number of days per month
    $month_days[1] = 31;
    if (is_leap_year ($y)) {
        $month_days[2] = 29; // leap year
    } else {
        $month_days[2] = 28; // common year
    }
    $month_days[3] = 31;
    $month_days[4] = 30;
    $month_days[5] = 31;
    $month_days[6] = 30;
    $month_days[7] = 31;
    $month_days[8] = 31;
    $month_days[9] = 30;
    $month_days[10] = 31;
    $month_days[11] = 30;
    $month_days[12] = 31;
    if ($months != 0) {
        $m += $months;
        while ($m < 1) {
            $m += 12;
            $y -= 1;
        }
        while ($m > 12) {
            $m -= 12;
            $y += 1;
        }
        
        // check leap year again
        if (is_leap_year ($y)) {
            $month_days[2] = 29; // leap year
        } else {
            $month_days[2] = 28; // common year
        }
        // cut days if necessary
        if ($d > $month_days[$m]) {
            $d = $month_days[$m];
        }
    }
    if ($days != 0) {
        $d += $days;
        while ($d > $month_days[$m]) {
            if ($m == 12) {
                $y++;
                $m = 1;
                $d -= $month_days[12];
                // update year
                if ($y % 4 == 0 and $y % 1000 != 0) {
                    $month_days[2] = 29; // leap year
                } else {
                    $month_days[2] = 28; // normal year
                }
            } else {
                $d -= $month_days[$m];
                $m++;
            }
        }
        while ($d < 1) {
            if ($m == 1) {
                $y--;
                // check leap year again
                if (is_leap_year ($y)) {
                    $month_days[2] = 29; // leap year
                } else {
                    $month_days[2] = 28; // normal year
                }
                $m = 12;
                $d += $month_days[12];
            } else {
                $m--;
                $d += $month_days[$m];
            }
        }
        
    }
    $date = str_pad ($y, 4, '0', STR_PAD_LEFT). '-'.
        str_pad ($m, 2, '0', STR_PAD_LEFT). '-'.
        str_pad ($d, 2, '0', STR_PAD_LEFT);
    return $date;
}

/**
 * determines if a given year is a leap year or not
 * 
 * @param int $y the year (4 digits -- unless you want a year before 100AD)
 * 
 * @return bool true for a leap year, false for a common year
 */
function is_leap_year ($y) {
    if ($y % 4 == 0 and ($y % 100 != 0 or $y % 400 == 0)) {
        return true; // leap year (divisible by 4, excluding those that are divisible by 100 but not 400)
    } else {
        return false; // common year
    }
}

/**
 * Checks if the specified date is valid
 * @param string $date An ISO 8601 formatted (YYYY-MM-DD) date to check
 * @return bool True if the date is valid, false otherwise
 */
function is_valid_date ($date) {
    list ($year, $month, $day) = explode ('-', $date);
    $result = true;
    settype ($day, 'integer');
    settype ($month, 'integer');
    settype ($year, 'integer');
    
    if ($year == 0) {
        $result = false;
    } else {
        if ($day <= 0 or $month <= 0 or $month > 12) {
            $result = false;
        } else {
            
            $month_days = array (
                1 => 31,
                2 => 28,
                3 => 31,
                4 => 30,
                5 => 31,
                6 => 30,
                7 => 31,
                8 => 31,
                9 => 30,
                10 => 31,
                11 => 30,
                12 => 31
            );
            if (is_leap_year ($year)) {
                $month_days[2] = 29;
            }
            
            if ($day > $month_days[$month]) {
                $result = false;
            }
        }
    }
    return $result;
}

/**
 * Checks a time
 * @param string $time The ISO 8601 formatted (HH:MM:SS) time to check
 * @return bool True if the time is valid, false otherwise
 */
function is_valid_time ($time) {
    list ($hour, $min, $sec) = explode (':', $time);
    
    $result = true;
    
    if (($hour < 0) or ($hour > 23)) {
        $result = false;
    } else if (($min < 0) or ($min > 59)) {
        $result = false;
    } else {
        if (isset($sec)) {
            if (($sec < 0) or ($sec > 59)) {
                $result = false;
            }
        }
    }
    
    return $result;
}

?>
