<?php
/**
 * @description handler event
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

class Handler implements EventInterface
{
    /**
     * @description packet
     *
     * @var string
     */
    private string $packet;

    /**
     * @description fd
     *
     * @var int
     */
    private int $fd;

    /**
     * @description ip
     *
     * @var string
     */
    private string $ip;

    /**
     * @description trace id
     *
     * @var string
     */
    private string $traceId;

    private string $spanId;

    /**
     * @description construct
     *
     * @param Message $packet
     *
     * @param int $fd
     *
     * @param string $ip
     *
     * @param string $traceId
     *
     * @return Handler
     */
    public function __construct(string $packet, int $fd, string $ip, string $traceId, string $spanId)
    {
        $this->packet = $packet;
        $this->fd = $fd;
        $this->ip = $ip;
        $this->traceId = $traceId;
        $this->spanId = $spanId;
    }

    /**
     * @description get packet
     *
     * @return string
     */
    public function getPacket() : string
    {
        return $this->packet;
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
     * @description get ip
     *
     * @return string
     */
    public function getIp() : string
    {
        return $this->ip;
    }

    /**
     * @description get trace id
     *
     * @return string
     */
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

    public function getSpanId() : string
    {
        return $this->spanId;
    }
}
