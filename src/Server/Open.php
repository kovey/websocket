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
use Kovey\Library\Exception\CloseConnectionException;

class Open
{
    public function open(EventManager $event, \Swoole\Websocket\Server $serv, \Swoole\Http\Request $request) : Open
    {
        try {
            $event->dispatch(new Event\Open($request));
        } catch (CloseConnectionException $e) {
            $serv->disconnect($request->fd, WebsocketCode::THROW_CLOSE_CONNECTION_EXCEPTION, 'THROW_CLOSE_CONNECTION_EXCEPTION');
        } catch (\Throwable $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e);
        }

        return $this;
    }
}
