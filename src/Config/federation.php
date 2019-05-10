<?php

// This is an example file only.

$GLOBALS['federation'] = [
    [
        'Name' => 'Aurora Server',
        'Url' => 'http://localhost:8080/Github-City-of-Heroes-Account-Portal/public',
        'Policy' => [ // Policy for characters coming from 'Aurora Server'
            'ForceInfluence' => 0, // 0 (or any number) to force inf to that number; false to disable and allow whatever the character has
            'ForceAccessLevel' => 0, // 0 (or any number) to force access level to that number; false to disable and allow whatever the character has
            'AllowInventory' => false, // false to delete the inventory, true to allow whatever the character has
        ],
        'Crypto' => [ // Configure the same crypto keys with Aurora Server
            'key' => 'AuroraKey',
            'iv' => 'AuroraVector',
        ],
    ],
    [
        'Name' => 'City Of Heroes Rebirth',
        'Url' => 'https://play.cityofheroesrebirth.com/portal/public',
        'Policy' => [ // Policy for characters coming from CoH Rebirth
            'ForceInfluence' => false, // Allow inf to carry over
            'ForceAccessLevel' => 0, // 0 (or any number) to force access level to that number; false to disable and allow whatever the character has
            'AllowInventory' => true, // Allow inventory to carry over
        ],
        'Crypto' => [ // This server's entry on Rebirth must have the same crypto keys as below, and vice versa.
            'key' => 'RebirthKey',
            'iv' => 'RebirthVector',
        ],
    ],
];
