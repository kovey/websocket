<?php
/**
 * @description protobuf event
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
use Google\Protobuf\Internal\Message;

class Protobuf implements EventInterface
{
    /**
     * @description packet
     *
     * @var Message
     */
    private Message $packet;

    /**
     * @description construct
     *
     * @param Message $packet
     *
     * @return Protobuf
     */
    public function __construct(Message $packet)
    {
        $this->packet = $packet;
    }

    /**
     * @description get packet
     *
     * @return Message
     */
    public function getPacket() : Message
    {
        return $this->packet;
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
