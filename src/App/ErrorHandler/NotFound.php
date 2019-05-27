<?php

namespace App\ErrorHandler;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;

class NotFound
{
    protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(Request $HttpRequest, Response $HttpResponse)
    {
        if ('cohportal' == $HttpRequest->getHeader('User-Agent')) {
            return $HttpRequest
                ->withStatus(404)
                ->withHeader('Content-Type', 'text/plain')
                ->write('Resource not found');
        } else {
            return $this->container->get('renderer')->render($HttpResponse->withStatus(404), 'core/page-generic-message.phtml', [
                'title' => 'Resource not found',
                'message' => 'The resource you were looking for was not found.', ]);
        }
    }
}
