<?php

namespace App\Controller;
use App\Model\CoHStats;

class MenuController
{
    private $menu = array();

    public function __construct()
    {
        array_push($this->menu, new \App\Model\MenuItem('Home', './'));
        
        if (!isset($_SESSION['account']))
        {
            array_push($this->menu, new \App\Model\MenuItem('Create Account', 'create'));
        }

        array_push($this->menu, new \App\Model\MenuItem('My Account', 'manage'));
        
        if (isset($_SESSION['account']))
        {
            array_push($this->menu, new \App\Model\MenuItem('Logout', 'logout'));
        }
    }

    // Called by /App/TwigExtension/MenuExtension, fyi
    public function GetMenu()
    {
        $gameStats = new CoHStats();

        $result = array();

        foreach ($this->menu as $item) {
            array_push($result, $item->GetMenu());
        }

        return ['menu_tree' => $result,
                'online' => $gameStats->GetOnline()];
    }
}
