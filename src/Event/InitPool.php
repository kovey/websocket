<?php
/**
 * @description init pool event
 *
 * @package Kovey\Websocket\Event
 *
 * @author kovey
 *
 * @time 2021-01-12 14:33:57
 *
 */
namespace Kovey\Websocket\Event;

use Kovey\Event\EventInterface;
use Kovey\Websocket\Server\Server;

class InitPool implements EventInterface
{
    /**
     * @description server
     *
     * @var Server
     */
    private Server $server;

    /**
     * @description construct
     *
     * @param Server $server
     *
     * @return InitPool
     */
    public function __construct(Server $server)
    {
        $this->server = $server;
    }

    /**
     * @description get server
     *
     * @return Server
     */
    public function getServer() : Server
    {
        return $this->server;
    }

    /**
     * @description propagation stopped
     *
     * @return bool
     */
    public function isPropagationStopped() : bool
    {
        return true;
    }

    /**
     * @description stop propagation
     *
     * @return EventInterface
     */
    public function stopPropagation() : EventInterface
    {
        return $this;
    }
}
