<?php

namespace App\ErrorHandler;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Util\MonoLogger;

class GeneralError
{
    protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function __invoke(Request $HttpRequest, Response $HttpResponse, $exception)
    {
        $errormessage = $exception->getMessage()."\nOn ".$exception->getFile().' line '.$exception->getLine().'\n'.$exception->getTraceAsString();

        if ('cohportal' == $HttpRequest->getHeader('User-Agent')) {
            return $HttpRequest
                ->withStatus(500)
                ->withHeader('Content-Type', 'text/plain')
                ->write($errormessage);
        } else {
            if ('dev' == getenv('portal_use')) {
                return $this->container->get('renderer')->render($HttpResponse->withStatus(500), 'core/page-generic-message.phtml', [
                    'title' => 'Error',
                    'message' => '<pre>'.$errormessage.'</pre>', ]);
            } else {
                MonoLogger::GetLogger()->error($errormessage);

                return $this->container->get('renderer')->render($HttpResponse->withStatus(500), 'core/page-generic-message.phtml', [
                    'title' => 'Error',
                    'message' => $exception->getMessage(), ]);
            }
        }
    }
}
