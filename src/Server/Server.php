<?php
/**
 * @description Websocket server
 *
 * @package Server
 *
 * @author kovey
 *
 * @time 2019-11-13 14:43:19
 *
 */
namespace Kovey\Websocket\Server;

use Google\Protobuf\Internal\Message;
use Kovey\Websocket\Event;
use Kovey\App\Components\ServerAbstract;

class Server extends ServerAbstract
{
    const PACKET_MAX_LENGTH = 2097152;

    /**
     * @description 初始化服务
     *
     * @return void
     */
    protected function initServer()
    {
        $this->serv = new \Swoole\WebSocket\Server($this->config['host'], $this->config['port']);
        $this->serv->set(array(
            'enable_coroutine' => true,
            'worker_num' => $this->config['worker_num'],
            'daemonize' => !$this->isRunDocker,
            'pid_file' => $this->config['pid_file'],
            'log_file' => $this->config['logger_dir'] . '/server/server.log',
            'event_object' => true,
            'log_rotation' => SWOOLE_LOG_ROTATION_DAILY,
            'log_date_format' => '%Y-%m-%d %H:%M:%S'
        ));

        $this->initAllowEvents()
            ->initCallback();
    }

    /**
     * @description init events support
     *
     * @return Server
     */
    private function initAllowEvents() : Server
    {
        $this->event->addSupportEvents(array(
            'handler' => Event\Handler::class,
            'error' => Event\Error::class,
            'close' => Event\Close::class,
            'open' => Event\Open::class,
            'pack' => Event\Pack::class
        ));

        return $this;
    }

    /**
     * @description init callback
     *
     * @return Server
     */
    private function initCallback() : Server
    {
        $this->serv->on('open', array($this, 'open'));
        $this->serv->on('message', array($this, 'message'));
        $this->serv->on('close', array($this, 'close'));
        return $this;
    }

    /**
     * @description connect event
     *
     * @param Swoole\Server $serv
     *
     * @param Swoole\Http\Request $request
     *
     * @return void
     */
    public function open(\Swoole\WebSocket\Server $serv, \Swoole\Http\Request $request) : void
    {
        $open = new Open();
        $open->open($this->event, $serv, $request);
    }

    /**
     * @description receive event
     *
     * @param Swoole\Server $serv
     *
     * @param Frame $frame
     *
     * @return void
     */
    public function message(\Swoole\WebSocket\Server $serv, \Swoole\WebSocket\Frame $frame) : void
    {
        if ($frame->opcode != SWOOLE_WEBSOCKET_OPCODE_BINARY) {
            $serv->disconnect($frame->fd, WebsocketCode::STREAM_ERROR, 'STREAM_ERROR');
            return;
        }

        $receive = new Receive($frame->data, $this->getClientIP($frame->fd), $frame->fd, $this->config['name']);
        $receive->begin()
                ->run($this->event, $serv)
                ->end($this)
                ->monitor($this);
    }

    /**
     * @description send data to client
     *
     * @param Message $packet
     *
     * @param int $fd
     *
     * @return void
     */
    public function send(Message $packet, int | string $action, int $fd, array $ext = array()) : bool
    {
        if (!$this->serv->exist($fd) || !$this->serv->isEstablished($fd)) {
            return false;
        }

        $data = $this->event->dispatchWithReturn(new Event\Pack($packet, $action, $ext));
        $len = strlen($data);
        if ($len <= self::PACKET_MAX_LENGTH) {
            return $this->serv->push($fd, $data, SWOOLE_WEBSOCKET_OPCODE_BINARY);
        }

        $sendLen = 0;
        while ($sendLen < $len) {
            $this->serv->push($fd, substr($data, $sendLen, self::PACKET_MAX_LENGTH), SWOOLE_WEBSOCKET_OPCODE_BINARY, ($sendLen + self::PACKET_MAX_LENGTH) >= $len);
            $sendLen += self::PACKET_MAX_LENGTH;
        }

        return true;
    }

    /**
     * @description close connection
     *
     * @param Swoole\Server $serv
     *
     * @param Swoole\Server\Event $fd
     *
     * @return void
     */
    public function close(\Swoole\Server $serv, \Swoole\Server\Event $event) : void
    {
        $close = new Close();
        $close->close($this->event, $event->fd);
    }
}
