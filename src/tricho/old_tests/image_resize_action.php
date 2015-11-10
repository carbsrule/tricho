<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require '../../tricho.php';

if (is_uploaded_file ($_FILES['image']['tmp_name'])) {
    
    if ($size = @getimagesize ($_FILES['image']['tmp_name'])) {
        $width = $size[0];
        $height = $size[1];
        
        // traditional resize
        make_sized_image ($_FILES['image']['tmp_name'], 'image.1', $width, 0, '', DEFAULT_JPEG_QUALITY);
        make_sized_image ($_FILES['image']['tmp_name'], 'image.2', 0, $height, '', DEFAULT_JPEG_QUALITY);
        make_sized_image ($_FILES['image']['tmp_name'], 'image.3', $width, $height/2, '', DEFAULT_JPEG_QUALITY);
        make_sized_image ($_FILES['image']['tmp_name'], 'image.4', $width/2, $height, '', DEFAULT_JPEG_QUALITY);
        make_sized_image ($_FILES['image']['tmp_name'], 'image.5', $width/2, $height/2, '', DEFAULT_JPEG_QUALITY);
        make_sized_image ($_FILES['image']['tmp_name'], 'image.6', $width/2, $height/4, '', DEFAULT_JPEG_QUALITY);
        make_sized_image ($_FILES['image']['tmp_name'], 'image.7', $width/4, $height/2, '', DEFAULT_JPEG_QUALITY);
        
        // resize with chop
        make_sized_image ($_FILES['image']['tmp_name'], 'image.8', $width, 0, '', DEFAULT_JPEG_QUALITY, true);
        make_sized_image ($_FILES['image']['tmp_name'], 'image.9', 0, $height, '', DEFAULT_JPEG_QUALITY, true);
        make_sized_image ($_FILES['image']['tmp_name'], 'image.10', $width, $height/2, '', DEFAULT_JPEG_QUALITY, true);
        make_sized_image ($_FILES['image']['tmp_name'], 'image.11', $width/2, $height, '', DEFAULT_JPEG_QUALITY, true);
        make_sized_image ($_FILES['image']['tmp_name'], 'image.12', $width/2, $height/2, '', DEFAULT_JPEG_QUALITY, true);
        make_sized_image ($_FILES['image']['tmp_name'], 'image.13', $width/2, $height/4, '', DEFAULT_JPEG_QUALITY, true);
        make_sized_image ($_FILES['image']['tmp_name'], 'image.14', $width/4, $height/2, '', DEFAULT_JPEG_QUALITY, true);
        make_sized_image ($_FILES['image']['tmp_name'], 'image.15', $width/2, $height/8, '', DEFAULT_JPEG_QUALITY, true);
        make_sized_image ($_FILES['image']['tmp_name'], 'image.16', $width/8, $height/2, '', DEFAULT_JPEG_QUALITY, true);
        
    } else {
        $_SESSION['old_tests']['img_resize']['err'] = 'Invalid image';
    }
}

redirect ('image_resize.php');

?>
