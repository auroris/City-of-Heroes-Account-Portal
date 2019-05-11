<?php

namespace App\TwigExtension;

class MenuExtension extends \Twig_Extension implements \Twig_Extension_GlobalsInterface
{
    public function __construct()
    {
    }

    public function getGlobals()
    {
        $menu = new \App\Controller\MenuController();

        return $menu->GetMenu();
    }

    public function getName()
    {
        return 'App\\TwigExtension\\MenuExtension';
    }
}
