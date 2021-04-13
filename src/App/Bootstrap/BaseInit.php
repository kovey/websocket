<?php
/**
 * @description bootstrap
 *
 * @package     App\Bootstrap
 *
 * @time        Tue Sep 24 09:00:10 2019
 *
 * @author      kovey
 */
namespace Kovey\Websocket\App\Bootstrap;

use Kovey\Websocket\App\App;
use Kovey\Websocket\Server\Server;
use Kovey\Websocket\App\Router\Routers;

class BaseInit
{
    /**
     * @description init app
     *
     * @param App $app
     *
     * @return void
     */
    public function __initApp(App $app) : void
    {
        $app->registerServer(new Server($app->getConfig()['server']))
            ->registerRouters(new Routers());
    }
}
