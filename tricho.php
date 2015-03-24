<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

if (version_compare(PHP_VERSION, '5.4.0') < 0) {
    ob_end_clean();
    @header('Content-type: text/html');
    echo "<html><head><title>ERROR</title></head>\n";
    echo "<body><b>Error:</b> Requires PHP 5.4 or greater</body></html>";
    exit(1);
}

// Output buffering is always on so that errors can be handled correctly, even
// mid-way through a page which is outputting data
ob_start();

require __DIR__ . '/tricho/runtime.php';
Tricho\Runtime::set('root_path', __DIR__ . '/', true);
require __DIR__ . '/tricho/functions_base.php';
require __DIR__ . '/tricho/autoload.php';

require __DIR__ . '/tricho/config/detect.php';
require __DIR__ . '/tricho/runtime_defaults.php';

require __DIR__ . '/tricho/constants.php';
require __DIR__ . '/tricho/functions_db.php';
require __DIR__ . '/tricho/functions_admin.php';
require __DIR__ . '/tricho/functions_time.php';
require __DIR__ . '/tricho/functions_richtext.php';
require __DIR__ . '/tricho/functions_auth.php';
require __DIR__ . '/tricho/images.php';

check_config();

session_start();
validate_session();
?>
