<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

if (version_compare(PHP_VERSION, '5.4.0') < 0) {
    @ob_end_clean();
    if (!empty($_SERVER['SERVER_PROTOCOL'])) {
        @header("{$_SERVER['SERVER_PROTOCOL']} 500 Internal Server Error");
    }
    @header('Content-type: text/plain');
    echo "Requires PHP 5.4 or greater\n";
    exit(1);
}

// Output buffering is always on so that errors can be handled correctly, even
// mid-way through a page which is outputting data
ob_start();

require __DIR__ . '/tricho/runtime.php';
Tricho\Runtime::set('root_path', __DIR__ . '/', true);
require __DIR__ . '/tricho/functions_base.php';
require __DIR__ . '/tricho/autoload.php';

// Pretend to be a test server while detecting the environment and loading the
// appropriate config. If this fails, e.g. because the detection script
// doesn't exist, the error MUST be shown.
Tricho\Runtime::set('live', false);

// N.B. use include, because require doesn't pass the ErrorException thrown
// by tricho_error_handler to tricho_exception_handler for some reason, and
// therefore presents a blank screen with no error message.
include __DIR__ . '/tricho/config/detect.php';

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
