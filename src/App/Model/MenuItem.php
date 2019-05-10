<?php

namespace App\Model;

class MenuItem
{
    private $text;
    private $url = '#';
    private $active = false;
    private $activeTrail = false;
    private $submenu = array();
    private $parent = array();

    public function __construct($text = '#', $url = '#', $active = false)
    {
        $this->text = $text;
        $this->url = $url;
        $this->active = $active;

        $submenu = array();
    }

    public function Add($submenu)
    {
        if (is_array($submenu)) {
            foreach ($submenu as $menu) {
                $menu->SetParent($this);
            }
        } else {
            $submenu.SetParent($this);
        }

        array_push($this->submenu, $submenu);
    }

    public function SetParent($parent)
    {
        $this->parent = $parent;
    }

    public function SetActive($activeTrail = false)
    {
        $this->active = true;
        $this->activeTrail = $activeTrail;
        $this->parent->SetActive(true);
    }

    public function GetMenu()
    {
        return [
            'text' => $this->text,
            'url' => $this->url,
            'active' => $this->active,
            'active-trail' => $this->activeTrail,
            'submenu' => $this->submenu,
        ];
    }
}
