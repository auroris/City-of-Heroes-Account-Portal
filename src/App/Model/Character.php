<?php

namespace App\Model;

use App\Util\SqlServer;
use Exception;

class Character
{
    protected $results = [];
    protected $constructed = [];
    protected $sql;
    protected $persistent = false;
    public $attributes = [];

    public function __construct($name = '', $persistent = false)
    {
        $this->persistent = $persistent;
        $this->sql = SqlServer::getInstance();

        if ('' != $name) {
            if ($this->sql->ReturnsRows('SELECT ContainerId FROM cohdb.dbo.ents WHERE name = ?', array($name))) {
                $results = array();
                $ret = 0;
                exec($GLOBALS['dbquery'].' -getcharacter '.escapeshellarg($name), $this->results, $ret);

                if (0 != $ret) {
                    throw new Exception('Calling '.$GLOBALS['dbquery'].' failed with a return code of '.$ret);
                }

                $this->ParseResults();
            } else {
                throw new Exception('No such character "'.$name.'"');
            }
        }
    }

    private function ParseResults()
    {
        // Let's loop through every result and determine if it's a single key=value or array of sorts.
        foreach ($this->results as $result) {
            // Some values have "//" and also have "// " at the start - Which is very odd might be best to see if we can fix it at source or not.
            // We need to remove these values to allow the below to extract correctly.
            // @TODO: Check this doesn't impact the import later? Are the // Needed at all? do these rows even have to be extracted?
            if (false !== strpos($result, '//')) {
                if (true === $this->persistent) {
                    $result = str_replace(['// ', '//'], '', $result);
                } else {
                    continue;
                }
            }

            // explode at first space, and assign to vars
            list($key, $value) = explode(' ', $result, 2);

            // This might have an underlining issue, We need to double check it doesn't cause an issue. The exec returns Strings wrapped in ""
            $value = str_replace('"', '', $value);

            // Let's do a check to see if we can strpos a [
            if (strpos($key, '[')) {
                list($table_row, $field) = explode('.', $key);
                list($table, $row) = explode('[', $table_row);
                $row = (int) $row; // force the row to be Int.

                // Reconstruct our extracted values into a PHP Array.
                $this->attributes[$table][$row][$field] = $value;
            } else {
                // Simple key=value pairing.
                $this->attributes[$key] = $value;
            }
        }
    }

    private function Reconstruct()
    {
        // Let's take the ->attributes and generate the results again, This could possibly be built much cleaner/nicer.
        // Reconstruction will get slightly messy again, but we know we only have to go 3 deep at most.
        foreach ($this->attributes as $table => $attr) {
            if (!is_array($attr)) {
                // If we aren't a numeric value, force the quotes "" back on to the value.
                if (!is_numeric($attr)) {
                    $attr = '"'.$attr.'"';
                }
                $this->constructed[] = implode(' ', [$table, $attr]);
            } else {
                foreach ($attr as $row => $fields) {
                    foreach ($fields as $field => $value) {
                        // If we aren't a numeric value, force the quotes "" back on to the value.
                        if (!is_numeric($value)) {
                            $value = '"'.$value.'"';
                        }
                        $key_string = "{$table}[{$row}].{$field}";
                        $this->constructed[] = implode(' ', [$key_string, $value]);
                    }
                }
            }
        }
    }

    public function ToJSON()
    {
        return json_encode($this->attributes, JSON_THROW_ON_ERROR);
    }

    public function ParseJSON($jsonString)
    {
        $this->attributes = json_decode($jsonString, JSON_THROW_ON_ERROR);
    }

    public function ToArray()
    {
        $this->Reconstruct();

        return $this->constructed;
    }
}
