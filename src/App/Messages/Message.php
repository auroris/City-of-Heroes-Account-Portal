<?php

namespace App\Messages;

use App\Util\DataHandling;

class Message implements \JSONSerializable
{
    public $to = '';
    public $from = '';
    private $data = array();
    private $encryptedData = '';

    public function __construct($to = '', $from = '')
    {
        $this->to = $to;

        if ('' == $from) {
            $this->from = getenv('portal_name');
        } else {
            $this->from = $from;
        }
    }

    public function __sleep()
    {
        $fedServer = $this->FindFederationServerByName($this->to);
        $this->encryptedData = DataHandling::Encrypt(json_encode($this->data), $fedServer['Crypto']['key'], $fedServer['Crypto']['iv']);

        return array('to', 'from', 'encryptedData');
    }

    public function __unsleep()
    {
        $fedServer = $this->FindFederationServerByName($this->to);
        $this->data = json_decode(DataHandling::Decrypt($this->encryptedData, $fedServer['Crypto']['key'], $fedServer['Crypto']['iv']), true);
    }

    public function jsonSerialize()
    {
        $this->__sleep();

        return ['To' => $this->to, 'From' => $this->from, 'Message' => $this->encryptedData];
    }

    public function Unserialize($json)
    {
        $data = json_decode($json, true);
        $this->to = $data['To'];
        $this->from = $data['From'];
        $this->encryptedData = $data['Message'];
        $this->__unsleep();
    }

    // Find a federation server by its name.
    private function FindFederationServerByName($name)
    {
        foreach ($GLOBALS['federation'] as $item) {
            if (false !== stripos($item['Name'], $name)) {
                return $item;
            }
        }
    }

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        return $this->data[$name];
    }

    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    public function __unset($name)
    {
        unset($this->data[$name]);
    }
}
