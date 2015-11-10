<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Runtime;

// This file creates the pop-up calendar used throughout the admin section of the site, and perhaps
// a few places on the front-end, too

require '../tricho.php';
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title><?= Runtime::get('site_name'); ?>: Date select</title>
    <link rel="stylesheet" type="text/css" href="calendar.css">
    <script language="JavaScript" type="text/javascript" src="functions.js"></script>
    <script language="JavaScript" type="text/javascript">
        <!--
        var my_cal;
        function make_calendar () {
            my_cal = new PopupCalendar ();
            my_cal.init ('<?= $_GET['f']; ?>', '<?= $_GET['pre']; ?>', '<?= addslashes ($_GET['onchange']); ?>');
        }
        // -->
    </script>
</head>
<body onLoad="make_calendar (); my_cal.draw ();">

<table>
    <p>Month
        <select name="month" id="month" onChange="my_cal.set_month (this.value); my_cal.draw ();">
            <option value="1">January</option>
            <option value="2">February</option>
            <option value="3">March</option>
            <option value="4">April</option>
            <option value="5">May</option>
            <option value="6">June</option>
            <option value="7">July</option>
            <option value="8">August</option>
            <option value="9">September</option>
            <option value="10">October</option>
            <option value="11">November</option>
            <option value="12">December</option>
        </select>
        Year
        <select name="year" id="year" onChange="my_cal.set_year (this.value); my_cal.draw ();">
        </select>
    </p>
    <table>
        <thead>
            <tr><th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th></tr>
        </thead>
        <tbody id="cal">
        </tbody>
    </table>
<table>
</form>

</body>
</html>
