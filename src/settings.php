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

// List of federation servers
$GLOBALS["federation"] = [
    ["Name" => "Aurora Server", "Url" => "https://coh.westus2.cloudapp.azure.com/auroris/public"]
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
