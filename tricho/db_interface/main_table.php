<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * @package main_system
 */

/**
 * Used to build the table seen on main.php which has rows showing a subset of the data in a database table.
 * 
 * @package main_system
 */
class MainTable {
    
    private static $next_table_id = 0;
    
    private $columns;
    private $visible_columns;
    private $primary_key_columns;
    private $select_query;
    private $options;
    private $text_blocks;
    private $import_stack;
    private $page_urls;
    private $search_cols;
    private $filter_skip_tables;
    private $inline_search = false;
    private $num_pre_filters;
    private $concat_func;
    private $view_type;
    
    /**
     * @param Table $table
     * @param int $view_type 'list' or 'export'
     */
    public function __construct (Table $table, $view_type = 'list') {
        $this->fields = new QueryFieldList ();
        $this->visible_columns = array ();
        $this->primary_key_columns = array ();
        $this->select_query = new SelectQuery ($table);
        $this->options = array (
            MAIN_OPTION_ALLOW_ADD => true,
            MAIN_OPTION_ALLOW_DEL => true,
            MAIN_OPTION_CONFIRM_DEL => true,
            MAIN_OPTION_CSV => false
        );
        $this->text_blocks = array (
            MAIN_TEXT_ADD_BUTTON => 'Add new %single%',
            MAIN_TEXT_DEL_BUTTON => 'Delete selected',
            MAIN_TEXT_DEL_POPUP => 'Delete the selected items?',
            MAIN_TEXT_CSV_BUTTON => 'Download CSV file',
            MAIN_TEXT_NO_RECORDS => 'No %multiple% available',
            MAIN_TEXT_NOT_FOUND => 'No %multiple% match your search terms',
            MAIN_TEXT_ADD_CONDITION => '+ Extra condition',
            MAIN_TEXT_APPLY_CONDITIONS => 'Search',
            MAIN_TEXT_CLEAR_CONDITIONS => 'X Clear',
        );
        $this->import_stack = array ();
        $this->page_urls = new MainUrlSet ();
        $this->search_cols = array ();
        $this->filter_skip_tables = array ();
        $this->view_type = $view_type;
        
        $this->initialiseDisplayParams ($table);
        $this->generateQuery ($table);
    }
    
    /**
     * Determines whether a column is an order column.
     * If so, it works out the order index (array counting, i.e. start at 0),
     * and what direction it is ordered in (i.e. ASC or DESC).
     * 
     * @param Column $column The column to check
     * @return array Element 0 is the order index (false if the column is not
     *         used for ordering), and element 1 is the direction
     *         (ORDER_DIR_ASC: 0 or ORDER_DIR_DESC: 1)
     */
    static function determine_order (Column $column) {
        $order = false;
        $order_dir = ORDER_DIR_ASC;
        $order_cols = $column->getTable ()->getOrder ('view');
        foreach ($order_cols as $order_key => $order_pair) {
            list ($order_col, $order_col_dir) = $order_pair;
            if ($order_col === $column) {
                $order = $order_key;
                if (strtolower ($order_col_dir) == 'desc') $order_dir = ORDER_DIR_DESC;
                break;
            }
        }
        return array ($order, $order_dir);
    }
    
    /**
     * Set up the english names, button text, etc for the main view
     * 
     * @param Table $table The base table
     */
    public function initialiseDisplayParams (Table $table) {
        
        list ($page_names, $seps) = $table->getPageUrls ();
        
        // Options for adding, deletion and CSV generation
        $this->setOption (MAIN_OPTION_ALLOW_ADD, $table->getAllowed ('add'));
        $this->setOption (MAIN_OPTION_ALLOW_DEL, $table->getAllowed ('del'));
        $this->setOption (MAIN_OPTION_CONFIRM_DEL, $table->getConfirmDel ());
        $this->setOption (MAIN_OPTION_CSV, $table->getAllowed ('export'));
        
        // Strings for buttons and alerts
        $texts = $table->getAltButtons ();
        if (@$texts['main_add'] != '') {
            $this->text_blocks[MAIN_TEXT_ADD_BUTTON] = $texts['main_add'];
        } else {
            $this->text_blocks[MAIN_TEXT_ADD_BUTTON] = str_replace ('%single%', strtolower ($table->getNameSingle ()),
            $this->text_blocks[MAIN_TEXT_ADD_BUTTON]);
        }
        if (@$texts['main_delete'] != '') {
            $this->text_blocks[MAIN_TEXT_DEL_BUTTON] = $texts['main_delete'];
        }
        if (@$texts['delete_alert'] != '') {
            $this->text_blocks[MAIN_TEXT_DEL_POPUP] = $texts['delete_alert'];
        }
        if (@$texts['main_csv'] != '') {
            $this->text_blocks[MAIN_TEXT_CSV_BUTTON] = $texts['main_csv'];
        }
        if (@$texts['add_cond'] != '') {
            $this->text_blocks[MAIN_TEXT_ADD_CONDITION] = $texts['add_cond'];
        }
        if (@$texts['apply_conds'] != '') {
            $this->text_blocks[MAIN_TEXT_APPLY_CONDITIONS] = $texts['apply_conds'];
        }
        if (@$texts['clear_conds'] != '') {
            $this->text_blocks[MAIN_TEXT_CLEAR_CONDITIONS] = $texts['clear_conds'];
        }
        if (@$texts['no_records'] != '') {
            $this->text_blocks[MAIN_TEXT_NO_RECORDS] = $texts['no_records'];
        } else {
            $this->text_blocks[MAIN_TEXT_NO_RECORDS] = str_replace ('%multiple%', strtolower ($table->getEngName ()),
            $this->text_blocks[MAIN_TEXT_NO_RECORDS]);
        }
        if (@$texts['not_found'] != '') {
            $this->text_blocks[MAIN_TEXT_NOT_FOUND] = $texts['not_found'];
        } else {
            $this->text_blocks[MAIN_TEXT_NOT_FOUND] = str_replace ('%multiple%', strtolower ($table->getEngName ()),
            $this->text_blocks[MAIN_TEXT_NOT_FOUND]);
        }
        
        // Alternate pages (instead of main.php, main_edit.php, etc)
        foreach ($page_names as $original_page_name => $url) {
            $page_name = strtoupper ($original_page_name);
        
            if ($page_name == 'MAIN') {
                $page_id = MAIN_PAGE_MAIN;
            } else {
                if (substr ($page_name, 0, 5) == 'MAIN_') $page_name = substr ($page_name, 5);
                $page_name = 'MAIN_PAGE_'. $page_name;
                if (defined ($page_name)) {
                    $page_id = constant ($page_name);
                } else {
                    throw new Exception ("Don't know about page {$original_page_name}");
                }
            }
            $this->page_urls->set ($page_id, $url, $seps[$original_page_name]);
        }
    }
    
    /**
     * Build a query for use in the main view (or export) using the columns
     * defined in a base table
     * 
     * @param Table $table The base table
     */
    public function generateQuery (Table $table) {
        $debug = false;
        
        if ($debug) echo "Processing base table ", $table->getName (), "<br>\n";
        
        $pk_col_num = 1;
        $pk_cols = $table->getIndex ('PRIMARY KEY');
        foreach ($pk_cols as $pk_col) {
            $pk_query_col = new AliasedColumn ($table, $pk_col, '_PK'. $pk_col_num++);
            $this->addField ($pk_query_col, null, true, false, false);
        }
        
        $view_items = $table->getView ($this->view_type);
        $view_columns = array ();
        
        // Don't include columns that link to the parent if accessed via parent
        $parent = null;
        if (@$_GET['p'] != '') {
            if ($debug) echo "Determining parent...<br>\n";
            list ($ancestor) = explode (',', $_GET['p']);
            list ($parent_name, $parent_pk) = explode ('.', $ancestor);
            if ($debug) echo "Parent: {$parent_name}<br>\n";
            $parent = $table->getParent ($parent_name);
        }
        
        foreach ($view_items as $item) {
            if ($item instanceof FunctionViewItem) {
                if ($debug) echo "Processing FunctionViewItem<br>\n";
                
                $function = new QueryFunction ('', new QueryFieldLiteral ($item->getCode (), false));
                $function->setAlias ($item->getName ());
                
                $this->addField ($function, true, false, false, false, 0);
                
            } else if ($item instanceof ColumnViewItem) {
                if ($debug) echo "Processing ColumnViewItem<br>\n";
                
                $column = $item->getColumn ();
                
                // Never show passwords on the main view
                if ($column instanceof PasswordColumn) continue;
                
                $view_columns[] = $column;
                
                $this->importColumn ($column, $parent, true);
                
            } else {
                echo '<p>Error: Invalid view item type ' . get_class ($item) . '</p>';
            }
        }
        
        // Process PKs and search columns
        $columns = $table->getColumns ();
        
        foreach ($columns as $column) {
            // Don't add duplicate column references
            if (in_array ($column, $view_columns, true)) {
                continue;
            }
            
            $this->importColumn ($column, $parent, false);
        }
    }
    
    /**
     * Process a column that belongs to the view specified in generateQuery, or
     * is searchable or used for ordering. The column will be added to the
     * query, and to the display table if required
     *
     * @param Column $column The column (from base table) to be imported
     * @param mixed $parent The parent table via which this main view is being
     *        accessed, otherwise null is given if the main view is not
     *        accessed via parent
     * @param bool $is_view_item True if the column belongs to the
     *        ColumnViewItem, otherwise False
     */
    public function importColumn ($column, $parent, $is_view_item) {
        $debug = false;
        
        $table = $column->getTable ();
        $q_table = $this->select_query->getBaseTable ();
        $search_cols = $table->getSearch ();
        
        // See if this column is searchable
        $search = false;
        $search_key = array_search ($column, $search_cols, true);
        if ($search_key !== false) {
            $search = array ($search_key, $column->getEngName ());
        }
        
        list ($order, $order_dir) = MainTable::determine_order ($column);
        
        // Don't include columns that link to the parent if accessed via parent
        if ($column->linksTo ($parent)) {
            if ($debug) echo "Ignoring column ", $column->getName (), " that links to ". $parent->getName (). "<br>";
            return;
        }
        
        $link_data = $column->getLink ();
        if (!($column instanceof LinkColumn)) {
            $main = false;
            
            if ($is_view_item) {
                if ($debug) {
                    echo "Including column <strong>",
                        $column->getTable()->getName(), '.',
                        $column->getName(), "</strong><br>\n";
                }
                
                $main = true;
            }
            
            $this->addField ($column, $main, false, $search, $order, $order_dir);
            
        } else {
            $this->concat_func = new QueryFunction ('CONCAT');
            $this->concat_func->setAlias ($column->getEngName ());
            
            if ($column->getTable ()->isColumnInView ($this->view_type, $column)) {
                $view = true;
            } else {
                $view = false;
            }
            
            $this->addField ($this->concat_func, $view, false, $search);
            
            //$q_col = new QueryColumn ($q_table, $column->getName (), null, $column->getType ());
            //$this->processDescriptors ($column, $q_col);
            
            list ($pos, $order_dir) = self::determine_order ($column);
            
            // The link descriptor is used for ordering, so it needs to be added to the select list,
            // otherwise there will be no alias to reference in the ORDER BY clause
            if (!$view and !$search) {
                $this->select_query->addSelectField($this->concat_func);
            }
            
            if ($pos !== false) {
                $this->select_query->addOrderBy (new OrderColumn ($this->concat_func, $order_dir), $pos);
            }
            
            $join = $this->select_query->autoJoin($column);
            
            $target_table = $join->getTable();
            $descriptors = $target_table->getRowIdentifier();
            if (count($descriptors) == 0) {
                $err = "LinkColumn target table {$target_table->getName()} " .
                    "has no row identifier";
                throw new Exception($err);
            }
            
            foreach ($descriptors as $part) {
                if ($part instanceof Column) {
                    $part = new AliasedColumn($target_table, $part);
                }
                $this->concat_func->addParam($part);
            }
        }
    }
    
    /**
     * Concatenates the link descriptors of a linked column into a single field,
     * adding necessary JOINs along the way.
     * 
     * @param Column $col The column from the table up the chain, that provides
     *        the join information
     * @param QueryColumn $q_col The query column that provides the join to
     *        this table. This is provided to ensure that the LEFT|INNER JOINs
     *        use the appropriate aliases.
     * @todo Remove 2nd parameter, it isn't being used and most likely is
     *       unnecessary since SelectQuery::autoJoin was added
     */
    function processDescriptors (Column $col, QueryColumn $q_col) {
        $debug = false;
        
        $link_data = $col->getLink ();
        if (!$link_data instanceof Link) throw new Exception ('Invalid column');
        
        $table = $link_data->getToColumn ()->getTable ();
        if ($debug) echo "Processing joined table: ", $table->getName (), "<br>\n";
        
        $to_column = $link_data->getToColumn ();
        $to_name = $table->getName (). '.'. $to_column->getName ();
        $from_table = $link_data->getFromColumn ()->getTable ();
        $from_name = $from_table->getName (). '.'. $col->getName ();
        list ($from_q_col, $to_q_col) = $this->select_query->autoJoin ($from_name, $to_name);
        if ($debug) echo "Created join from ", $from_q_col, " to ", $to_q_col, "<br>\n";
        
        $to_q_table = $to_q_col->getTable ();
        
        $description = $link_data->getDescription ();
        foreach ($description as $desc_col) {
            $desc_link = null;
            if (!is_string ($desc_col)) {
                $desc_link = $desc_col->getLink ();
            }
            
            // Process secondary links recursively
            if ($desc_link != null) {
                $q_col = new QueryColumn (
                    $to_q_table,
                    $desc_link->getFromColumn ()->getName (),
                    null,
                    $desc_link->getToColumn ()->getType ()
                );
                
                if ($debug) echo "SECONDARY calling processDescriptors {$desc_col}, {$q_col}<br>\n";
                
                $this->processDescriptors ($desc_col, $q_col);
                
            // Process normal columns
            } else {
                if (is_string ($desc_col)) {
                    $desc_q_col = new QueryFieldLiteral ($desc_col);
                } else if ($desc_col instanceof TemporalColumn) {
                    $desc_q_col = new DateTimeQueryColumn (
                        $to_q_table,
                        $desc_col->getName (),
                        null,
                        $desc_col->getType ()
                    );
                    $desc_q_col->setDateFormat ($desc_col->getParam ('date_format'));
                } else {
                    $desc_q_col = new QueryColumn ($to_q_table, $desc_col->getName ());
                }
                $this->concat_func->addParam ($desc_q_col);
            }
        }
    }
    
    /**
     * Adds a field to use in the query to get the rows for the HTML table
     * display, search parameters, or ordering.
     * 
     * @param QueryField $field the field to add.
     * @param bool $main True if the field will be displayed in the HTML table
     * @param bool $pk true if the field is a primary key field
     * @param mixed $search An integer array key if this field is to be
     *        searchable, false otherwise
     * @param mixed $order if this field is to be used for ordering, $order is
     *        an array index (0 or more) giving the significance of this column
     *        in the order columns; otherwise it is false
     * @param int $order_dir ORDER_DIR_ASC or ORDER_DIR_DESC
     */
    public function addField (
        QueryField $field,
        $main,
        $pk = false,
        $search = false,
        $order = false,
        $order_dir = ORDER_DIR_ASC)
    {
        $select_field_added = false;
        if ($main or $pk) {
            // Add to main view
            if ($main) $this->visible_columns[] = $field;
            
            // add to primary keys
            if ($pk) {
                $found = false;
                foreach ($this->primary_key_columns as $q_col) {
                    if ($q_col->getName() == $field->getName() and $q_col->getTable()->getName() == $field->getTable()->getName()) {
                        $found = true;
                        break;
                    }
                }
                if (! $found) {
                    $this->primary_key_columns[] = $field;
                }
            }
            
            $this->select_query->addSelectField ($field);
            $select_field_added = true;
        }
        
        // add to search
        if (@is_array ($search)) {
            $this->search_cols[$search[0]] = array ($field, $search[1]);
        }
        
        // add to order
        if ($order !== false) {
            if (! $select_field_added) $this->select_query->addSelectField ($field);
            $this->select_query->addOrderBy (new OrderColumn ($field, $order_dir), $order);
        }
        
        $this->fields->add ($field);
    }
    
    
    /**
     * Removes a column from display according to its position in the list
     * With the leftmost column being 0, going towards infinity rightwards
     * This only removes the column from view, not from order, sql or search
     * @param int $position The position of the column to not show anymore
     */
    public function removeColumnByPos ($position) {
        unset ($this->visible_columns [$position]);
    }
    
    
    /**
     * Add a table to the array of tables that should be skipped when filters
     * are applied
     * @param mixed $table The table to skip. (can be a string of the name or a
     *        MainTable)
     */
    public function addFilterSkipTable ($table) {
        if ($table instanceof MainTable) {
            $this->filter_skip_tables[] = $table->getName();
        } else {
            $this->filter_skip_tables[] = cast_to_string ($table);
        }
    }
    
    
    /**
     * Applies the supplied filters to the query used to build the main table
     * display
     * 
     * @param array $filters an array of {@link MainFilter}s
     */
    public function applyFilters ($filters) {
        
        // First, determine how many records would be returned without filters
        $count_select_query = clone $this->select_query;
        $count_select_query->removeAllSelectFields ();
        $count_select_query->clearOrderBy ();
        $count_select_query->dropJoins (SQL_JOIN_TYPE_LEFT);
        $count_func = new QueryFunction ('COUNT', array (new QueryFieldLiteral ('*', false)));
        $count_func->setAlias ('count');
        $count_select_query->addSelectField ($count_func);
        
        $q = cast_to_string ($count_select_query);
        
        if (@$_SESSION['setup']['view_q'] === true) {
            echo "<p style=\"font-size: 9px;\">Q: {$q}</p>\n";
        }
        
        $res = execq($q);
        $row = fetch_assoc($res);
        $this->num_pre_filters = $row['count'];
        
        
        // Apply the filters
        if (@is_array ($filters)) {
            
            // Build a set of conditions based on the filters
            $new_where_conditions = array ();
            $require_all = true;
            foreach ($filters as $filter_key => $filter) {
                // Regular Column
                if ($filter instanceof MainFilter) {
                    // create the condition and then add it
                    $cond = $filter->applyFilter($this);
                    if ($cond != null) {
                        $new_where_conditions[] = $cond;
                    }
                
                // Linked Column
                } else if ($filter instanceof MainJoinFilter) {
                    // check if table is in hate list
                    $joins = $filter->getJoinList();
                    $table = $joins[0]->getToTable();
                    if (in_array($table, $this->filter_skip_tables)) continue;

                    // create the condition and then add it
                    $cond = $filter->applyFilter($this);
                    if ($cond != null) {
                        $new_where_conditions[] = $cond;
                    }
                
                // Other
                } else if ($filter_key == '_match_type') {
                    if ($filter == 'any') {
                        $require_all = false;
                    }
                }
            }
            
            
            /* build query based on the where conditions that were generated above */
            if (count($new_where_conditions) > 0) {
                
                //// Match all ////
                if ($require_all) {
                    $where = $this->select_query->getWhere ();
                    foreach ($new_where_conditions as $cond) {
                        $where->addCondition ($cond, LOGIC_TREE_AND);
                        
                        // update root if we just added new parents to the old where root
                        $where_root = $where->getRoot ();
                        if ($where_root->getParent () != null) {
                            while ($where_root->getParent () != null) {
                                $where_root = $where_root->getParent ();
                            }
                            $where->setRoot ($where_root);
                        }
                    }
                    
                //// Match Any ////
                } else {
                    // create new OR node
                    // shove all conditions under it
                    // if root exists, add an AND parent and shove the OR under it
                    // otherwise set the OR as root
                    $orNode = new LogicOperatorNode (LOGIC_TREE_OR);
                    foreach ($new_where_conditions as $cond) {
                        $orNode->addChild ($cond);
                    }
                    $where = $this->select_query->getWhere ();
                    $where_root = $where->getRoot ();
                    
                    if ($where_root == null) {
                        // Null root
                        $where->setRoot ($orNode);
                    } else if ($where_root instanceof LogicOperatorNode AND $where_root->getType () == LOGIC_TREE_AND) {
                        // OR under existing AND
                        $where_root->addChild ($orNode);
                    } else {
                        // OR under existing AND
                        $andNode = new LogicOperatorNode (LOGIC_TREE_AND);
                        $andNode->addChild ($where_root);
                        $andNode->addChild ($orNode);
                        $where->setRoot ($andNode);
                    }
                    
                }
            }
            
        }
        
    }
    
    
    /**
     * Displays the main table (allowing users to add or delete rows, or choose
     * a record to edit), including its search box if there is at least one
     * searchable column
     */
    public function draw () {
        echo $this->getHtml ();
    }
    
    /**
     * gets the HTML that will be used to draw the main table
     * 
     * @param int $page The number of the current page to view
     * @param int $records_per_page The maximum number of database records that
     *        will be displayed on the page. Further records will be viewed via
     *        a pagination interface.
     * @param bool $open_search If true, the search bar will be open straight
     *             away even if no search terms have been specified
     * 
     * @return string the HTML
     */
    public function getHtml ($page = null, $records_per_page = RECORDS_PER_PAGE, $open_search = false) {
        
        $return_string = '';
        
        $table_name = $this->select_query->getBaseTable ()->getName ();
        
        if ($this->inline_search) {
            $search_key = 'inline_search';
        } else {
            $search_key = 'search_params';
        }
        $sess = @$_SESSION[ADMIN_KEY][$search_key][$table_name];
        
        // Show filter options -- should this be its own function?
        if (count($this->search_cols) > 0) {
            
            ksort ($this->search_cols);
            
            list ($search_url, $search_sep) = $this->page_urls->get (MAIN_PAGE_SEARCH_ACTION);
            
            $return_string .= "<form method=\"post\" action=\"{$search_url}\">";
            
            $return_string .= "<input type=\"hidden\" name=\"_t\" value=\"{$table_name}\">";
            $return_string .= '<input type="hidden" name="_p" value="' .
                hsc(@$_GET['p']) . '">';
            
            if ($this->inline_search) {
                $return_string .= "<input type=\"hidden\" name=\"_search_type\" value=\"inline\">";
                $return_string .= "<input type=\"hidden\" name=\"_f\" value=\"{$_GET['f']}\">";
            }
            
            $return_string .= '<fieldset id="search_container">';
            
            $return_string .= "<legend class=\"clickable\" onclick=\"display_search (true);\"> ".
                "<img src=\"". ROOT_PATH_WEB. IMAGE_SEARCH_CLOSED. "\" alt=\"\"> Search </legend>";
            
            $fields = array ();
            $return_string .= "<div id=\"search\">\n";
            
            $return_string .= "<input type=\"radio\" name=\"_match_type\" value=\"all\" id=\"search_match_all\"";
            $match_type = @$sess['_match_type'];
            if ($match_type == 'all' or $match_type == '') {
                $return_string .= " checked";
            }
            $return_string .= "> <label for=\"search_match_all\">Match all</label>";
            $return_string .= "<input type=\"radio\" name=\"_match_type\" value=\"any\" id=\"search_match_any\"";
            if ($match_type == 'any') $return_string .= " checked";
            $return_string .= "> <label for=\"search_match_any\">Match any</label>";
            
            // add condition button
            $return_string .= " &nbsp; <input type=\"button\" value=\"{$this->text_blocks[MAIN_TEXT_ADD_CONDITION]}\"
                onclick=\"add_empty_search_condition ();\">\n";
            
            
            // show conditions
            foreach ($this->search_cols as $search_col) {
                
                list ($search_col, $eng_name) = $search_col;
                
                // check to see if column has link in Table object (from tables.xml)
                $link = null;
                /*
                if ($search_col->getAlias () != null) {
                    $column = $GLOBALS['table']->get ($search_col->getAlias ());
                    $link = $column->getLink ();
                } else {
                    $column = $GLOBALS['table']->get ($search_col->getName ());
                }
                */
                $column = $GLOBALS['table']->get($search_col->getName());
                
                /* LINKED COLUMN */
                if ($link != null) {
                    $field_defn = "'". $search_col->getAlias ()."' : ['". addslashes ($eng_name)."', TYPE_LINKED, ";
                    $field_defn .= ($column->isNullAllowed() ? 'true' : 'false') . ", '";
                    $field_defn .= $column->getTable()->getName() . "']";
                
                /* DATE COLUMN */
                } elseif ($search_col instanceof DateTimeQueryColumn) {
                    $field_defn = "'". $search_col->getName ()."' : ['". addslashes ($eng_name)."', TYPE_DATETIME, ";
                    $field_defn .= ($column->isNullAllowed() ? 'true' : 'false') . ', ';
                    
                    // time support
                    if ($search_col->isTimeColumn()) {
                        $field_defn .= 'true, ';
                    } else {
                        $field_defn .= 'false, ';
                    }
                    
                    // date support
                    if ($search_col->isDateColumn()) {
                        $field_defn .= 'true, ' . $search_col->getMinYear() . ', ' . $search_col->getMaxYear() . ']';
                    } else {
                        $field_defn .= 'false]';
                    }
                
                /* OTHER */
                } else if ($search_col instanceof Column) {
                    $field_defn = "'". $search_col->getName ()."' : ['". addslashes ($eng_name)."', ";
                    if ($search_col instanceof NumericColumn) {
                        $field_defn .= 'TYPE_NUMERIC';
                    } else if ($search_col instanceof BooleanColumn) {
                        $field_defn .= 'TYPE_BINARY';
                    } else {
                        $field_defn .= 'TYPE_TEXT';
                    }
                    
                    $field_defn .= ', ' . ($column->isNullAllowed() ? 'true' : 'false') . ']';
                }
                
                $fields[] = $field_defn;
            }
            
            $return_string .= "</div>\n"; // id=search
            
            $return_string .= "<div id=\"search_buttons\">\n";
            $return_string .= "<input type=\"hidden\" name=\"_action\" value=\"\">";
            $return_string .= "<input type=\"button\" value=\"{$this->text_blocks[MAIN_TEXT_CLEAR_CONDITIONS]}\" onclick=\"this.form._action.value='clear'; this.form.submit();\">\n";
            $return_string .= "<input type=\"submit\" value=\"{$this->text_blocks[MAIN_TEXT_APPLY_CONDITIONS]}\">\n";
            $return_string .= "</div>\n";
            
            $return_string .= "</fieldset>\n"; // id=search_container
            
            $return_string .= "</form>";
            
            $return_string .= "<script type=\"text/javascript\">\n";
            $return_string .= "var fields = {\n    ". implode (",\n    ", $fields). "\n};\n";
            
            
            // Set up a condition for each search parameter that is in the session
            $filters = &$_SESSION[ADMIN_KEY][$search_key][
                $this->select_query->getBaseTable ()->getName ()
            ];
            
            // for each filter, check it is valid in this context
            if ($filters == null) {
                $filters = array();
            } else {
                // pop off all invalid filters
                foreach ($filters as $index => $filter) {
                    if ($filter instanceof MainJoinFilter) {
                        $joins = $filter->getJoinList();
                        $table = $joins[0]->getToTable();
                        if (in_array($table, $this->filter_skip_tables)) {
                            unset($filters[$index]);
                        }
                    }
                }
                // if there's only the match type left, ditch it all
                if (count($filters) == 1) $filters = array();
            }
            
            $bits = explode (':', $fields[0]);
            $return_string .= "var default_field = " . $bits[0] . ";\n\n";
            
            
            // Open the search box if there is at least one filter,
            // or if the settings say that the search box should always be opened
            if (@count($filters) > 1 or $open_search) {
                $return_string .= "display_search (true);\n";
            } else {
                $return_string .= "display_search (false);\n";
            }
            
            // If there is at least one filter, load the filter(s)
            // otherwise just load a default filter
            if (@count($filters) > 1) {
                $return_string .= "var conditions = [\n";
                $filter_count = 0;
                foreach ($filters as $filter_key => $filter) {
                    if ($filter_key === '_match_type') continue;
                    if ($filter_count++ > 0) $return_string .= ",\n";
                    if (in_array ($filter->getType (), array (LOGIC_CONDITION_BETWEEN, LOGIC_CONDITION_NOT_BETWEEN))) {
                        $value = $filter->getValue ();
                        
                        $return_string .= "new Condition ('". $filter->getName (). "', ". $filter->getType ().
                            ", '". addslashes ($value[0]). "', '". addslashes ($value[1]). "')";
                    } else {
                        $value = $filter->getValue ();
                        
                        $return_string .= "new Condition ('". $filter->getName (). "', ". $filter->getType ().
                            ", '". addslashes ($value). "')";
                    }
                }
                $return_string .= "\n];\n";
            } else {
                $return_string .= "var conditions = [new Condition (default_field, COND_LIKE, '', '')];\n";
            }
            
            $return_string .= "add_search_conditions (conditions, false);\n";
            $return_string .= "</script>\n";
        }
        
        /*
        Show main table
        */
        
        if ($page === null) {
            $page = @$_GET['page'];
        }
        
        $records_per_page = (int) $records_per_page;
        if ($records_per_page > MAIN_VIEW_PER_PAGE_MAX) {
            $records_per_page = MAIN_VIEW_PER_PAGE_MAX;
        } else if ($records_per_page < MAIN_VIEW_PER_PAGE_MIN) {
            $records_per_page = MAIN_VIEW_PER_PAGE_MIN;
        }
        
        /*
        modify the query to just give a row count
        */
        $count_select_query = clone $this->select_query;
        $count_select_query->removeAllSelectFields ();
        $count_select_query->clearOrderBy ();
        // retain inner joins because they matter
        // actually, if there are any inner joins, perhaps we need to retain all joins
        $count_select_query->dropJoins (SQL_JOIN_TYPE_LEFT);
        $count_func = new QueryFunction ('COUNT', array (new QueryFieldLiteral ('*', false)));
        $count_func->setAlias ('count');
        $count_select_query->addSelectField ($count_func);
        
        $q = cast_to_string ($count_select_query);
        
        if (@$_SESSION['setup']['view_q'] === true) {
            echo "<p style=\"font-size: 9px;\">Q: {$q}</p>\n";
        }
        
        $res = execq($q);
        $row = fetch_assoc($res);
        $record_count = $row['count'];
        
        // set limit based on count and page
        $total_pages = ceil ($record_count / $records_per_page);
        $page = (int) $page;
        if ($page < 1) {
            $page = 1;
        } else if ($page > $total_pages) {
            $page = $total_pages;
        }
        $offset = ($page - 1) * $records_per_page;
        
        $this->select_query->setLimit ("{$records_per_page} OFFSET {$offset}");
        
        if (@$_SESSION['setup']['view_q'] === true or @$_GET['q'] == 'y') {
            echo "<p style=\"font-size: 9px;\">Q: ", cast_to_string ($this->select_query), "</p>\n";
        }
        
        $res = execq($this->select_query);
        
        $table_name = $this->select_query->getBaseTable ()->getName ();
        
        
        // determine if we should paginate
        $enable_pagination = false;
        if (@$res->rowCount() > 0) {
            $enable_pagination = true;
            if ($record_count <= $records_per_page and $records_per_page == RECORDS_PER_PAGE) {
                $enable_pagination = false;
            }
        }
        
        // showing records x to y of z
        if ($enable_pagination) {
            $start_page = $page - 7;
            if ($start_page < 1) $start_page = 1;
            $end_page = $start_page + 14;
            if ($end_page > $total_pages) $end_page = $total_pages;
            
            $block_end = ($start_page - 1) * $records_per_page;
            
            $viewing_records_end = $page * $records_per_page;
            $viewing_records_start = $viewing_records_end - $records_per_page + 1;
            if ($viewing_records_end > $record_count) $viewing_records_end = $record_count;
            
            // commas
            $viewing_records_start = number_format($viewing_records_start);
            $viewing_records_end = number_format($viewing_records_end);
            $record_count = number_format($record_count);
            
            // number per page
            $return_string .= '<form action="main_number_action.php" method="post" class="rs_pagesize">';
            
            $return_string .= "<span class=\"rs_viewing\">Viewing {$viewing_records_start} - {$viewing_records_end} of {$record_count}</span>";
            
            $size = strlen (MAIN_VIEW_PER_PAGE_MAX);
            $return_string .= "<input type=\"text\" size=\"{$size}\" maxlength=\"{$size}\" name=\"num\" value=\"{$records_per_page}\">";
            $return_string .= '<span class="rs_per_page">Records per page</span>';
            $return_string .= '<input type="submit" value="Change">';
            $return_string .= "<input type=\"hidden\" name=\"_t\" value=\"{$table_name}\">";
            $return_string .= "<input type=\"hidden\" name=\"_c\" value=\"".
                htmlspecialchars ($_SERVER['REQUEST_URI']). "\">";
            $return_string .= '</form>';
            
        } else {
            $start_page = 1;
        }
        
        // start of table if there are records and/or add button is enabled
        if (@$res->rowCount() > 0 or $this->options[MAIN_OPTION_ALLOW_ADD]) {
            list ($action_url, $junk) = $this->page_urls->get (MAIN_PAGE_ACTION);
            
            $form_id = 'main_form' . (++MainTable::$next_table_id);
            
            $return_string .= "<form id=\"{$form_id}\" action=\"{$action_url}\" name=\"rows_{$table_name}\"".
                " method=\"post\">\n";
            $return_string .= "<input type=\"hidden\" value=\"{$table_name}\" name=\"_t\"/>\n";
            
            if (@$_GET['p'] != '') {
                $return_string .= "<input type=\"hidden\" value=\"{$_GET['p']}\" name=\"_p\"/>\n";
            }
            if ($this->inline_search) {
                $return_string .= "<input type=\"hidden\" value=\"{$_GET['f']}\" name=\"_f\"/>\n";
            }
        }
        
        // pagination
        if ($enable_pagination) {
            $return_string .= "<table id=\"rs_nav\"><tr><td>";
            
            // determine base url for arrows
            if ($this->inline_search) {
                $base_url = "inline_search.php?t=". urlencode ($table_name). "&amp;p={$_GET['p']}&amp;f={$_GET['f']}";
            } else {
                list ($main_url, $main_sep) = $this->page_urls->get (MAIN_PAGE_MAIN);
                $base_url = "{$main_url}{$main_sep}t=" .
                    urlencode($table_name) . '&amp;p=' . urlencode(@$_GET['p']);
            }
            
            // first arrow
            $image = ROOT_PATH_WEB. IMAGE_ARROW_FIRST;
            if ($page == 1) {
                $image = ROOT_PATH_WEB. IMAGE_ARROW_FIRST_DISABLED;
            } else {
                $return_string .= "<a href=\"{$base_url}&amp;page=1\">";
            }
            $return_string .= "<img src=\"{$image}\" alt=\"|<\" title=\"Go to the first record\">";
            if ($page != $start_page) $return_string .= '</a>';
            
            $return_string .= '</td><td>';
            
            // previous arrow
            $image = ROOT_PATH_WEB. IMAGE_ARROW_PREVIOUS;
            if ($page == 1) {
                $image = ROOT_PATH_WEB. IMAGE_ARROW_PREVIOUS_DISABLED;
            } else {
                $prev = $page - 1;
                $return_string .= "<a href=\"{$base_url}&amp;page={$prev}\">";
            }
            $return_string .= "<img src=\"{$image}\" alt=\"<\" title=\"Go to the previous record\">";
            if ($page != $start_page) $return_string .= '</a>';
            
            // numbers
            $return_string .= '</td>';
            for ($i = $start_page; $i <= $end_page; $i++) {
                $block_start = $block_end + 1;
                $block_end += $records_per_page;
                if ($block_end > $record_count) $block_end = $record_count;
                
                $return_string .= "<td";
                if ($i == $page) $return_string .= ' class="current_page"';
                $return_string .= ">{$i}</td>";
                
            }
            
            $return_string .= '<td>';
            
            // next arrow
            $image = ROOT_PATH_WEB. IMAGE_ARROW_NEXT;
            if ($page == $total_pages) {
                $image = ROOT_PATH_WEB. IMAGE_ARROW_NEXT_DISABLED;
            } else {
                $next = $page + 1;
                $return_string .= "<a href=\"{$base_url}&amp;page={$next}\">";
            }
            $return_string .= "<img src=\"{$image}\" alt=\">\" title=\"Go to the next record\">";
            if ($page != $total_pages) $return_string .= '</a>';
            
            $return_string .= '</td><td>';
            
            // last arrow
            $image = ROOT_PATH_WEB. IMAGE_ARROW_LAST;
            if ($page == $total_pages) {
                $image = ROOT_PATH_WEB. IMAGE_ARROW_LAST_DISABLED;
            } else {
                $return_string .= "<a href=\"{$base_url}&amp;page={$total_pages}\">";
            }
            $return_string .= "<img src=\"{$image}\" alt=\">|\" title=\"Go to the last record\">";
            if ($page != $total_pages) $return_string .= '</a>';
            
            $return_string .= "</td></tr></table>";
        }
        
        // add button
        if ($this->options[MAIN_OPTION_ALLOW_ADD]) {
            list ($add_url, $add_sep) = $this->page_urls->get (MAIN_PAGE_ADD);
            
            $button_label = $this->text_blocks[MAIN_TEXT_ADD_BUTTON];
            $return_string .= "<p><input type=\"button\" value=\"{$button_label}\" name=\"add\" ";
            
            $return_string .= "onclick=\"window.location = '{$add_url}{$add_sep}t=". urlencode ($table_name);
            
            if (@$_GET['p'] != '') {
                $return_string .= "&p={$_GET['p']}";
            }
            $return_string .= "';\"/></p>\n";
        }
        
        
        // data
        if (@$res->rowCount() > 0) {
            $return_string .= "<table class=\"form-table\">\n    <tr class=\"header-row\">\n";
            if ($this->options[MAIN_OPTION_ALLOW_DEL]) {
                $return_string .= "        <td>&nbsp;</td>\n";
            } else {
                $return_string .= "        <td style=\"display: none;\">&nbsp;</td>\n";
            }
            foreach ($this->visible_columns as $col) {
                $return_string .= "        ". $col->getTH (). "\n";
            }
            $return_string .= "    </tr>\n";
            
            $prev_row = new MainRow (null, null, null);
            $row = new MainRow (@fetch_assoc($res), $this->primary_key_columns, $this->select_query->getOrderBy ());
            $next_row = new MainRow (@fetch_assoc($res), $this->primary_key_columns, $this->select_query->getOrderBy ());
            $odd_row = 1;
            
            while ($row->getPrimaryKey () !== null) {
                
                //echo "Row has order identifier: ", $row->getOrderIdentifier (), "<br/>\n";
                
                $return_string .= "    <tr class=\"";
                if ($odd_row == 1) {
                    $return_string .= 'altrow1';
                } else {
                    $return_string .= 'altrow2';
                }
                //    onmouseout=\"remove_highlight (this);\" onmouseover=\"highlight (this);\"
                $return_string .= "\">\n";
                if ($this->options[MAIN_OPTION_ALLOW_DEL]) {
                    $return_string .= '        <td class="checkbox"><input type="checkbox" value="1" name="del['.
                        $row->getPrimaryKey (). "]\"/></td>\n";
                } else {
                    $return_string .= '        <td style="display: none;"><input type="hidden" value="0" name="del['.$row->getPrimaryKey (). "]\"/></td>\n";
                }
                foreach ($this->visible_columns as $col) {
                    $data = $row->get($col->identify('row'));
                    $return_string .= "        " .
                        $col->getTD($data, $row->getPrimaryKey()) . "\n";
                }
                $return_string .= "    </tr>\n";
                
                $prev_row = $row;
                $row = $next_row;
                $next_row = new MainRow (@fetch_assoc($res), $this->primary_key_columns, $this->select_query->getOrderBy ());
                $odd_row = $odd_row ^ 1;
            }
            
            $return_string .= "</table>\n";
            
            $return_string .= "<div id=\"rowoptions\">\n";
            
            if ($this->options[MAIN_OPTION_ALLOW_DEL] or $this->options[MAIN_OPTION_CSV]) {
                $return_string .= "<p>";
                
                if ($this->options[MAIN_OPTION_ALLOW_DEL]) {
                    
                    $return_string .= "<input type=\"button\" id=\"select_all_button\" onclick=\"main_select_all ('{$form_id}');\" value=\"Select/de-select all\"> ";
                    
                    if ($this->options[MAIN_OPTION_CONFIRM_DEL]) {
                        $return_string .= "<input type=\"hidden\" name=\"rem\" value=\"\">\n";
                        $return_string .= "<input type=\"button\" value=\"{$this->text_blocks[MAIN_TEXT_DEL_BUTTON]}\" ".
                            "onclick=\"confirmDelete('{$table_name}','".
                                addslashes ($this->text_blocks[MAIN_TEXT_DEL_POPUP]). "');\">\n";
                    } else {
                        $return_string .= "<input type=\"submit\" value=\"Delete selected\" name=\"rem\"/> ";
                    }
                    
                }
                
                if ($this->options[MAIN_OPTION_CSV]) {
                    $return_string .= "<input type=\"button\" value=\"{$this->text_blocks[MAIN_TEXT_CSV_BUTTON]}\" " .
                        "onclick=\"window.location = 'main_export.php?t=" .
                        hsc($table_name) . '&amp;p=' . hsc(@$_GET['p']) .
                        "';\">\n";
                }
                
                $return_string .= "</p>\n";
            }
            
            $return_string .= "</div>\n";
            
            list ($main_url, $main_sep) = $this->page_urls->get (MAIN_PAGE_MAIN);
            list ($edit_url, $edit_sep) = $this->page_urls->get (MAIN_PAGE_EDIT);
            
            $return_string .= "<script type=\"text/javascript\">" .
                "activate_main_form('{$main_url}{$main_sep}t=" .
                urlencode($table_name) .
                '&p=' . @$_GET['p'] . "', '{$edit_url}{$edit_sep}t=" .
                urlencode($table_name) .
                '&p=' . @$_GET['p'] . "', {$start_page}, '{$form_id}');" .
                "</script>\n";
            
        } else {
            // there were no rows
            if ($this->num_pre_filters > 0) {
                $return_string .= "<p>{$this->text_blocks[MAIN_TEXT_NOT_FOUND]}</p>\n";
            } else {
                $return_string .= "<p>{$this->text_blocks[MAIN_TEXT_NO_RECORDS]}</p>\n";
            }
        }
        
        if (@$res->rowCount() > 0 or $this->options[MAIN_OPTION_ALLOW_ADD]) {
            $return_string .= "</form>\n";
        }
        
        return $return_string;
        
    }
    
    /**
     * Exports this table to a common format (i.e. CSV)
     *
     * @param int $export_type EXPORT_TYPE_CSV or EXPORT_TYPE_TSV
     * @return string The exported data in the specified format
     */
    public function export ($export_type = 0) {
        
        // Set up the export
        switch ($export_type) {
            case EXPORT_TYPE_CSV:
                $field_separator = ',';
                $record_separator = "\n";
                $quote_fields = true;
                $headings = true;
                break;
                
            case EXPORT_TYPE_TSV:
                $field_separator = "\t";
                $record_separator = "\n";
                $quote_fields = false;
                $headings = true;
                break;
                
            default:
                throw new exception ("Invalid export type specified.");
        }
        
        
        // get our result set
        $q = cast_to_string ($this->select_query);
        $res = execq($q);
        
        
        // export our data
        if (@$res->rowCount() > 0) {
            
            // headings
            if ($headings) {
                $i = 0;
                foreach ($this->visible_columns as $col) {
                    if ($i++ > 0) $return_string .= $field_separator;
                    $value = strip_tags($col->getTH ());
                    
                    // quote if we should
                    if ($quote_fields) {
                        $find = array ("\n", "\r\n", "\r");
                        $value = str_replace($find, "\n", $value);
                        $return_string .= '"' . $value . '"';
                        
                    } else {
                        $return_string .= $value;
                    }
                }
                $return_string .= $record_separator;
            }
            
            // rows or something
            $prev_row = new MainRow (null, null, null);
            $row = new MainRow (@fetch_assoc($res), $this->primary_key_columns, $this->select_query->getOrderBy ());
            $next_row = new MainRow (@fetch_assoc($res), $this->primary_key_columns, $this->select_query->getOrderBy ());
            $odd_row = 1;
            
            // data itself
            while ($row->getPrimaryKey () != null) {

                $i = 0;
                foreach ($this->visible_columns as $col) {
                    if ($i++ > 0) $return_string .= $field_separator;
                    
                    $data = $row->get($col->identify('row'));
                    
                    if ($main_col instanceof MainOrderColumn) {
                        $value = $data;
                    } else {
                        $value = strip_tags($col->getTD($data, $row->getPrimaryKey()));
                        $value = str_replace ('&nbsp;', '', $value);
                    }

                    // quote if we should
                    if ($quote_fields) {
                        $find = array ("\n", "\r\n", "\r");
                        $value = str_replace ($find, "\n", $value);
                        $return_string .= '"' . $value . '"';
                    } else {
                        $return_string .= $value;
                    }
                }
                
                $return_string .= $record_separator;
                $prev_row = $row;
                $row = $next_row;
                $next_row = new MainRow (@fetch_assoc($res), $this->primary_key_columns, $this->select_query->getOrderBy ());
            }
                 
        }

        return $return_string;
    }
    
    /**
     * Gets the query handler used to build the main table interface
     * 
     * @return SelectQuery
     */
    public function getSelectQuery () {
        return $this->select_query;
    }
    
    /**
     * Gets the list of columns that will be displayed on the main interface
     * 
     * @return array of {@link QueryField}s used to build the query
     */
    public function getVisibleColumns () {
        return $this->visible_columns;
    }
    
    /**
     * sets the options for the main interface
     * 
     * @param int $option the option (from constant definition), e.g.
     *        MAIN_OPTION_ALLOW_ADD
     * @param bool $value whether the option should be set or not
     */
    public function setOption ($option, $value) {
        
        $int_option = (int) $option;
        
        switch ($int_option) {
            case MAIN_OPTION_ALLOW_ADD:
            case MAIN_OPTION_ALLOW_DEL:
            case MAIN_OPTION_CONFIRM_DEL:
            case MAIN_OPTION_CSV:
                $this->options[$option] = (bool) $value;
                break;
            
            default:
                throw new Exception ("Unknown option {$option}");
        }
    }
    
    /**
     * Sets the alternate text to be used on the buttons and pop-ups
     * 
     * @param int $option the button/popup (from constant definition), e.g.
     *        MAIN_TEXT_DEL_POPUP
     * @param string $value the text to show on the button or pop-up
     */
    public function setText ($option, $value) {
        
        $int_option = (int) $option;
        
        switch ($int_option) {
            case MAIN_TEXT_ADD_BUTTON:
            case MAIN_TEXT_DEL_BUTTON:
            case MAIN_TEXT_DEL_POPUP:
            case MAIN_TEXT_CSV_BUTTON:
            case MAIN_TEXT_NO_RECORDS:
            case MAIN_TEXT_NOT_FOUND:
                $this->text_blocks[$option] = (bool) $value;
                break;
            
            default:
                throw new Exception ("Unknown text field {$option}");
        }
    }
    
    /**
     * Clears the search columns for this MainTable
     * Effectivly disabling search
     */
    public function clearSearchCols () {
        $this->search_cols = array();
    }
    
    /**
     * redefines which page URLs will be used to manipulate the data
     * 
     * @author Benno, 2007-12-06
     * @param mixed $pages a constant or array of constants representing the
     *        page action(s), e.g. MAIN_PAGE_EDIT
     * @param mixed $urls a string or array of strings representing the URL(s),
     *        e.g. main_edit.php
     */
    function setPageUrls ($pages, $urls) {
        
        if (!is_array ($pages)) {
            $pages = array ($pages);
        }
        if (!is_array ($urls)) {
            $urls = array ($urls);
        }
        
        if (@count($pages) != @count($urls)) {
            throw new Exception ('Parameters $pages and $urls have different numbers of elements');
        }
        
        reset($pages);
        reset($urls);
        while (list($page_id, $page) = each($pages)) {
            list($url_id, $url) = each($urls);
            $this->page_urls->set($page, $url);
        }
        
    }
    
    /**
     * Defines whether the main interface is for an inline search.
     * If so, the search and pagination forms will redirect back to
     * inline_search.php instead of main.php
     * 
     * @author Benno, 2007-12-06
     * @param bool $val
     */
    function setInlineSearch ($val) {
        $this->inline_search = (bool) $val;
    }
    
}

?>
