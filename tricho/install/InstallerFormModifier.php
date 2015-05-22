<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho;

use Tricho\DataUi\Form;
use Tricho\DataUi\FormModifier;
use Tricho\DbConn\ConnManager;
use Tricho\Meta\Database;
use Tricho\Query\InsertQuery;
use Tricho\Query\RawQuery;
use Tricho\Util\SqlParser;

class InstallerFormModifier extends FormModifier {
    private $form = null;
    
    private function errorRedir($err) {
        $_SESSION['err'][] = $err;
        redirect($this->form->getFormURL() . '?f=' . $this->form->getID());
    }
    
    function postProcess(Form $form, array $data, $insert_id, array $codes) {
        $this->form = $form;
        
        $root = Runtime::get('root_path');
        
        // 1 Establish database connection using new details
        $conn_class = 'Tricho\\DbConn\\' . $data['db_type'] . 'Conn';
        $params = [
            'host' => $data['db_host'],
            'port' => $data['db_port'],
            'user' => $data['db_user'],
            'pass' => $data['db_pass'],
            'db' => $data['db_name'],
        ];
        $conn = new $conn_class($params);
        try {
            $conn->connect();
        } catch (\PDOException $ex) {
            $err = 'Database connection failed: ' . $ex->getMessage();
            $this->errorRedir($err);
        }
        ConnManager::add($conn);
        
        // 2 check _tricho_users has no data
        $q = new RawQuery("SHOW TABLES LIKE '_tricho%'");
        $q->set_internal(true);
        $res = execq($q);
        if ($row = fetch_row($res)) {
            $this->errorRedir('Database already installed :)');
        }
        
        // 3 Copy config files, with modified content
        $master_salt = false;
        $files = ['all.php', 'detect.php', 'live.php', 'dev.php'];
        foreach ($files as $file) {
            if ($file == 'live.php') {
                $dest = $_POST['live_host'];
                $dest = preg_replace('/[^a-z0-9_\-\.]/i', '', $dest);
                $dest .= '.php';
            } else {
                $dest = $file;
            }
            $src = $root . 'tricho/config/examples/' . $file;
            $dest = $root . 'tricho/config/' . $dest;
            
            $content = file_get_contents($src);
            if (!$content) {
                $this->errorRedir('Failed to read example config file');
            }
            $changes = array();
            if ($file == 'all.php') {
                $master_salt = generate_code(40);
                $changes["'master_salt'"] = $master_salt;
            } else if ($file == 'live.php') {
                $changes["_SERVER['SERVER_NAME']"] = $data['live_domain'];
                $changes["'site_name'"] = $data['site_name'];
            } else if ($file == 'dev.php') {
                $changes["_SERVER['SERVER_NAME']"] = $data['test_domain'];
                $changes["'site_name'"] = $data['site_name'];
                $changes["'class'"] = $data['db_type'] . 'Conn';
                $changes["'host'"] = $data['db_host'];
                $changes["'port'"] = $data['db_port'];
                $changes["'db'"] = $data['db_name'];
                $changes["'user'"] = $data['db_user'];
                $changes["'pass'"] = $data['db_pass'];
                $changes['SITE_EMAIL'] = "{$data['email_from_name']} <{$data['email_from_address']}>";
                $changes['SITE_EMAILS_ERROR'] = $data['email_admin'];
                $changes['SITE_EMAILS_ADMIN'] = $data['email_admin'];
            }
            
            foreach ($changes as $field => $value) {
                if (!preg_match('/^[0-9]+$/', $value)) {
                    $value = "'" . str_replace("'", "\\'", $value) . "'";
                }
                
                if (preg_match('/^[A-Z_]+$/', $field)) {
                    $pattern = "/(define\s*\('{$field}'),\s*'[^']*'\)/";
                    $content = preg_replace($pattern, "$1, {$value})", $content);
                    continue;
                }
                
                $pattern = '/(' . preg_quote($field, '/');
                $pattern .= ") (=>?) ('[^']*'|[0-9]+)/";
                $content = preg_replace($pattern, "$1 $2 {$value}", $content);
            }
            
            $ok = file_put_contents($dest, $content);
            if (!$ok) {
                $this->errorRedir('Failed to write config file');
            }
        }
        if (!$master_salt) throw new Exception('Master salt not generated');
        
        // 4 Copy xml definitions across (admin*.form.xml + tables.xml)
        $data_dir = $root . 'tricho/data/';
        if (!copy('install/tables.xml', "{$data_dir}tables.xml")) {
            $this->errorRedir('Failed to copy tables.xml');
        }
        $forms = glob('install/*.form.xml');
        foreach ($forms as $form) {
            $file = basename($form);
            if (!copy($form, "{$data_dir}{$file}")) {
                $this->errorRedir("Failed to copy {$file}");
            }
        }
        
        // 5 Run CREATE TABLE queries for each XML definition
        execq("START TRANSACTION");
        
        $db = Database::parseXML("{$data_dir}tables.xml");
        foreach ($db->getTables() as $table) {
            $q = $table->getCreateQuery();
            execq($q);
        }
        
        // 6 Run *.sql files
        $parser = new SqlParser();
        $queries = $parser->parse(file_get_contents('install/tlds.sql'));
        if (!$queries) $this->errorRedir('Missing TLD values');
        foreach ($queries as $q) execq($q);
        
        // 7 Insert admin user
        Runtime::set('master_salt', $master_salt);
        $table = $db->get('_tricho_users');
        $q = new InsertQuery($table, [
            'User' => $_POST['admin_user'],
            'Pass' => $table->get('Pass')->encrypt($_POST['admin_pass']),
            'AccessLevel' => 2,
        ]);
        execq($q);
        execq('COMMIT');
        
        unset($_SESSION['forms'][$this->form->getID()]);
        
        $_SESSION['admin']['msg'] = 'Tricho is now installed :)';
    }
}
