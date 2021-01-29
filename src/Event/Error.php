<?php
/**
 * @description error event
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

class Error implements EventInterface
{
    /**
     * @description error info
     *
     * @var string | \Throwable
     */
    private \Throwable | string $error;

    /**
     * @description construct
     *
     * @param \Throwable | string $error
     *
     * @return Error
     */
    public function __construct(\Throwable | string $error)
    {
        $this->error = $error;
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
     * @description get error info
     *
     * @return string | \Throwable
     */
    public function getError() : string | \Throwable
    {
        return $this->error;
    }
}
