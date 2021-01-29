<?php
/**
 * @description connection close event
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

class Close implements EventInterface
{
    /**
     * @description connection fd
     *
     * @var int
     */
    private int $fd;

    /**
     * @description construct
     *
     * @param int $fd
     *
     * @return Close
     */
    public function __construct(int $fd)
    {
        $this->fd = $fd;
    }

    /**
     * @description get fd
     *
     * @return int
     */
    public function getFd() : int
    {
        return $this->fd;
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
