<?php

namespace App\Controller;

use App\Model\CoHStats;

class MenuController
{
    private $menu = array();

    public function __construct()
    {
        array_push($this->menu, new \App\Model\MenuItem('Home', getenv('portal_url')));

        if (!isset($_SESSION['account'])) {
            array_push($this->menu, new \App\Model\MenuItem('Create Account', getenv('portal_url').'create'));
        }

        array_push($this->menu, new \App\Model\MenuItem('My Account', getenv('portal_url').'manage'));

        if (isset($_SESSION['account'])) {
            array_push($this->menu, new \App\Model\MenuItem('Logout', getenv('portal_url').'logout'));
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

        // In addition to exporting the menu, I also export some common parameters to the template
        return ['portal_name' => getenv('portal_name'),
                'portal_url' => getenv('portal_url'),
                'menu_tree' => $result,
                'online' => $gameStats->GetOnline(), ];
    }
}
