<?php
/**
 * This file is part of Tricho and is copyright (C) Transmogrifier E-Solutions.
 * It is released under the GNU General Public License, version 3 or later.
 * See COPYRIGHT.txt and LICENCE.txt in the tricho directory for more details.
 */

namespace Tricho\Query;

use Tricho\Meta\Column;
use Tricho\Meta\LinkColumn;
use Tricho\Meta\TemporalColumn;


class IdentifierQuery extends SelectQuery
{
    protected $ident;

    function __construct (QueryTable $table)
    {
        parent::__construct($table);

        $this->ident = new QueryFunction('CONCAT', []);
        $this->ident->setAlias('Value');
        $this->addTable($table);
        $this->addSelectField($this->ident);
    }


    function addTable(QueryTable $table)
    {
        $identifier = $table->getRowIdentifier();
        $ident_func = $this->ident;

        foreach ($identifier as $element) {
            if ($element instanceof LinkColumn) {
                $join = $this->autoJoin($element);
                $target_table = $join->getTable();
                $this->addTable($target_table);
            } else if ($element instanceof Column) {
                $is_date = false;
                if ($element instanceof TemporalColumn) {
                    $is_date = true;
                }

                if ($table instanceof AliasedTable) {
                    $element = new AliasedColumn($table, $element);
                }

                if ($is_date) {
                    $element = new DateFormatFunction($element);
                }

                $element = new QueryFunction('IFNULL', [$element, '???']);

                $ident_func->addParam($element);
            } else {
                // $element is a string divider, e.g. ' ' or ', '
                $ident_func->addParam(new QueryFieldLiteral($element));
            }
        }
    }
}
