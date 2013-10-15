<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';
?>

<html>
    <head>
        <title>Test suite: Image resize</title>
        <style type="text/css">
            .floater {
                display: block;
                float: left;
                margin: 10px 10px 10px 10px;
            }
        </style>
    </head>
    <body>
        
<?php
if (isset ($_SESSION['old_tests']['img_resize']['err'])) {
    report_error ($_SESSION['old_tests']['img_resize']['err']);
    unset ($_SESSION['old_tests']['img_resize']['err']);
}
?>
        
        <form method="post" action="image_resize_action.php" enctype="multipart/form-data">
            <input type="file" name="image"> <input type="submit" value="Resize">
        </form>
        
<?php
for ($i = 1; $i <= 16; $i++) {
    
    if ($i == 8) {
        echo "<hr style=\"clear: both;\">";
    }
    
    $file = 'image.'. $i;
    if (file_exists ($file)) {
        $size = getimagesize ($file);
        echo "<div class=\"floater\"><img src=\"{$file}\" width=\"100\" /><br />",
            "{$size[0]}&times;{$size[1]}</div>\n";
    }
}
?>
        
    </body>
</html>
