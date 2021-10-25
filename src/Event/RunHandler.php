<?php
/**
 * @description run hander event
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
use Kovey\Websocket\Handler\HandlerAbstract;
use Google\Protobuf\Internal\Message;

class RunHandler implements EventInterface
{
    /**
     * @description handler
     *
     * @var HandlerAbstract
     */
    private HandlerAbstract $handler;

    /**
     * @description method
     *
     * @var string
     */
    private string $method;

    /**
     * @description message
     *
     * @var Message
     */
    private Message $message;

    /**
     * @description fd
     *
     * @var int
     */
    private int $fd;

    /**
     * @description base
     *
     * @var Message
     */
    private Message $base;

    /**
     * @description construct
     *
     * @param HandlerAbstract $handler
     *
     * @param string $method
     *
     * @param Message $message
     *
     * @param int $fd
     *
     * @return RunHandler
     */
    public function __construct(HandlerAbstract $handler, string $method, Message $message, int $fd, Message $base)
    {
        $this->handler = $handler;
        $this->fd = $fd;
        $this->method = $method;
        $this->message = $message;
        $this->base = $base;
    }

    /**
     * @description get handler
     *
     * @return HandlerAbstract
     */
    public function getHandler() : HandlerAbstract
    {
        return $this->handler;
    }

    /**
     * @description get message
     *
     * @return Message
     */
    public function getMessage() : Message
    {
        return $this->message;
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
     * @description get method
     *
     * @return string
     */
    public function getMethod() : string
    {
        return $this->method;
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
     * @description get base
     *
     * @return Message
     */
    public function getBase() : Message
    {
        return $this->base;
    }
}
