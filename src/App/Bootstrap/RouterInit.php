<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2021-04-07 13:40:02
 *
 */
namespace Kovey\Websocket\App\Bootstrap;

use Kovey\Websocket\App\App;
use Kovey\Websocket\App\Router\Router;
use Kovey\Container\Event;

class RouterInit
{
    /**
     * @description init parse inject
     *
     * @param Application $app
     *
     * @return void
     */
    public function __initRouterInInject(App $app) : void
    {
        $app->registerLocalLibPath(APPLICATION_PATH . '/application');
        $handler = $app->getConfig()['websocket']['handler'];
        $app->getContainer()
            ->on('Protocol', function (Event\Protocol $event) use ($app) {
                $router = new Router($event->getProtobuf(), $event->getRouter(), $event->getProtobufBase());
                $app->registerRouter($event->getCode(), $router);
            })
            ->parse(APPLICATION_PATH . '/application/' . $handler, $handler);
    }
}
