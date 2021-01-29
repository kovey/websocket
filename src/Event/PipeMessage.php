<?php
/**
 * @description pipe message event
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

class PipeMessage implements EventInterface
{
    /**
     * @description data
     *
     * @var Array
     */
    private Array $data;

    /**
     * @description construct
     *
     * @param Array $data
     *
     * @return PipeMessage
     */
    public function __construct(Array $data)
    {
        $this->data = $data;
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

    /**
     * @description get path
     *
     * @return string
     */
    public function getPath() : string
    {
        return $this->data['p'] ?? '';
    }

    /**
     * @description get method
     *
     * @return string
     */
    public function getMethod() : string
    {
        return $this->data['m'] ?? '';
    }

    /**
     * @description get arguments
     *
     * @return Array
     */
    public function getArgs() : Array
    {
        return $this->data['a'] ?? array();
    }

    /**
     * @description get trace id
     *
     * @return string
     */
    public function getTraceId() : string
    {
        return $this->data['t'] ?? '';
    }
}
