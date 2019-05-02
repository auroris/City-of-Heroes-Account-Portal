<?php
namespace App\CoH;

use \Exception;

class Federation {
    private $name;
    private $url;

    function __construct($host = "") {
        foreach ($GLOBALS["federation"] as $item) {
            if (stripos($item["Url"], $host) !== false) {
                $this->name = $item["Name"];
                $this->url = $item["Url"];
                return;
            }
        }
        throw new Exception("The server " . $host . " is not part of this server's federation list. Characters cannot be transfered until a proper federating relationship has been established.");
    }

    function getName() {
        return $this->name;
    }

    function getUrl() {
        return $this->url;
    }
}