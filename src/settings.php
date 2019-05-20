<?php

// The web portal's configuration
$dotenv = Dotenv\Dotenv::create(__DIR__, 'Config/config.env');
$dotenv->load();
$dotenv->required(['db_server', 'db_database', 'db_username', 'db_password', 'dbquery',
                    'portal_name', 'portal_url', 'portal_key', 'portal_iv', 'cohauth', 'cohdb',
                    'portal_lfg_only', 'portal_hide_csr', ]);

// The Federation's configuration
require_once __DIR__.'/Config/federation.php';

// Load map lists
\App\Model\Maps::Generate();

// Slim PHP settings
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
        'determineRouteBeforeAppMiddleware' => true,

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__.'/../templates/',
            'cache_path' => false, //__DIR__ . '/../cache/',
        ],

        // Monolog settings
        'logger' => [
            'name' => 'portal',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__.'/../logs/app.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
    ],
];
