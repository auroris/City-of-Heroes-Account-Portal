<?php

namespace App\Model;

use App\Util\SqlServer;
use App\Util\Exec;
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
        $this->sql = SqlServer::getInstance();
        $this->persistent = $persistent;
        $cmd = getenv('dbquery').' -getcharacter '.escapeshellarg($name);

        if ('' != $name) {
            if ($this->sql->ReturnsRows('SELECT ContainerId FROM cohdb.dbo.ents WHERE Name = ?', array($name))) {
                $this->results = explode("\n", Exec::Exec($cmd, 5));
                $this->ParseResults();
                $this->BlacklistEntries();
            } else {
                throw new Exception('No such character "'.$name.'"');
            }
        }
    }

    public function SetArray(array $res = [])
    {
        $this->results = $res;
        $this->ParseResults();
    }

    // Some fields should just not be included in exports
    private function BlacklistEntries()
    {
        unset($this->attributes['Ents2'][0]['AuthUserDataEx']);
        unset($this->attributes['Ents2'][0]['AccSvrLock']);
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

            $result = trim($result);

            $val = explode(' ', $result, 2);
            if (false === $val || 2 != count($val)) {
                continue;
            }

            // explode at first space, and assign to vars
            list($key, $value) = $val;

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

    public function PutCharacter()
    {
        $this->Reconstruct();
        $charfile = implode("\n", $this->constructed)."\n";

        $output = '';
        $file = tmpfile();
        $path = stream_get_meta_data($file)['uri'];
        fwrite($file, $charfile);

        $cmd = getenv('dbquery').' -putcharacter < '.$path.' 2>&1';

        Exec::Exec($cmd, 10);

        fclose($file);
    }

    public function GetAlignment()
    {
        $PlayerType = 0;
        $PlayerSubType = 0;
        $PlayerPraetorian = 0;

        if (isset($this->attributes['PlayerType'])) {
            $PlayerType = $this->attributes['PlayerType'];
        }
        if (isset($this->attributes['PlayerSubType'])) {
            $PlayerSubType = $this->attributes['PlayerSubType'];
        }
        if (isset($this->attributes['Ents2'][0]['PraetorianProgress'])) {
            $playerPraetorian = $this->attributes['Ents2'][0]['PraetorianProgress'];
        }

        if ($PlayerPraetorian < 3 && $PlayerPraetorian > 0 && 0 == $PlayerType) {
            return 'Resistance';
        }
        if ($PlayerPraetorian < 3 && $PlayerPraetorian > 0 && 1 == $PlayerType) {
            return 'Loyalist';
        }
        if (0 == $PlayerType && 0 == $PlayerSubType) {
            return 'Hero';
        }
        if (0 == $PlayerType && 1 == $PlayerSubType) {
            return 'Paragon';
        }
        if (0 == $PlayerType && 2 == $PlayerSubType) {
            return 'Vigilante';
        }
        if (1 == $PlayerType && 0 == $PlayerSubType) {
            return 'Villain';
        }
        if (1 == $PlayerType && 1 == $PlayerSubType) {
            return 'Tyrant';
        }
        if (1 == $PlayerType && 2 == $PlayerSubType) {
            return 'Rogue';
        }

        return 'Unkonwn';
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

    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    public function __get($name)
    {
        return $this->attributes[$name];
    }

    public function __isset($name)
    {
        return isset($this->attributes[$name]);
    }

    public function __unset($name)
    {
        unset($this->attributes[$name]);
    }
}
