<?php

// The web portal's configuration
$dotenv = Dotenv\Dotenv::create(__DIR__, '/../config/config.env');
$dotenv->load();
$dotenv->required(['db_server', 'db_database', 'db_username', 'db_password', 'dbquery',
                    'portal_name', 'portal_url', 'portal_key', 'portal_iv', 'cohauth', 'cohdb',
                    'portal_lfg_only', 'portal_hide_csr', 'portal_patchset', 'email_username', 'email_password', 'portal_use', 'portal_style', 'user_data', ]);

// App root dir:
$GLOBALS['ROOT'] = __DIR__.'/..';

// The Federation's configuration
include $GLOBALS['ROOT'].'/config/federation.php';

// Load map lists
\App\Model\Maps::Generate();

// SQL Parser settings
PhpMyAdmin\SqlParser\Context::setMode('MSSQL');

// Slim PHP settings
return [
    'settings' => [
        'displayErrorDetails' => ('dev' == getenv('portal_use') ? true : false),
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
