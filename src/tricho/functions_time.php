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
