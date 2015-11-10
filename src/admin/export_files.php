<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Runtime;
use Tricho\Meta\Database;
use Tricho\Meta\FileColumn;

/**
 * Exports the files saved in admin-driven directories as a zip archive.
 * 
 * Parameters that can be passed in via GET are:
 * debug: y or n (default); whether or not to run in debug mode (gives debug output instead of file download)
 * del: y (default) or n; whether or not to delete the created zip file after it has been sent to the browser
 */

require_once '../tricho.php';
test_admin_login ();

// allow the script to run for 15 minutes
set_time_limit (900);

$debug_mode = false;
if (in_array(@$_GET['debug'], array('true', 'y', 'Y', '1'))) {
    $debug_mode = true;
}

$delete_archive = true;
if (in_array(@$_GET['del'], array('false', 'n', 'N', '0'))) {
    $delete_archive = false;
}

$db = Database::parseXML();

$tables = $db->getTables ();
$dirs = array ();
$root = Runtime::get('root_path');
foreach ($tables as $table) {
    $cols = $table->getColumns ();
    foreach ($cols as $col) {
        if (!($col instanceof FileColumn)) continue;
        $store_loc = $col->getStorageLocation();
        if ($store_loc != '') {
            $store_loc = $root . $store_loc;
            if (!in_array ($store_loc, $dirs)) {
                $dirs[] = $store_loc;
            }
        }
    }
}

if ($debug_mode) echo "Dirs: <pre>", print_r ($dirs, true), "</pre>\n";

$files = array ();
foreach ($dirs as $dir) {
    get_files_list ($dir, $files);
}

if (count ($files) == 0) {
    require 'head.php';
    echo "<p class=\"confirm\">No files to export</p>\n";
    require 'foot.php';
    exit;
} else if (!is_dir ('temp') or !is_writeable ('temp')) {
    require 'head.php';
    report_error ('Unable to write to temp dir');
    require 'foot.php';
    exit (1);
}

if ($debug_mode) echo "Files: <pre>", print_r ($files, true), "</pre>\n";

$zip_archive = new ZipArchive ();
$attempt = 1;
$archive_ready = false;
do {
    $archive_file = 'temp/export_files_'. date ('Y-m-d'). '_'. str_pad ($attempt, 3, '0', STR_PAD_LEFT). '.zip';
    ++$attempt;
    if (!file_exists ($archive_file)) $archive_ready = true;
} while (!$archive_ready and $attempt < 100);

if (!$archive_ready) {
    require 'head.php';
    report_error ('Unable to create zip file, try deleting existing exports');
    require 'foot.php';
    exit (1);
}

if ($zip_archive->open ($archive_file, ZipArchive::CREATE) !== true) {
    require 'head.php';
    report_error ('Unable to create zip file, check directory permissions');
    require 'foot.php';
    exit (1);
}

if ($debug_mode) echo "Created zip file: {$archive_file} (", (int) @filesize ($archive_file), "B)<br>\n";

foreach ($files as $file) {
    $short_name = str_replace($root, '', $file);
    if ($debug_mode) {
        echo "Adding file {$short_name}... ";
        flush ();
        ob_flush ();
    }
    $res = $zip_archive->addFile ($file, $short_name);
    if ($debug_mode) {
        echo ($res? 'OK': 'FAILED'), "<br>\n";
    } else if (!$res) {
        
        require 'head.php';
        $zip_archive->close ();
        $error_text = "Failed to add file to zip archive: {$short_name} (file size, ".
            bytes_to_human ((int) @filesize ($file)). ', archive size: '.
            bytes_to_human ((int) @filesize ($archive_file)). ')';
        report_error ($error_text);
        if ($delete_archive) @unlink ($archive_file);
        require 'foot.php';
        exit (1);
        
    }
}
$zip_archive->close ();

if ($debug_mode) {
    echo "<br><br>\nFinished, archive size: ", bytes_to_human ((int) filesize ($archive_file)), "<br>\n";
} else {
    
    $out_file = @fopen ($archive_file, 'r');
    if (!$out_file) {
        require 'head.php';
        report_error ('Unable to read zip file');
        if ($delete_archive) @unlink ($archive_file);
        require 'foot.php';
    }
    
    $safe_name = str_replace(' ', '_', Runtime::get('site_name'));
    $safe_name = preg_replace("/[^A-Za-z0-9_\-]+/", '', $safe_name);
    $safe_name = strtolower($safe_name);
    $file_name = $safe_name . "_export_files_" . date ('Y-m-d') . '.zip';
    
    header ('Content-type: application/zip');
    header ("Content-Disposition: attachment; filename={$file_name}");
    header ("Cache-Control: cache, must-revalidate");
    header ("Pragma: public");
    header ("Content-length: ". filesize ($archive_file));
    
    ob_end_flush ();
    ob_implicit_flush (true);
    while (!feof ($out_file)) {
        echo fread ($out_file, 8192);
    }
    fclose ($out_file);
    
}

if ($delete_archive) @unlink ($archive_file);
exit;

/* *****************************************************************************
*                                                                                                                                                            *
*    Script ends here, function definitions follow below                                                 *
*                                                                                                                                                            *
*******************************************************************************/

/**
 * 
 * Gets the list of files in a directory and its subdirectories (excluding
 * hidden files)
 * 
 * @param $dir The directory to read files from
 * @param &$files An array in which to store the file names found
 * 
 * @return void The list of file names found are added to the referenced $files array
 * 
 * @todo move into Tricho core somewhere?
 * 
 */
function get_files_list ($dir, &$files = array ()) {
    
    global $debug_mode;
    
    if ($dir_files = @scandir ($dir)) {
        foreach ($dir_files as $file) {
            if ($file[0] == '.') continue;
            if ($debug_mode) echo $dir, ' has file ', $file, "<br>\n";
            $file = $dir. '/'. $file;
            if (is_dir ($file)) {
                get_files_list ($file, $files);
            } else if (is_file ($file)) {
                $files[] = $file;
            }
        }
    }
}
