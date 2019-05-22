<?php

namespace App\Model;

use Exception;

class Vars
{
    public $ouroboros = array();

    public function __construct($dataset)
    {
        if (false != realpath('./../data/'.$dataset)) {
            $data = file_get_contents('./../data/'.$dataset.'/vars.attribute');
            if (false === $data) {
                throw new Exception('Unable to read data/'.$dataset.'/vars.attribute');
            }

            preg_match_all(
                '/"([^"]+)"/',
                file_get_contents('./../data/'.$dataset.'/vars.attribute'),
                $this->ouroboros,
                PREG_PATTERN_ORDER
            );

            $this->ouroboros = array_map('strtolower', $this->ouroboros[1]);
        }
    }

    public function Exists($needle)
    {
        return in_array($needle, $this->ouroboros);
    }
}
