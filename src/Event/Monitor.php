<?php
/**
 * @description monitor event
 *
 * @package Kovey\Websocket\Event
 *
 * @author kovey
 *
 * @time 2021-01-08 10:02:48
 *
 */
namespace Kovey\Websocket\Event;

use Kovey\Event\EventInterface;

class Monitor implements EventInterface
{
    /**
     * @description monitor data
     *
     * @var Array
     */
    private Array $data;

    /**
     * @description construct
     *
     * @param Array $data
     *
     * @return Monitor
     */
    public function __construct(Array $data)
    {
        $this->data = $data;
    }

    /**
     * @description get data
     *
     * @return Array
     */
    public function getData() : Array
    {
        return $this->data;
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
