<?php

use Slim\App;

return function (App $app) {
    $container = $app->getContainer();

    // Coss-site request forgery guard
    $container['csrf'] = function ($c) {
        $csrf = new \Slim\Csrf\Guard;
        $csrf->setPersistentTokenMode(true);
        return $csrf;
    };
    $app->add($container->get('csrf'));
    
    // view renderer
    $container['renderer'] = function ($c) {
        $settings = $c->get('settings')['renderer'];
        $view = new \Slim\Views\Twig($settings['template_path'], ['cache' => $settings['cache_path']]);

        // Instantiate and add Slim specific extension
        $router = $c->get('router');
        $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
        $view->addExtension(new \Slim\Views\TwigExtension($router, $uri));
        $view->addExtension(new \App\TwigExtension\CsrfExtension($c->get('csrf')));
        
        return $view;
    };

    // monolog
    $container['logger'] = function ($c) {
        $settings = $c->get('settings')['logger'];
        $logger = new \Monolog\Logger($settings['name']);
        $logger->pushProcessor(new \Monolog\Processor\UidProcessor());
        $logger->pushHandler(new \Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
        return $logger;
    };
};
