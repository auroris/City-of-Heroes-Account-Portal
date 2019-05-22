<?php

namespace App\Controller;

use Psr\Container\ContainerInterface;
use Slim\Http\Request;
use Slim\Http\Response;
use App\Model\Vars;
use App\Model\Character;
use App\Util\DataHandling;

//http://10.5.0.91/City-of-Heroes-Account-Portal/public/api/character/raw?q=3V89%2BliJFSpjTV6NTeUccA%3D%3D
//http://10.5.0.91/City-of-Heroes-Account-Portal/public/character/dev?q=3V89%2BliJFSpjTV6NTeUccA%3D%3D

class CharacterController
{
    protected $container;

    // constructor receives container instance
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function Dev(Request $HttpRequest, Response $HttpResponse, array $HttpArgs)
    {
        echo '<pre>';

        $ouroboros = new Vars('ouroborosv1');
        $i25 = new Vars('i25');

        $character = new Character(DataHandling::Decrypt($_GET['q'], getenv('portal_key'), getenv('portal_iv')));

        foreach ($character->Powers as $power) {
            if ($ouroboros->Exists($power['PowerName']) &&
                $ouroboros->Exists($power['CategoryName']) &&
                $ouroboros->Exists($power['PowerSetName'])
            ) {
                echo $power['PowerName']." ouroboros validated\n";
            } else {
                echo $power['PowerName']." ouroboros fail\n";
                print_r($power);
            }

            if ($i25->Exists($power['PowerName']) &&
                $i25->Exists($power['CategoryName']) &&
                $i25->Exists($power['PowerSetName'])
            ) {
                echo $power['PowerName']." issue25 validated\n";
            } else {
                echo $power['PowerName']." issue25 fail\n";
                print_r($power);
            }

            //if ($vars->Exists($power['PowerName'])) {
            //    echo $power['PowerName']." exists\n";
            //} else {
            //    echo $power['PowerName']." fail\n";
            //}
            //print_r($power);
        }
    }
}
