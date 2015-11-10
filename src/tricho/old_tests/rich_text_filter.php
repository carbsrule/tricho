<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../../tricho.php';
header ('Content-Type: text/html; charset=utf-8');
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
    <title>Rich text filtering test suite</title>
</head>
<body>
    <h1>Rich text filter tests</h1>

<?php

/******************************************************************************/
/*                                                                     test 1                                                                     */
/******************************************************************************/

$tags_allow     = 'a:href,img:src;alt';
$tags_replace = '';
$tags_deny        = '';

$input = '<a href="http://google.com.au/">
    <img src="http://www.google.com.au/intl/en_au/images/logo.gif" alt="Google!"/>

</a>';

$expect = '<a href="http://google.com.au/">
    <img src="http://www.google.com.au/intl/en_au/images/logo.gif" alt="Google!"/>
</a>';

run_test (1, $input, $expect, $tags_allow, $tags_replace, $tags_deny);

/******************************************************************************/
/*                                                                     test 2                                                                     */
/******************************************************************************/

$tags_allow = 'img:src;alt';
$tags_replace = '';
$tags_deny = 'a';

$input = '<p>Testing</p><a href="http://google.com.au/">
    <img src="http://www.google.com.au/intl/en_au/images/logo.gif" alt="Google!"/>
</a>';

$expect = '<p>Testing</p>';

run_test (2, $input, $expect, $tags_allow, $tags_replace, $tags_deny);

/******************************************************************************/
/*                                                                     test 3                                                                     */
/******************************************************************************/

/* defaults */
$tags_allow = HTML_TAGS_ALLOW;
$tags_replace = HTML_TAGS_REPLACE;
$tags_deny = HTML_TAGS_DENY;

$input = '<script>
    function spamalot () {
        while (true)
            alert ("SPAM SPAM");
        }
</script>
 <table>
    <tr>
        <th colspan="2">Table header</th>
    </tr>
    <tr>
        <td><b>LEFT</b></td>
        <td>RIGHT</td>
    </tr>
</table>';

$expect = '<table>
    <tr>
        <th>Table header</th>
    </tr>
    <tr>
        <td>
            <strong>LEFT</strong>
        </td>
        <td>RIGHT</td>
    </tr>
</table>';

run_test (3, $input, $expect, $tags_allow, $tags_replace, $tags_deny);

/******************************************************************************/
/*                                                                     test 4                                                                     */
/******************************************************************************/

/* defaults */
$tags_allow     = HTML_TAGS_ALLOW;
$tags_replace = HTML_TAGS_REPLACE;
$tags_deny        = HTML_TAGS_DENY;

$input    = "<p>二条城（にじょうじょう）とは京都市中京区二条通堀川西入二条城町にある江戸時代の城である。</p>";

$expect = "<p>二条城（にじょうじょう）とは京都市中京区二条通堀川西入二条城町にある江戸時代の城である。</p>";

run_test (3, $input, $expect, $tags_allow, $tags_replace, $tags_deny);

/******************************************************************************/
/*                                                                     test 5                                                                     */
/******************************************************************************/

/* defaults */
$tags_allow     = 'a:href;target,p';
$tags_replace = HTML_TAGS_REPLACE;
$tags_deny        = 'blockquote';

$input = "<a href=\"#\">LINK!</a>
<p>
    <b>徳川家康の将軍宣下と、徳川慶喜の大政奉還が行われ、江戸幕府の始まりと終焉の場所でもある。</b>
</p>
<blockquote>二条城（にじょうじょう）とは京都市中京区二条通堀川西入二条城町にある江戸時代の城である。</blockquote>";

$expect = "<p><a href=\"#\">LINK!</a></p><p><strong>徳川家康の将軍宣下と、徳川慶喜の大政奉還が行われ、江戸幕府の始まりと終焉の場所でもある。</strong></p>";

run_test (5, $input, $expect, $tags_allow, $tags_replace, $tags_deny);

/**
 * Takes an input HTML string and applies rich text cleaning, checking the
 * result is as expected (i.e. compared with a given expected output string).
 *
 * The expected result and the actual result are used to build DOM trees which
 * are then normalized to allow a more effective string comparison.
 *
 * @param int $test Test number (used for results output)
 * @param string $input Input HTML
 * @param string $expect Expected value of input after cleaned
 * @param string $allow Tag allow rules
 * @param string $replace Tag replacemenet rules
 * @param string $deny Tag deny rules
 */
function run_test ($test, $input, $expect, $allow, $replace, $deny) {
    
    $out = "";
    
    $output = clean_rich_text_input (
        $input, $allow, $replace, $deny
    );
    
    $out .= 'INPUT:<br />';
    $out .= "<pre style=\"border: 1px solid #3333FF; padding: 2px 2px 2px 2px;\">\n";
    $out .= htmlspecialchars ($input);
    $out .= '</pre>';
    
    $out .= "RULES:<br />\n";
    $out .= "<pre style=\"border: 1px solid #3333FF; padding: 2px 2px 2px 2px;\">\n";
    $out .= "\tALLOW:     ". (($allow == '')     ? 'NONE' : $allow).     "\n";
    $out .= "\tREPLACE: ". (($replace == '') ? 'NONE' : $replace). "\n";
    $out .= "\tDENY:        ". (($deny == '')        ? 'NONE' : $deny).        "\n";
    $out .= "</pre>";
    
    $out .= 'OUTPUT:<br />';
    $out .= "<pre style=\"border: 1px solid #3333FF; padding: 2px 2px 2px 2px;\">\n";
    $out .= htmlspecialchars ($output);
    $out .= '</pre>';
    
    $out .= 'EXPECTED:<br />';
    $out .= "<pre style=\"border: 1px solid #3333FF; padding: 2px 2px 2px 2px;\">\n";
    $out .= htmlspecialchars ($expect);
    $out .= '</pre>';
    
    $output_doc = new DOMDocument ('1.0', 'utf-8');
    $expect_doc = new DOMDocument ('1.0', 'utf-8');
    
    $output_doc->preserveWhiteSpace = false;
    $output_doc->formatOutput = true;
    $except_doc->preserveWhiteSpace = false;
    $except_doc->formatOutput = true;
    
    @$output_doc->loadXML ($output);
    @$expect_doc->loadXML ($expect);
    
    $output_doc->normalizeDocument ();
    $expect_doc->normalizeDocument ();
    
    /* $out .= '<pre>'. htmlentities ($output_doc->saveXML ()). '</pre>'; */
    
    if ($output_doc->saveXML () == $expect_doc->saveXML ()) {
        $out = "<h3>Test {$test} -- <span style=\"color: green;\">Passed!</span></h3><br />{$out}";
    } else {
        $out = "<h3>Test {$test} -- <span style=\"color: red;\">Failed!</span></h3><br />{$out}";
    }
    
    echo $out;
}

?>

</body>
</html>
