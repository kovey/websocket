<?php
/**
 * @description pack event
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

class Pack implements EventInterface
{
    /**
     * @description packet
     *
     * @var Message
     */
    private Message $packet;

    /**
     * @description action
     *
     * @var int
     */
    private int $action;

    /**
     * @description construct
     *
     * @return Pack
     */
    public function __construct(Message $packet, int $action)
    {
        $this->packet = $packet;
        $this->action = $action;
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
     * @description get action
     *
     * @return int
     */
    public function getAction() : int
    {
        return $this->action;
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
