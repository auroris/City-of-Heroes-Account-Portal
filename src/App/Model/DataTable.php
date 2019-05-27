<?php

namespace App\Model;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Components\Condition;
use PhpMyAdmin\SqlParser\Components\OrderKeyword;
use PhpMyAdmin\SqlParser\Components\Limit;

class DataTable
{
    protected $sql;

    public function __construct()
    {
        $this->sql = \App\Util\SqlServer::getInstance();
    }

    public function Get($sql, $params = [])
    {
        $parser = new Parser($sql);

        // Total number of returned records before any filtering
        $recordsT = $this->sql->FetchNumeric('select count(*) as num from ('.$sql.') as tb');
        $recordsTotal = $recordsT[0][0];
        $recordsFiltered = $recordsT[0][0];
        $statement = $parser->statements[0];

        // Create where clause
        if (isset($_GET['search']) && strlen($_GET['search']['value']) > 0) {
            $whereClause = '';
            $i = 0;

            foreach ($parser->statements[0]->expr as $column_description) {
                if (isset($column_description->column)) {
                    if ($i > 0) {
                        $whereClause .= ' OR';
                    }

                    $whereClause .= ' '.$column_description->column.' LIKE ?';
                    array_push($params, '%'.$_GET['search']['value'].'%');
                    ++$i;
                }
            }

            $statement->where = new Condition($whereClause);
        }

        // Order by
        if (isset($_GET['order']) && is_numeric($_GET['order'][0]['column'])) {
            $statement->order = new OrderKeyword(($_GET['order'][0]['column'] + 1), 'asc' == $_GET['order'][0]['dir'] ? 'ASC' : 'DESC');
        }

        $sql = $statement->build();

        // SQL Server 2012's version of the limit clause
        if (isset($_GET['start']) && is_numeric($_GET['start']) && is_numeric($_GET['length'])) {
            $sql .= ' OFFSET '.$_GET['start'].' ROWS FETCH NEXT '.$_GET['length'].' ROWS ONLY';
        }

        $records = [];
        $records = $this->sql->FetchNumeric($sql, $params);

        // Tell DataTable that we filtered some records and how many are returned
        if (isset($_GET['search']) && strlen($_GET['search']['value']) > 0) {
            $recordsFiltered = count($records);
        }

        return array(
            'draw' => isset($request['draw']) ?
                intval($request['draw']) :
                0,
            'recordsTotal' => intval($recordsTotal),
            'recordsFiltered' => intval($recordsFiltered),
            'data' => $records,
        );
    }
}
