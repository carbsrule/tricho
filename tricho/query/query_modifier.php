<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * Used for making modifications to an existing SelectQuery
 */
interface QueryModifier {
    
    /**
     * Make the modifications. You can add WHERE clauses, JOINs, etc. to a
     * pre-built base query.
     * @param SelectQuery $query The query that should be modified.
     */
    function modify(SelectQuery $query);
    
}
