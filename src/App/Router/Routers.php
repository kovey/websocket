<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2021-04-12 15:40:55
 *
 */
namespace Kovey\Websocket\App\Router;

class Routers implements RoutersInterface
{
    private Array $routers;

    private string $base = '';

    public function __construct()
    {
        $this->routers = Array();
    }

    public function addRouter(string | int $code, RouterInterface $router) : RoutersInterface
    {
        $this->routers[$code] = $router;
        $this->base = $router->getProtobufBase();
        return $this;
    }

    public function getRouter(string | int $code) : ?RouterInterface
    {
        return $this->routers[$code] ?? null;
    }

    public function getBase() : string
    {
        return $this->base;
    }
}
