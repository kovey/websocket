<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2021-01-12 14:33:57
 *
 */
namespace Kovey\Websocket\Event;

use Kovey\Event\EventInterface;
use Google\Protobuf\Internal\Message;

class Handler implements EventInterface
{
    private Message $packet;

    private int $fd;

    private string $ip;

    private string $traceId;

    public function __construct(Message $packet, int $fd, string $ip, string $traceId)
    {
        $this->packet = $packet;
        $this->fd = $fd;
        $this->ip = $ip;
        $this->traceId = $traceId;
    }

    public function getPacket() : Message
    {
        return $this->packet;
    }

    public function getFd() : int
    {
        return $this->fd;
    }

    public function getIp() : string
    {
        return $this->ip;
    }

    public function getTraceId() : string
    {
        return $this->traceId;
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
