<?php

namespace App\Model;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Components\Condition;
use PhpMyAdmin\SqlParser\Components\OrderKeyword;
use PhpMyAdmin\SqlParser\Components\Limit;
use App\Util\SqlServer;

class DataTable
{
    protected $sql;
    protected $params = [];
    protected $parser;

    public function __construct($query = '', $params = [])
    {
        $this->sql = SqlServer::getInstance();

        $this->parser = new Parser($query);
        $this->params = $params;
    }

    public function GetColumns()
    {
        return $this->sql->FetchNumeric(
            'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ?',
            array($this->parser->statements[0]->from[0]->table)
        );
    }

    public function GetJSON()
    {
        $params = $this->params;
        $statement = $this->parser->statements[0];

        // Total number of returned records before any filtering
        $recordsT = $this->sql->FetchNumeric('select count(*) as num from ('.$statement->build().') as tb', $this->params);
        $recordsTotal = $recordsT[0][0];
        $recordsFiltered = $recordsT[0][0];

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

        $result = $statement->build();

        // SQL Server 2012's version of the limit clause
        if (isset($_GET['start']) && is_numeric($_GET['start']) && is_numeric($_GET['length'])) {
            $result .= ' OFFSET '.$_GET['start'].' ROWS FETCH NEXT '.$_GET['length'].' ROWS ONLY';
        }

        $records = [];
        $records = $this->sql->FetchNumeric($result, $params);

        // Tell DataTable that we filtered some records and how many are returned
        if (isset($_GET['search']) && strlen($_GET['search']['value']) > 0) {
            $recordsFiltered = count($records);
        }

        return json_encode(array(
            'draw' => isset($request['draw']) ?
                intval($request['draw']) :
                0,
            'recordsTotal' => intval($recordsTotal),
            'recordsFiltered' => intval($recordsFiltered),
            'data' => $records,
        ));
    }
}
