<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho;

use Tricho\Runtime;
use Tricho\DataUi\Form;
use Tricho\Meta\Database;
use Tricho\Meta\CharColumn;
use Tricho\Query\InsertQuery;
use Tricho\Util\SqlParser;

/**
 * Used for installing Tricho on a new server
 */
class Installer {
    
    private function loadForm($id) {
        $form = new Form($id);
        $form->load(__DIR__ . '/installer.form.xml');
        $form->setFormURL(Runtime::get('installer_path'));
        $form->setActionURL(Runtime::get('installer_path'));
        
        $path = Runtime::get('installer_path');
        $parts = explode('/', trim($path, '/'));
        
        // Remove tricho/install.php to get base path
        array_pop($parts);
        array_pop($parts);
        $path = '/';
        if (count($parts)) $path .= implode('/', $parts) . '/';
        $path .= 'admin/';
        $form->setSuccessURL($path);
        
        $modifier = new InstallerFormModifier();
        $form->setModifier($modifier);
        
        return $form;
    }
    
    
    function install(array $data) {
        $form = $this->loadForm($_POST['_f']);
        $form->process(null, [], ['retain_session' => true]);
    }
    
    
    function renderForm() {
        $form = $this->loadForm(@$_GET['f']);
        $id = $form->getID();
        if (!isset($_SESSION['forms'][$id])) {
            $_SESSION['forms'][$id] = ['values' => [], 'errors' => []];
        }
        $session = &$_SESSION['forms'][$id];
        
        $defaults = [
            'live_host' => 'livebox',
            'db_type' => 'Mysql',
            'db_host' => '127.0.0.1',
            'db_port' => '3306',
        ];
        foreach ($defaults as $key => $val) {
            if (empty($session['values'][$key])) {
                $session['values'][$key] = $val;
            }
        }
        if (!isset($session['errors'])) {
            $session['errors'] = [];
        }
        return $form->render($session['values'], $session['errors']);
    }
    
    
    
    
    private function NAH_DONT_RUN() {
    
    if (count($_POST) > 0) {
        
        
        
        execq("START TRANSACTION");
        
        $db = Database::parseXML($xml_loc);
        foreach ($db->getTables() as $table) {
            $q = $table->getCreateQuery();
            execq($q);
        }
        
        $parser = new SqlParser();
        $queries = $parser->parse(file_get_contents('install/tlds.sql'));
        if (!$queries) {
            die('<p><strong>Error:</strong> missing TLD values.</p>');
        }
        foreach ($queries as $q) execq($q);
        
        // user insert
        $db = Database::parseXML('install/tables.xml');
        $table = $db->get('_tricho_users');
        if (!$table) {
            die('<p><strong>Error:</strong> missing definition of _tricho_users.</p>');
        }
        $q = new InsertQuery($table, array(
            'User' => $_POST['user'],
            'Pass' => $table->get('Pass')->encrypt($_POST['pass']),
            'AccessLevel' => 2
        ));
        execq($q);
        
        execq('COMMIT');
        
        unset($_SESSION['install']);
        
        $_SESSION['admin']['id'] = $_POST['user'];
        $_SESSION['setup']['id'] = $_POST['user'];
        $_SESSION['setup']['level'] = 2;
        
        redirect(ROOT_PATH_WEB . "admin/");
    }
    }
    
}
