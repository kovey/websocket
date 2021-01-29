<?php
/**
 * @description open event
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
use Swoole\Http\Request;

class Open implements EventInterface
{
    /**
     * @description request object
     *
     * @var Request
     */
    private Request $request;

    /**
     * @description construct
     *
     * @param Request $request
     *
     * @return Open
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @description get request
     *
     * @return Request
     */
    public function getRequest() : Request
    {
        return $this->request;
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
