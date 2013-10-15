<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

header('Content-type: text/xml; charset=UTF-8');
?>
<p style="border: 1px black solid; background-color: #CCF; padding: 1em;" class="">
    This text should be very small because the p has a style of xx-small
    <form action="pizza.php">
    <input type="hidden" value="1" name="hidden_field" />
    <input type="text" value="1" name="text_field" />
    <input type="password" value="1" name="pwd_field" />
    <input type="radio" value="1" name="radio_field" />
    <input type="checkbox" value="1" name="check_field" />
    <input type="file" value="1" name="file_field" />
    <input type="submit" />
    </form>
</p>
