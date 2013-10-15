<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

/**
 * @package query_builder
 */

// NOTE: the column list can either be auto-managed (in which case all
// duplicated columns will have aliases) or manually-managed, which is probably
// better because visible columns are the only ones that need aliases.
// In other cases, they can simply be referenced using the table identifier
// (name or alias) as a qualifier. So, the aliases will be managed by MainTable
// or the calling code
/**
 * provides a basic set of functions for {@link AliasedColumn},
 * {@link QueryFieldLiteral}, {@link QueryFunction}, etc.
 * 
 * @package query_builder
 */
abstract class AliasedField implements QueryField {
    
    protected $name;
    protected $alias;
    
    function __toString () {
        if ($this->alias == '') {
            return (string) $this->name;
        } else {
            return (string) $this->alias;
        }
    }
    
    /**
     * gets the name of this field
     * 
     * @return string
     */
    function getName () {
        return $this->name;
    }
    
    /**
     * sets the name of this field
     * 
     * @param string $name
     */
    function setName ($name) {
        $this->name = $name;
    }
    
    /**
     * gets the alias used for this field
     * 
     * @return string
     */
    function getAlias () {
        return $this->alias;
    }
    
    /**
     * sets the alias used for this field
     * 
     * @param string $alias
     */
    function setAlias ($alias) {
        $this->alias = $alias;
    }
    
    /**
     * Identifies this column in a given context. As this is an abstract class,
     * it has no context, so this method simply calls __toString (), although
     * its behaviour is overridden in sub-classes.
     */
    function identify ($context) {
        return cast_to_string ($this);
    }
    
}

?>
