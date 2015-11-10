<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

use Tricho\Meta\Table;

/**
 * returns true if a table name is valid
 */
function table_name_valid ($s) {
    
    if (trim ($s) == '') return false;
    
    $len = strlen ($s);
    for ($x = 0; $x < $len; $x++) {
        switch ($s[$x]) {
            case '.':
            case '/':
            case '\\':
            case '`':
                return false;
        }
    }
    
    return true;
}


/**
 * Returns an array [0] = major, [1] = minor, [2] = rev
 */
function get_mysql_version () {
    $q = "SELECT VERSION()";
    $res = execq($q);
    $version = $res->fetchColumn(0);
    
    list ($major, $minor, $rev) = explode ('.', $version);
    list ($rev, $tag) = explode ('-', $rev);
    
    return array ($major, $minor, $rev);
}


/**
 * Returns true if the mysql version is at least the specified version
 */
function mysql_version_at_least ($major, $minor, $rev) {
    list ($our_major, $our_minor, $our_rev) = get_mysql_version ();
    
    if ($our_major > $major) {
        return true;
        
    } else if ($our_major == $major) {
        // major is equal, check minor
        if ($our_minor > $minor) {
            return true;
            
        } else if ($our_minor == $minor) {
            // minor is equal, check rev
            if ($our_rev >= $rev) {
                return true;
            }
        }
    }
    
    return false;
}


/**
 * Gets the available character sets and collations that match the allowed charsets in SQL_CHARSETS.
 * This involves fetching the list of supported collations from the MySQL server.
 * Charsets and collations are both listed in preferential order.
 * 
 * @author benno 2010-01
 * 
 * @return array each element has the charset name as the key, and an array of
 *     the available collations as the value, e.g.:
 *     array (
 *         'utf8' => array ('utf8_general_ci', 'utf8_unicode_ci', 'utf8_bin')
 *     )
 */
function get_available_collation_mappings () {
    
    static $allowed_charsets = array ();
    if (count ($allowed_charsets) == 0) {
        $allowed_charsets = preg_split ('/,\s*/', SQL_CHARSETS);
    }
    
    static $preferred_mappings = array (
        'utf8' => array (
            'utf8_unicode_ci',
            'utf8_general_ci',
            'utf8_bin'
        ),
        'latin1' => array (
            'latin1_general_ci',
            'latin1_general_cs',
            'latin1_bin'
        )
    );
    
    $available_mappings = array ();
    $res = execq("SHOW COLLATION");
    while ($row = $res->fetch()) {
        if (in_array ($row['Charset'], $allowed_charsets)) {
            $available_mappings[$row['Charset']][] = $row['Collation'];
        }
    }
    
    // Put preferred collations first
    $sorted_collations = array ();
    foreach ($preferred_mappings as $charset => $preferred_collations) {
        if ($available_collations = $available_mappings[$charset]) {
            foreach ($preferred_collations as $wanted_collation) {
                $key = array_search ($wanted_collation, $available_collations);
                if ($key !== false) {
                    $sorted_collations[$charset][] = $wanted_collation;
                    unset ($available_mappings[$charset][$key]);
                }
            }
        }
    }
    
    // Put all other collations after, in alphabetical order
    asort ($available_mappings);
    foreach ($available_mappings as $charset => $collations) {
        asort ($collations);
        $sorted_collations[$charset] = array_merge ((array) $sorted_collations[$charset], $collations);
    }
    
    return $sorted_collations;
}


/**
 * Gets the available MySQL table engines, in preferential order.
 * This involves fetching the list of supported engines from the MySQL server.
 * 
 * @author benno 2010-01
 * 
 * @return array each element is an engine name, e.g. MyISAM
 */
function get_available_engines () {
    
    static $allowed_engines = array ();
    if (count ($allowed_engines) == 0) {
        $allowed_engines = preg_split ('/,\s*/', SQL_ENGINES);
    }
    
    $available_engines = array ();
    $res = execq("SHOW ENGINES");
    $ignore = array('NO', 'DISABLED');
    while ($row = $res->fetch()) {
        if (!in_array ($row['Support'], $ignore)) $available_engines[] = $row['Engine'];
    }
    
    $sorted_engines = array ();
    foreach ($allowed_engines as $engine) {
        if (in_array ($engine, $available_engines)) $sorted_engines[] = $engine;
    }
    
    return $sorted_engines;
}


/**
 * Gets the collation of a table. Note that this requires a database lookup.
 * @param mixed $table a Table, or a string containing the table name
 * @return mixed A string of the table's collation, or false if there was an
 *     error getting the information from the database
 * @author benno 2011-08-02
 */
function get_table_collation ($table) {
    if ($table instanceof Table) {
        $table = $table->getName ();
    }
    $table = (string) $table;
    if (strpos ($table, '`') !== false) {
        throw new Exception ('Invalid table name');
    }
    $result = false;
    $res = execq('SHOW TABLE STATUS LIKE '. sql_enclose ($table));
    while ($row = fetch_assoc($res)) {
        if ($row['Name'] == $table) return $row['Collation'];
    }
    return $result;
}


/**
 * Prints the columns defined so far in the table creation process
 * @param array $columns the columns (each of which is a config array, not a Column object)
 * @param int $curr_col the current column (to list that column in bold)
 * @return void
 * @author benno 2010-11-11, 2011-08-05
 */
function table_create_list_columns ($columns, $curr_col = 0) {
    echo "<table class=\"table_cols\">\n";
    echo "<tr>",
        "<th>&nbsp;</th>",
        "<th>&nbsp;</th>",
        "<th>Name</th>",
        "<th colspan=\"2\" align=\"left\">Type</th>",
        "<th>&nbsp;</th>",
        "</tr>\n";
    foreach ($columns as $id => $col) {
        
        $sql_defn = $col['sqltype'];
        if ($col['sql_size'] != '') $sql_defn .= '('. $col['sql_size']. ')';
        if (count ($col['sql_attribs']) > 0) $sql_defn .= ' '. implode (' ', $col['sql_attribs']);
        
        echo "<tr><td>$id</td>\n";
        echo "<td class=\"mandatory\">\n";
        if (@$col['mandatory']) {
            echo '<img src="', ROOT_PATH_WEB, IMAGE_MANDATORY, '" alt="*" title="Mandatory">';
        } else {
            echo '&nbsp;';
        }
        echo "</td>\n<td>";
        if ($id == $curr_col) echo "<b>";
        echo "{$col['name']}";
        if ($id == $curr_col) echo "</b>";
        echo "</td>\n";
        echo "<td>{$col['class']}</td>\n";
        echo "<td valign=\"bottom\"><small>{$sql_defn}</small></td>\n";
        echo "<td>",
            "<a href=\"table_create1.php?id={$id}\">Edit</a> ",
            "<a href=\"table_create_del_col.php?id={$id}&name={$col['name']}\">Delete</a>",
            "</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
}
?>
