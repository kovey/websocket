<?php
/**
 * @description unpack event
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

class Unpack implements EventInterface
{
    /**
     * @description stream
     * 
     * @var string
     */
    private string $stream;

    /**
     * @description construct
     *
     * @param string $stream
     *
     * @return Unpack
     */
    public function __construct(string $stream)
    {
        $this->stream = $stream;
    }

    /**
     * @description get stream
     *
     * @return string
     */
    public function getStream() : string
    {
        return $this->stream;
    }

    /**
     * @description get data
     *
     * @deprecated removed 3.0
     *
     * @return string
     */
    public function getData() : string
    {
        trigger_error('Kovey\Websocket\Event\Unpack::getData is deprecated, we will remove this method in future.', E_USER_WARNING);

        return $this->getStream();
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
