/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

function show_row (id) {
    var row = document.getElementById (id);
    if (row.nodeName && row.nodeName.toUpperCase () == 'TR') {
        row.style.display = '';
    } else {
        alert ("Tried show_row on " + id + " nodeName: " + row.nodeName);
    }
}

function hide_row (id) {
    var row = document.getElementById (id);
    if (row.nodeName && row.nodeName.toUpperCase () == 'TR') {
        row.style.display = 'none';
    } else {
        alert ("Tried hide_row on " + id + " nodeName: " + row.nodeName);
    }
}

function find_form (element) {
    var parent = element;
    var response;
    while (parent.nodeName.toUpperCase () != 'FORM') {
        parent = parent.parentNode;
        if (parent == null) {
            break;
        }
        response += parent.nodeName + "\n";
    }
    // window.alert (response + ': ' + parent.name);
    return parent;
}

function cal_popup (url, on_change_function_name) {
    
    if (on_change_function_name) {
        var sep = '?';
        if (url.search (/\?/)) {
            sep = '&';
        }
        
        url += sep + 'onchange=' + encodeURIComponent (on_change_function_name);
    }
    
    var win = window.open (url, '_blank',
        'width=265,height=190,directories=0,location=0,menubar=0,resizable=1,scrollbars=0,status=0,toolbar=0');
    win.focus ();
}

function find_form_input (form, name) {
    var inputs = form.elements;
    var found = null;
    for (i = 0; i < inputs.length; i++) {
        if (inputs[i].name == name) {
            found = inputs[i];
            break;
        }
    }
    return found;
}

function PopupCalendar () {
    
    var prefix;
    var opener_form;
    var month;
    var year;
    var min_year;
    var max_year;
    var cal_dates;
    var date = new Date ();
    var on_change_function_name = '';
    
    this.init = function (form_name, prefix, change_func_name) {
        this.opener_form = eval ('window.opener.document.forms.' + form_name);
        this.prefix = prefix;
        if (change_func_name) this.on_change_function_name = change_func_name;
        
        // Pull values from calling page
        var init_month = find_form_input (this.opener_form, prefix + '[m]');
        this.month = Number (init_month.value);
        //alert ('this.month: ' + this.month);
        
        var old_year_field = find_form_input (this.opener_form, prefix + '[y]');
        this.year = old_year_field.value;
        
        // if selected date is valid, use it, otherwise use 0001-01-01
        this.selected_date = new Date ();
        var init_day = find_form_input (this.opener_form, prefix + '[d]');
        var selected_day = Number (init_day.value);
        if (!isNaN (this.month) && !isNaN (selected_day) && this.year > 0) {
            this.selected_date.setFullYear (this.year);
            this.selected_date.setMonth (this.month - 1);
            this.selected_date.setDate (selected_day);
        } else {
            this.selected_date.setFullYear (1);
            this.selected_date.setMonth (0);
            this.selected_date.setDate (1);
        }
        
        
        // If drop-downs were left blank on calling page, set a default (today)
        // if (this.month <= 0 || this.month > 12) this.month = 1;
        this.curr_date = new Date ();
        if (this.month <= 0 || this.month > 12 || isNaN(this.month)) this.month = this.curr_date.getMonth () + 1;
        if (this.year <= 0) this.year = this.curr_date.getFullYear();
        
        // alert ('this.month: ' + this.month);
        
        var min = 10000;
        var max = -1;
        var year_field = document.getElementById ('year');
        var month_field = document.getElementById ('month');
        month_field.value = this.month;
        if (year_field == null) alert ("No year field");
        var year_set = false;
        var first_year = -1;
        
        // alert ("Month: " + month_field.value);
        
        var current_index = 0;
        if (old_year_field.options) {
            for (i = 0; i < old_year_field.options.length; i++) {
                if (old_year_field.options[i].value > 0) {
                    // if (this.year == 0) this.year = old_year_field.options[i].value;
                    if (first_year == -1) first_year = i;
                    year_field.options[current_index] = new Option (old_year_field.options[i].value,
                        old_year_field.options[i].value);
                    if (year_field.options[current_index].value == this.year) {
                        year_field.selectedIndex = current_index;
                        year_set = true;
                    }
                    current_index++;
                }
            }
        }
        if (old_year_field.options) {
            if (!year_set) {
                this.year = old_year_field.options[first_year].value;
                year_field.value = this.year;
            }
        } else {
            // if there's not a year select on the caller page, use a text field on the popup as well
            // year_field.type = 'text';
            new_year_field = document.createElement ('input');
            new_year_field.attributes.type = 'text';
            new_year_field.attributes.size = 4;
            new_year_field.size = 4;
            new_year_field.value = this.year;
            new_year_field.onchange =    function () {
                my_cal.set_year (this.value); my_cal.draw ();
            };
            year_field.parentNode.replaceChild (new_year_field, year_field);
        }
        
        this.cal_dates = new Array ();
        for (i = 1; i <= 31; i++) {
            this.cal_dates[i] = new PopupCalendarDate (i);
        }
        
    }
    
    this.draw = function () {
        var table = document.getElementById ('cal');
        
        click_dates = new Array ();
        
        // TODO: remove old elements
        var children = table.childNodes;
        for (i = children.length - 1; i >= 0; i--) {
            table.removeChild (children.item (i));
        }
        
        // work out the first day of the month
        date.setDate (1);
        date.setMonth (this.month - 1);
        date.setFullYear (this.year);
        var start_day = date.getDay ();
        
        var tr;
        var td;
        var td_count = 0;
        
        tr = document.createElement ('tr');
        table.appendChild (tr);
        
        for (i = 0; i < start_day; i++) {
            td = document.createElement ('td');
            td.appendChild (document.createTextNode ('\u00a0')); // &nbsp;
            tr.appendChild (td);
            td_count++;
        }
        
        var month_days = days_per_month (this.month, this.year);
        // alert (month_days + " days in " + this.month + "/" + this.year);
        var action_script;
        
        var current_year_month_match = false;
        var selected_year_month_match = false;
        if (this.month == this.curr_date.getMonth () + 1 && this.year == this.curr_date.getFullYear()) {
            current_year_month_match = true;
        }
        if (this.month == this.selected_date.getMonth () + 1 && this.year == this.selected_date.getFullYear()) {
            selected_year_month_match = true;
        }
        
        for (i = 0; i < month_days; i++) {
            if (td_count == 7) {
                table.appendChild (document.createTextNode ("\n"));
                tr = document.createElement ('tr');
                table.appendChild (tr);
                td_count = 0;
            }
            
            tr.appendChild (this.cal_dates[i + 1].getTd (
                current_year_month_match && (i + 1 == this.curr_date.getDate ()),
                selected_year_month_match && (i + 1 == this.selected_date.getDate ()))
            );
            td_count++;
        }
        while (td_count < 7) {
            td = document.createElement ('td');
            td.appendChild (document.createTextNode ('\u00a0'));
            tr.appendChild (td);
            td_count++;
        }
        
    }
    
    this.set_month = function (month) {
        this.month = month;
    }
    
    this.set_year = function (year) {
        this.year = year;
    }
    
    this.submit = function (day) {
        var month = String (this.month);
        day = String (day);
        if (month.length < 2) month = '0'.concat (month);
        if (day.length < 2) day = '0'.concat (day);
        
        var day_field = find_form_input (this.opener_form, this.prefix + '[d]');
        var month_field = find_form_input (this.opener_form, this.prefix + '[m]');
        var year_field = find_form_input (this.opener_form, this.prefix + '[y]');
        
        // alert ('Setting date '+ this.year.concat ('.'). concat (month). concat ('.'). concat (day));
        
        // fire onchange event if date has changed
        if (Number (day_field.value) != Number (day) ||
                Number (month_field.value) != Number (month) ||
                Number (year_field.value) != Number (this.year)) {
            if (this.on_change_function_name) {
                eval ('window.opener.' + this.on_change_function_name);
            }
        }
        
        day_field.value = day;
        month_field.value = month;
        year_field.value = this.year;
        window.close ();
    }
    
}

function PopupCalendarDate (input) {
    this.td = document.createElement ('td');
    
    this.td.appendChild (document.createTextNode (input));
    this.td.style.cursor = 'pointer';
    this.td.style.textAlign = 'center';
    this.td.setAttribute ('id', 'day_' + input);
    this.td.onmouseover = function () {
        cell = document.getElementById ('day_' + input);
        // if (cell == null) alert ('No cell with id day_' + input);
        if (cell.className == '' || cell.className == 'current') {
            cell.className = 'current';
        } else {
            cell.className += ' current';
        }
    }
    this.td.onmouseout = function () {
        cell = document.getElementById ('day_' + input);
        if (cell.className == 'current') {
            cell.className = '';
        } else {
            cell.className = cell.className.replace (' current', '');
        }
    }
    this.td.onclick = function () {
        my_cal.submit (input);
    }
    
    this.getTd = function (is_today, is_selected) {
        
        var td = this.td;
        
        /*
        if (is_today == true) {
            var bolder = document.createElement ('strong');
            td = this.td.cloneNode (true);
            
            bolder.appendChild (td.firstChild);
            td.appendChild (bolder);
        } else {
            td = this.td;
        }
        */
        
        if (is_selected) {
            td.className = 'selected';
        } else {
            td.className = '';
        }
        
        if (is_today) {
            td.style.fontWeight = 'bold';
        } else {
            td.style.fontWeight = 'normal';
        }
        
        return td;
    }
}

function days_per_month (month, year) {
    var res;
    switch (Number (month)) {
        case 1:    res = 31; break;
        case 2:
            if (is_leap_year (year)) {
                res = 29; // leap year
            } else {
                res = 28; // common year
            }
            break;
        case 3:    res = 31; break;
        case 4:    res = 30; break;
        case 5:    res = 31; break;
        case 6:    res = 30; break;
        case 7:    res = 31; break;
        case 8:    res = 31; break;
        case 9:    res = 30; break;
        case 10: res = 31; break;
        case 11: res = 30; break;
        case 12: res = 31; break;
        default:
            alert ('Error: ' + month + ' is not between 1 and 12');
    }
    // alert (res + ' days in ' + month + '/' + year);
    return res;
}

function is_leap_year (year) {
    if (year % 4 == 0 && (year % 100 != 0 || year % 400 == 0)) {
        return true; // leap year (divisible by 4, excluding those that are divisible by 100 but not 400)
    } else {
        return false; // common year
    }
}

/**
* Checks if the specified datetime is valid
* @param string date_time An ISO 8601 formatted (YYYY-MM-DD HH:MM:SS) datetime to check
* @return bool True if the datetime is valid, false otherwise
**/
function is_valid_date_time (date_time) {
    
    var date_time_parts = date_time.split (' ');
    if (date_time_parts.length == 2) {
        return is_valid_date (date_time_parts[0]) && is_valid_time (date_time_parts[1]);
    } else {
        return false;
    }
    
}

/**
* Checks if the specified date is valid
* @param string date An ISO 8601 formatted (YYYY-MM-DD) date to check
* @return bool True if the date is valid, false otherwise
**/
function is_valid_date (date) {
    
    var date_parts = date.split ('-');
    var result = true;
    
    if (date_parts.length == 3) {
        
        var year = date_parts[0];
        var month = date_parts[1];
        var day = date_parts[2];
        
        if (year.length != 4 || year == 0) {
            result = false;
        } else {
            if (month.length != 2 || day.length != 2 || day <= 0 || month <= 0 || month > 12) {
                result = false;
            } else {
                
                var month_days = {
                    1: 31,
                    2: 28,
                    3: 31,
                    4: 30,
                    5: 31,
                    6: 30,
                    7: 31,
                    8: 31,
                    9: 30,
                    10: 31,
                    11: 30,
                    12: 31
                };
                if (is_leap_year (year)) {
                    month_days[2] = 29;
                }
                
                if (day > month_days[Number (month)]) {
                    result = false;
                }
            }
        }
        
    } else {
        result = false;
    }
    
    return result;
}

/**
* Checks if the specified time is valid
* 
* @param string time The ISO 8601 formatted (HH:MM:SS) time to check
* @return bool True if the time is valid, false otherwise
**/
function is_valid_time (time) {
    
    var time_parts = time.split (':');
    var result = true;
    
    if (time_parts.length == 3) {
        
        var hour = time_parts[0];
        var min = time_parts[1];
        var sec = time_parts[2];
        
        if (hour.length != 2 || hour < 0 || hour > 23) {
            result = false;
        } else if (min.length != 2 || min < 0 || min > 59) {
            result = false;
        } else if (sec.length != 2 || sec < 0 || sec > 59) {
            result = false;
        }
        
    } else {
        result = false;
    }
    
    return result;
}

function get_radio_value (radio_el) {
    
    if (typeof (radio_el) == 'object' && radio_el.length > 0) {
        
        for (var i in radio_el) {
            if (radio_el[i].checked == true) {
                return radio_el[i].value;
            }
        }
    }
    return false;
}

/*
function calendar_over (element) {
    element.style.backgroundColor = 'FF0000';
}

function calendar_out (element) {
    element.style.backgroundColor = 'FFFFFF';
}

calendar_pass_back = function (data) {
    alert ('Pass back ' + data);
}
*/
