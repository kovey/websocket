<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2021-04-13 16:43:55
 *
 */
namespace Kovey\Websocket\Server;

use Kovey\Websocket\Event;
use Kovey\Event\EventManager;
use Kovey\Logger\Logger;

class Close
{
    public function close(EventManager $event, int $fd) : Close
    {
        try {
            $event->dispatch(new Event\Close($fd));
        } catch (\Throwable $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e);
        }

        return $this;
    }
}
