<?php

namespace App\Controller;

class MenuController
{
    private $menu = array();

    public function __construct()
    {
        array_push($this->menu, new \App\Model\MenuItem('Home', './'));
        array_push($this->menu, new \App\Model\MenuItem('Create Account', 'create'));
        array_push($this->menu, new \App\Model\MenuItem('My Account', 'manage'));
        array_push($this->menu, new \App\Model\MenuItem('Logout', 'logout'));
    }

    public function GetMenu()
    {
        $result = array();

        foreach ($this->menu as $item) {
            array_push($result, $item->GetMenu());
        }

        return $result;
    }
}
