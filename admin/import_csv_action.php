<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once '../tricho.php';

test_setup_login (true, SETUP_ACCESS_FULL);

// type checks
switch ($_FILES['csv']['error']) {
    case UPLOAD_ERR_OK:
        $error = null;
        break;
        
    case UPLOAD_ERR_INI_SIZE:
    case UPLOAD_ERR_PARTIAL:
        $error = 'The entire file could not be uploaded';
        break;
        
    case UPLOAD_ERR_NO_FILE:
        $error = 'No file was selected for upload';
        break;
        
    case UPLOAD_ERR_NO_TMP_DIR:
    case UPLOAD_ERR_CANT_WRITE:
        $error = 'Server Error';
        break;
}

// report error
if ($error != null) {
    $_SESSION[ADMIN_KEY]['err'] = $error;
    redirect ('import_csv.php');
}

// determine the random filename
$filename = 'temp/'. generate_code (10);
while (file_exists ($filename)) {
    $filename = 'temp/'. generate_code (10);
}

// try to move it
if (!move_uploaded_file ($_FILES['csv']['tmp_name'], $filename)) {
    $_SESSION[ADMIN_KEY]['err'] = 'Unable to move file to temp directory';
    redirect ('import_csv.php');
}

// open file handle
$fh = @fopen ($filename, 'r');
if ($fh === false) {
    $_SESSION[ADMIN_KEY]['err'] = 'Unable to open file for reading';
    redirect ('import_csv.php');
}

// read headers from file
$hdrs = @fgetcsv ($fh);
if ($hdrs === false) {
    $_SESSION[ADMIN_KEY]['err'] = 'Unable to read headers from file';
    redirect ('import_csv.php');
}

// try to read some lines
$lines = array ();
$index = 0;
$line = @fgetcsv ($fh);
while ($line !== false) {
    $lines[] = $line;
    $index++;
    if ($index == 4) break;
    $line = @fgetcsv ($fh);
}

// report an error if there are none
if (count ($lines) == 0) {
    $_SESSION[ADMIN_KEY]['err'] = 'Unable to read any lines from file';
    redirect ('import_csv.php');
}

// close file
fclose ($fh);


// save our state
$_SESSION[ADMIN_KEY]['import_csv'] = array ();
$_SESSION[ADMIN_KEY]['import_csv']['filename'] = $filename;
$_SESSION[ADMIN_KEY]['import_csv']['headers'] = $hdrs;
$_SESSION[ADMIN_KEY]['import_csv']['lines'] = $lines;
$_SESSION[ADMIN_KEY]['import_csv']['table'] = $_POST['table'];

// redirect
redirect ('import_csv2.php');
?>
