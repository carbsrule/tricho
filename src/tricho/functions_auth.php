<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Runtime;
use Tricho\Meta\Database;
use Tricho\Query\RawQuery;

/**
 * Checks to see that a site user (member) is logged in
 *
 * Redirects to login.php on failure
 *
 * @param bool $redirect_on_error whether or not to push the user back to the login form if the check fails
 *     (true by default).
 * @return bool true if logged in, false otherwise
 */
function test_login ($redirect_on_error = true) {
    if (empty($_SESSION['user']['id'])) {
        if ($redirect_on_error) {
            $_SESSION['user']['err'] = 'You have not logged in, or your session has expired';

            $url = dirname ($_SERVER['PHP_SELF']). '/login.php';
            if (count ($_POST) == 0) {
                $url .= '?redirect='. urlencode ($_SERVER['REQUEST_URI']);
            }
            redirect ($url);

        } else {
            return false;
        }
    } else {
        return true;
    }
}

/**
 * Checks to see that an administrator is logged in to the current admin section.
 *
 * Redirects to [admin]/login.php on failure ([admin] could also be, for example, user_admin)
 *
 * @param bool $redirect_on_error whether or not to push the user back to the login form if the check fails
 *     (true by default).
 * @return bool true if logged in, false otherwise
 */
function test_admin_login ($redirect_on_error = true) {
    if (!empty($_SESSION[ADMIN_KEY]['id'])) {
        return true;
    }
    if ($redirect_on_error) {
        $err = 'You have not logged in, or your session has expired';
        $_SESSION[ADMIN_KEY]['err'] = $err;
        $url = ROOT_PATH_WEB . ADMIN_DIR . 'login.php';
        if (count($_POST) == 0 && !empty($_SERVER['REQUEST_URI'])) {
            $url .= '?redirect=' . urlencode($_SERVER['REQUEST_URI']);
        }
        redirect ($url);
    } else {
        return false;
    }
}

/**
 * Checks to see that a database setup user is logged in.
 *
 * Redirects to admin/setup/login.php on failure
 *
 * @param bool $redirect_on_error whether or not to push the user back to the login form if the check fails
 *     (true by default).
 * @param int $access_level The minimum level of access that is required
 *     (SETUP_ACCESS_LIMITED or SETUP_ACCESS_FULL). By default, SETUP_ACCESS_FULL is required.
 * @return bool true if logged in, false otherwise
 */
function test_setup_login ($redirect_on_error = true, $access_level = SETUP_ACCESS_FULL) {

    if (@$_SESSION['setup']['id'] == '' or
            (((int) $_SESSION['setup']['level']) < $access_level)) {
        if ($redirect_on_error) {
            $_SESSION[ADMIN_KEY]['err'] = 'You have not logged in, or your session has expired';
            $url = ROOT_PATH_WEB . ADMIN_DIR . 'login.php';
            if (count ($_POST) == 0) {
                $url .= '?redirect='. urlencode ($_SERVER['REQUEST_URI']);
            }
            redirect ($url);
        } else {
            return false;
        }
    } else {
        return true;
    }
}


/**
 * Checks to see if an admin username and password combination is valid.
 *
 * @author benno 2008-07-01, benno 2013-09-17
 *
 * @param string $user the username
 * @param string $pass the password
 * @param int $num_tables the number of tables currently defined in the database.
 *        If no tables are defined, the username 'install', combined with the
 *        install password (install_pw in Runtime) will be accepted, so that the
 *        user can create the database.
 * @return int the access level that the user has (-1 if the credentials failed)
 */
function authorise_admin($user, $pass, $num_tables) {

    // Allow an 'install' login if no database tables have yet been created
    if ($num_tables == 0) {
        $install_pw = Runtime::get('install_pw');
        if ($user == 'install' and $pass == $install_pw and $install_pw != '') {
            return SETUP_ACCESS_FULL;
        }
        return -1;
    }

    $db = Database::parseXML();
    $table = $db->get('_tricho_users');

    // If the setup users table isn't defined in the XML, the salt used is
    // unknown, so the login can't be authorised
    if ($table == null) return -1;

    $q = "SELECT AccessLevel, Pass
        FROM `_tricho_users`
        WHERE User = " . sql_enclose($user) . "
        LIMIT 1";
    $q = new RawQuery($q);
    $q->set_internal(true);
    try {
        $res = execq($q);
    } catch (QueryException $ex) {
        return -1;
    }

    $row = $res->fetch();
    if (!$row) return -1;

    if ($table->get('Pass')->matchEncrypted($pass, $row['Pass'])) {
        return (int) $row['AccessLevel'];
    }

    return -1;
}


/**
 * Checks to see if the user's IP address is locked out
 *
 * @author benno, 2008-08-03
 *
 * @param Database $db the database meta-data created from tables.xml.
 *     This is used to see if the XML knows about the _tricho_login_failures table
 * @param bool $extend_period if true, and the user is locked out, starts the lockout period again from the present moment
 *
 * @return bool true if the user's IP is locked out
 */
function ip_locked_out (Database $db, $extend_period) {

    if ($db->get('_tricho_login_failures') == null) {
        throw new LogicException('Missing core table _tricho_login_failures');
    }

    $res = execq("SELECT ID
        FROM `_tricho_login_failures`
        WHERE IP = '{$_SERVER['REMOTE_ADDR']}'
            AND LockedUntil >= NOW() AND Active = 1
        LIMIT 1");
    if ($row = $res->fetch()) {
        // use default lockout period if not specified
        if (defined ('IP_LOCKOUT_PERIOD') and is_int (IP_LOCKOUT_PERIOD)) {
            $lockout_period = IP_LOCKOUT_PERIOD;
        } else {
            $lockout_period = DEFAULT_LOCKOUT_PERIOD;
        }

        if ($extend_period) {
            execq("UPDATE `_tricho_login_failures`
                SET LockedUntil = DATE_ADD(NOW(), INTERVAL {$lockout_period} MINUTE)
                WHERE ID = {$row['ID']}");
        }

        return true;
    } else {
        return false;
    }
}


/**
 * Records a failed login against the user's IP address, and locks the user's IP address if necessary.
 * This function should only ever be called from a login action page, and only after {@link ip_locked_out}
 *     has been called.
 *
 * @author benno, 2008-08-03
 *
 * @param string $user the username used for the login attempt
 *
 * @return int the number of tries left before the user is locked out (if 0, the user is locked out)
 */
function record_failed_login ($user) {

    $res = execq("SELECT COUNT(*)
        FROM `_tricho_login_failures`
        WHERE IP = '{$_SERVER['REMOTE_ADDR']}' AND Active = 1");
    $row = fetch_row($res);
    $q = "INSERT INTO `_tricho_login_failures`
            SET Time = NOW(), User = ". sql_enclose ($user). ", IP = '{$_SERVER['REMOTE_ADDR']}', Active = 1";
    $tries_left = IP_LOCKOUT_NUM_FAILURES - $row[0] - 1;
    if ($tries_left <= 0) {
        $tries_left = 0;

        if (defined ('IP_LOCKOUT_PERIOD') and is_int (IP_LOCKOUT_PERIOD)) {
            $lockout_period = IP_LOCKOUT_PERIOD;
        } else {
            $lockout_period = DEFAULT_LOCKOUT_PERIOD;
        }

        $q .= ", LockedUntil = DATE_ADD(NOW(), INTERVAL {$lockout_period} MINUTE)";
    }

    execq($q);

    return $tries_left;
}


/**
 * Clears the record of failed login attempts for the user's IP address.
 * This function is to be called when a user successfully logs in.
 *
 * @author benno, 2008-08-03
 *
 * @return void
 */
function clear_failed_logins () {
    execq("UPDATE `_tricho_login_failures`
        SET Active = 0
        WHERE IP = '{$_SERVER['REMOTE_ADDR']}'");
}
?>
