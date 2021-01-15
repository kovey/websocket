<?php
/**
 * @description
 *
 * @package
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
    private Message $packet;

    private int $action;

    public function __construct(Message $packet, int $action)
    {
        $this->packet = $packet;
        $this->action = $action;
    }

    public function getPacket() : Message
    {
        return $this->packet;
    }

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
