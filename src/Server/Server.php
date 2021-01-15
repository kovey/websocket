<?php
/**
 * @description Websocket服务器, 基于protobuf
 *
 * @package Server
 *
 * @author kovey
 *
 * @time 2019-11-13 14:43:19
 *
 */
namespace Kovey\Websocket\Server;

use Kovey\Logger\Logger;
use Google\Protobuf\Internal\Message;
use Kovey\Library\Exception\CloseConnectionException;
use Kovey\Library\Exception\KoveyException;
use Kovey\Library\Exception\ProtocolException;
use Kovey\Library\Server\PortInterface;
use Kovey\Websocket\Event;
use Kovey\Event\Dispatch;
use Kovey\Event\Listener\Listener;
use Kovey\Event\Listener\ListenerProvider;

class Server implements PortInterface
{
    /**
     * @description 服务器
     *
     * @var Swoole\Websocket\Server
     */
    private \Swoole\WebSocket\Server $serv;

    /**
     * @description 配置
     *
     * @var Array
     */
    private Array $conf;

    /**
     * @description 事件
     *
     * @var Array
     */
    private Array $onEvents;

    /**
     * @description 允许的事件
     *
     * @var Array
     */
    private Array $allowEvents;

    /**
     * @description 是否运行在docker中
     *
     * @var bool
     */
    private bool $isRunDocker;

    private Dispatch $dispatch;

    private ListenerProvider $provider;

    /**
     * @description 构造函数
     *
     * @param Array $conf
     *
     * @return Server
     */
    public function __construct(Array $conf)
    {
        $this->conf = $conf;
        $this->isRunDocker = ($this->conf['run_docker'] ?? 'Off') === 'On';
        $this->onEvents = array();
        $this->provider = new ListenerProvider();
        $this->dispatch = new Dispatch($this->provider);

        $logDir = $this->conf['logger_dir'] . '/server';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $pidDir = dirname($this->conf['pid_file']);
        if (!is_dir($pidDir)) {
            mkdir($pidDir, 0777, true);
        }

        $this->serv = new \Swoole\WebSocket\Server($this->conf['host'], $this->conf['port']);
        $this->serv->set(array(
            'enable_coroutine' => true,
            'worker_num' => $this->conf['worker_num'],
            'daemonize' => !$this->isRunDocker,
            'pid_file' => $this->conf['pid_file'],
            'log_file' => $this->conf['logger_dir'] . '/server/server.log',
            'event_object' => true,
            'log_rotation' => SWOOLE_LOG_ROTATION_DAILY,
            'log_date_format' => '%Y-%m-%d %H:%M:%S'
        ));


        $this->initAllowEvents()
            ->initCallback();
    }

    /**
     * @description 初始化允许的事件
     *
     * @return Server
     */
    private function initAllowEvents() : Server
    {
        $this->allowEvents = array(
            'handler' => Event\Hander::class,
            'pipeMessage' => Event\PipeMessage::class,
            'initPool' => Event\InitPool::class,
            'pack' => Event\Pack::class,
            'unpack' => Event\Unpack::class,
            'error' => Event\Error::class,
            'close' => Event\Close::class,
            'open' => Event\Open::class
        );

        return $this;
    }

    /**
     * @description 初始化回调
     *
     * @return Server
     */
    private function initCallback() : Server
    {
        $this->serv->on('open', array($this, 'open'));
        $this->serv->on('message', array($this, 'message'));
        $this->serv->on('close', array($this, 'close'));
        $this->serv->on('pipeMessage', array($this, 'pipeMessage'));
        $this->serv->on('workerStart', array($this, 'workerStart'));
        $this->serv->on('managerStart', array($this, 'managerStart'));
        return $this;
    }

    /**
     * @description manager 启动回调
     *
     * @param Swoole\Server $serv
     *
     * @return null
     */
    public function managerStart(\Swoole\WebSocket\Server $serv)
    {
        ko_change_process_name($this->conf['name'] . ' master');
    }

    /**
     * @description worker 启动回调
     *
     * @param Swoole\Server $serv
     *
     * @param int $workerId
     *
     * @return null
     */
    public function workerStart(\Swoole\WebSocket\Server $serv, $workerId)
    {
        ko_change_process_name($this->conf['name'] . ' worker');

        try {
            $this->dispatch->dispatch(new Event\InitPool($this));
        } catch (\Throwable $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e);
        }
    }

    /**
     * @description 添加事件
     *
     * @param string $events
     *
     * @param callable $cal
     *
     * @return Server
     *
     * @throws Exception
     */
    public function on(string $event, callable | Array $call) : PortInterface
    {
        if (!isset($this->allowEvents[$event])) {
            return $this;
        }

        if (!is_callable($call)) {
            throw new KoveyException(sprintf('%s event is not callable', $event), 500);
        }

        $this->onEvents[$event] = $event;
        $listener = new Listener();
        $listener->addEvent($this->allowEvents[$event], $call);
        $this->provider->addListener($listener);

        return $this;
    }

    /**
     * @description 管道事件回调
     *
     * @param Swoole\Server $serv
     *
     * @param int $workerId
     *
     * @param mixed $data
     *
     * @return null
     */
    public function pipeMessage(\Swoole\WebSocket\Server $serv, \Swoole\Server\PipeMessage $message)
    {
        try {
            $this->dispatch->dispatch(new Event\PipeMessage($message->data));
        } catch (\Throwable $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $message->data['t'] ?? '');
        }
    }

    /**
     * @description 链接回调
     *
     * @param Swoole\Server $serv
     *
     * @param Swoole\Http\Request $request
     *
     * @return null
     */
    public function open(\Swoole\WebSocket\Server $serv, \Swoole\Http\Request $request)
    {
        try {
            $this->dispatch->dispatch(new Event\Open($request));
        } catch (CloseConnectionException $e) {
            $serv->disconnect($request->fd, WebsocketCode::THROW_CLOSE_CONNECTION_EXCEPTION, 'THROW_CLOSE_CONNECTION_EXCEPTION');
        } catch (\Throwable $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e);
        }
    }

    /**
     * @description 接收回调
     *
     * @param Swoole\Server $serv
     *
     * @param int $fd
     *
     * @param Frame $frame
     *
     * @return null
     */
    public function message(\Swoole\WebSocket\Server $serv, \Swoole\WebSocket\Frame $frame)
    {
        if ($frame->opcode != SWOOLE_WEBSOCKET_OPCODE_BINARY) {
            $serv->disconnect($frame->fd, WebsocketCode::STREAM_ERROR, 'STREAM_ERROR');
            return;
        }

        $traceId = hash('sha256', uniqid($frame->fd, true) . random_int(1000000, 9999999));
        try {
            $protobuf = $this->dispatch->dispatchWithReturn(new Event\Unpack($frame->data));
            if (empty($protobuf)) {
                throw new Exception('unpack error', 500, 'unpack_exception');
            }

            $this->handler($protobuf, $frame->fd, $traceId);
        } catch (CloseConnectionException $e) {
            $serv->disconnect($frame->fd, WebsocketCode::THROW_CLOSE_CONNECTION_EXCEPTION, 'THROW_CLOSE_CONNECTION_EXCEPTION');
            Logger::writeExceptionLog(__LINE__, __FILE__, $traceId);
        } catch (ProtocolException $e) {
            $serv->disconnect($frame->fd, WebsocketCode::PROTOCOL_ERROR, 'PROTOCOL_ERROR');
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $traceId);
        } catch (\Throwable $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $traceId);
        }
    }

    /**
     * @description Handler 处理
     *
     * @param Message $packet
     *
     * @param int $fd
     *
     * @param string $traceId
     *
     * @return null
     */
    private function handler(Message $packet, $fd, string $traceId)
    {
        $result = $this->dispatch->dispatchWithReturn(new Event\Handler($packet, $fd, $this->getClientIP($fd), $traceId));
        if (empty($result) || !isset($result['message']) || !isset($result['action'])) {
            return;
        }

        if (!$result['message'] instanceof Message) {
            $this->serv->disconnect($fd, WebsocketCode::PROTOCOL_ERROR, 'PROTOCOL_ERROR');
            return;
        }

        $this->send($result['message'], $result['action'], $fd);
    }

    /**
     * @description 发送数据
     *
     * @param Message $packet
     *
     * @param int $fd
     *
     * @return null
     */
    private function send(Message $packet, int $action, $fd)
    {
        $data = $this->dispatch->dispatchWithReturn(new Event\Pack($packet, $action));
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
     * @description 关闭链接
     *
     * @param Swoole\Server $serv
     *
     * @param int $fd
     *
     * @return null
     */
    public function close(\Swoole\Server $serv, $fd)
    {
        try {
            $this->dispatch->dispatch(new Event\Close($fd));
        } catch (\Throwable $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e);
        }
    }

    /**
     * @description 启动服务
     *
     * @return null
     */
    public function start()
    {
        $this->serv->start();
    }

    /**
     * @description 获取底层服务
     *
     * @return Swoole\Server
     */
    public function getServ() : \Swoole\WebSocket\Server
    {
        return $this->serv;
    }

    /**
     * @description 获取远程ID
     *
     * @param int $fd
     *
     * @return string
     */
    public function getClientIP($fd) : string
    {
        $info = $this->serv->getClientInfo($fd);
        if (empty($info)) {
            return '';
        }

        return $info['remote_ip'] ?? '';
    }
}
