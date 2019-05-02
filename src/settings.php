<?php

// Your DB connection information and credentials
$GLOBALS["db"] =
[
    'Server' => 'localhost\sqlexpress',
    'Database' => 'cohauth', 
    'Username'=>'CoHDB', 
    'Password'=>'bqaDDMA7QUKNABYdKQrj'
];

// Path to DBQuery
$GLOBALS["dbquery"] = "C:\\Issue25Server\\bin\\dbquery.exe";

$GLOBALS["features] = [
    "NoDuplicateIPs" => false,     // true to disallow multiple account registration from the same IP
    "AllowNonFederatedPulls" => true // Allows users to pull characters from here to other servers that we haven't federated with
];
 
// List of federation servers
$GLOBALS["federation"] = [
    [
        "Name" => "Aurora Server", 
        "Url" => "https://coh.westus2.cloudapp.azure.com/auroris/public",
        "Policy" => [ // Policy for characters coming from 'Aurora Server'
            "ForceInfluence" => 0, // 0 (or any number) to force inf to that number; false to disable and allow whatever the character has
            "AllowInventory" => false, // false to delete the inventory, true to allow whatever the character has
        ]
    ],
    [ 
        "Name" => "City Of Heroes Rebirth",
        "Url" => "https://play.cityofheroesrebirth.com/portal/public/",
        "Policy" => [ // Policy for characters coming from CoH Rebirth
            "ForceInfluence" => false, // Allow inf to carry over
            "AllowInventory" => true // Allow inventory to carry over
        ]
    ]
];

// Your cryptographic keys used for character transfers
$GLOBALS["crypto"] = [
    "key" => "Some Key",
    "iv" => "Some Initialization Vector"
];

// Slim PHP settings
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
            'cache_path' => false, //__DIR__ . '/../cache/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ]
    ]
];
