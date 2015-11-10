<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

require_once __DIR__ . '/../../tricho.php';
class SelectQueryTest extends PHPUnit_Framework_TestCase {
    private static function mkname($name) {
        return str_replace('`', '', $name);
    }
    private static function init_db($func, array $cols) {
        $table = '`__' . self::mkname($func) . '`';
        $q = "DROP TABLE IF EXISTS {$table}";
        execq($q);
        $q = "CREATE TABLE {$table} (";
        $col_num = 0;
        foreach ($cols as $name => $conf) {
            if (++$col_num != 1) $q .= ', ';
            $q .= '`' . self::mkname($name) . "` {$conf}";
        }
        $q .= ')';
        execq($q);
    }
    private function fin_db($func) {
        $q = 'DROP TABLE `__' . self::mkname($func) . '`';
        execq($q);
    }
    
    public function testTable() {
        $cols = array('ID' => 'INT AUTO_INCREMENT PRIMARY KEY');
        self::init_db(__FUNCTION__, $cols);
        for ($i = 1; $i <= 5; ++$i) {
            $q = "INSERT INTO __testTable SET ID = (11 * $i)";
            execq($q);
        }
        
        $table = new Table();
        $table->setName('__' . self::mkname(__FUNCTION__));
        $id = new IntColumn('ID');
        $id->setTable($table);
        
        $qh = new SelectQuery($table);
        $qh->addSelectField(new QueryFunction('MAX', $id));
        execq($qh);
        self::fin_db(__FUNCTION__);
        $this->assertTrue(true);
    }
}
?>
