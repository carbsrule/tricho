<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require __DIR__ . '/tricho/runtime.php';
tricho\Runtime::set('root_path', __DIR__ . '/', true);
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
