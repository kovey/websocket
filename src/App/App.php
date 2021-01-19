<?php
/**
 * @description Global app object
 *
 * @package     Websocket\App
 *
 * @time        2020-03-08 15:51:43
 *
 * @author      kovey
 */
namespace Kovey\Websocket\App;

use Kovey\Websocket\Handler\HandlerAbstract;
use Kovey\Process\ProcessAbstract;
use Kovey\Connection\Pool\PoolInterface;
use Kovey\Container\ContainerInterface;
use Kovey\Library\Config\Manager;
use Kovey\Websocket\App\Bootstrap\Autoload;
use Kovey\Websocket\Server\Server;
use Kovey\Process\UserProcess;
use Kovey\Logger\Logger;
use Kovey\Logger\Monitor;
use Google\Protobuf\Internal\Message;
use Kovey\Connection\AppInterface;
use Kovey\Library\Util\Json;
use Kovey\Websocket\Event;
use Kovey\Event\Dispatch;
use Kovey\Event\Listener\Listener;
use Kovey\Event\Listener\ListenerProvider;
use Kovey\Library\Exception\KoveyException;

class App implements AppInterface
{
    /**
     * @description App instance
     *
     * @var App
     */
    private static App $instance;

    /**
     * @description Server
     *
     * @var Kovey\Websocket\Server\Server
     */
    private Server $server;

    /**
     * @description container
     *
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @description bootstrap
     *
     * @var Kovey\Websocket\Bootstrap\Bootstrap
     */
    private mixed $bootstrap;

    /**
     * @description custom bootstrap
     *
     * @var mixed
     */
    private mixed $customBootstrap;

    /**
     * @description config
     *
     * @var Array
     */
    private Array $config;

    /**
     * @description user process
     *
     * @var UserProcess
     */
    private UserProcess $userProcess;

    /**
     * @description connection pool
     *
     * @var Array
     */
    private Array $pools;

    /**
     * @description autoload
     *
     * @var Kovey\Websocket\App\Bootstrap\Autoload
     */
    private Autoload $autoload;

    /**
     * @description events listened
     *
     * @var Array
     */
    private Array $onEvents;

    /**
     * @description events support
     *
     * @var Array
     */
    private static Array $events = array(
        'monitor' => Event\Monitor::class,
        'protobuf' => Event\Protobuf::class,
        'run_handler' => Event\RunHandler::class,
        'pipeMessage' => Event\PipeMessage::class,
        'error' => Event\Error::class
    );

    /**
     * @description global veriable
     *
     * @var Array
     */
    private Array $globals;

    /**
     * @description other app object
     *
     * @var Array
     */
    private Array $otherApps;

    /**
     * @description event dispatcher
     *
     * @var Dispatch
     */
    private Dispatch $dispatch;

    /**
     * @description event listener provider
     *
     * @var ListenerProvider
     */
    private ListenerProvider $provider;

    /**
     * @description construct
     *
     * @return App
     */
    private function __construct()
    {
        $this->pools = array();
        $this->onEvents = array();
        $this->globals = array();
        $this->otherApps = array();
        $this->config = array();
        $this->provider = new ListenerProvider();
        $this->dispatch = new Dispatch($this->provider);
    }

    private function __clone()
    {}

    /**
     * @description get app instance
     *
     * @return App
     */
    public static function getInstance() : App
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @description register global veriable
     *
     * @param string $name
     *
     * @param mixed $val
     *
     * @return App
     */
    public function registerGlobal(string $name, mixed $val) : App
    {
        $this->globals[$name] = $val;
        return $this;
    }

    /**
     * @description get global veriable
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getGlobal(string $name) : mixed
    {
        return $this->globals[$name] ?? null;
    }

    /**
     * @description event listen
     *
     * @param string $event
     *
     * @param callable $callable
     *
     * @return App
     */
    public function on(string $event, callable | Array $callable) : App
    {
        if (!isset(self::$events[$event])) {
            return $this;
        }

        if (!is_callable($callable)) {
            return $this;
        }

        $this->onEvents[$event] = $event;
        $listener = new Listener();
        $listener->addEvent(self::$events[$event], $callable);
        $this->provider->addListener($listener);

        return $this;
    }

    /**
     * @description set config
     *
     * @param Array $config
     *
     * @return App
     */
    public function setConfig(Array $config) : App
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @description get config
     *
     * @return Array
     */
    public function getConfig() : Array
    {
        return $this->config;
    }

    /**
     * @description app start
     *
     * @return App
     */
    public function bootstrap() : App
    {
        if (is_object($this->bootstrap)) {
            $btfuns = get_class_methods($this->bootstrap);
            foreach ($btfuns as $fun) {
                if (substr($fun, 0, 6) !== '__init') {
                    continue;
                }

                $this->bootstrap->$fun($this);
            }
        }

        if (is_object($this->customBootstrap)) {
            $funs = get_class_methods($this->customBootstrap);
            foreach ($funs as $fun) {
                if (substr($fun, 0, 6) !== '__init') {
                    continue;
                }

                $this->customBootstrap->$fun($this);
            }
        }

        return $this;
    }

    /**
     * @description handler process
     *
     * @param Event\Handler $event
     *
     * @return Array
     */
    public function handler(Event\Handler $event) : Array
    {
        $begin = microtime(true);
        $reqTime = time();
        $message = array();
        $result = array();
        $monitorType = '';
        $trace = '';
        $err = '';
        try {
            if (!isset($this->onEvents['protobuf'])) {
                $monitorType = 'exception';
                if (isset($this->onEvents['error'])) {
                    $result = $this->dispatch->dispatchWithReturn(new Event\Error('protobuf event is not register'));
                }

                return $result;
            }

            $message = $this->dispatch->dispatchWithReturn(new Event\Protobuf($event->getPacket()));
            if (empty($message['handler']) || empty($message['method'])) {
                $monitorType = 'exception';
                if (isset($this->onEvents['error'])) {
                    $result = $this->dispatch->dispatchWithReturn(new Event\Error('unknown message'));
                }

                return $result;
            }

            $class = $this->config['websocket']['handler'] . '\\' . ucfirst($message['handler']);
            $keywords = $this->container->getKeywords($class, $message['method']);
            $instance = $this->container->get($class, $event->getTraceId(), $keywords['ext']);
            if (!$instance instanceof HandlerAbstract) {
                $monitorType = 'exception';
                if (isset($this->onEvents['error'])) {
                    $result = $this->dispatch->dispatchWithReturn(new Event\Error(sprintf('%s is not extends HandlerAbstract', ucfirst($message['handler']))));
                }

                return $result;
            }

            $instance->setClientIp($event->getIp());

            $monitorType = 'success';
            if (!isset($this->onEvents['run_handler'])) {
                $method = $message['method'];
                if ($keywords['openTransaction']) {
                    $keywords['database']->getConnection()->beginTransaction();
                    try {
                        $result = call_user_func(array($instance, $method), $message['message'], $event->getFd());
                        $keywords['database']->getConnection()->commit();
                    }  catch (\Throwable $e) {
                        $keywords['database']->getConnection()->rollBack();
                        throw $e;
                    }
                }  else {
                    $result = call_user_func(array($instance, $method), $message['message'], $event->getFd());
                }
                return $result;
            }

            if ($keywords['openTransaction']) {
                $keywords['database']->getConnection()->beginTransaction();
                try {
                    $result = $this->dispatch->dispatchWithReturn(new Event\RunHandler($instance, $message['method'], $message['message'], $event->getFd()));
                    $keywords['database']->getConnection()->commit();
                } catch (\Throwable $e) {
                    $keywords['database']->getConnection()->rollBack();
                    throw $e;
                }
            } else {
                $result = $this->dispatch->dispatchWithReturn(new Event\RunHandler($instance, $message['method'], $message['message'], $event->getFd()));
            }

            return $result;
        } catch (KoveyException $e) {
            $trace = $e->getTraceAsString();
            $err = $e->getMessage();
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $event->getTraceId());
            $monitorType = 'exception';
            if (isset($this->onEvents['error'])) {
                $result = $this->dispatch->dispatchWithReturn(new Event\Error($e));
            }

            return $result;
        } catch (\Throwable $e) {
            $trace = $e->getTraceAsString();
            $err = $e->getMessage();
            $monitorType = 'exception';
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $event->getTraceId());
            if (isset($this->onEvents['error'])) {
                $result = $this->dispatch->dispatchWithReturn(new Event\Error($e));
            }

            return $result;
        } finally {
            if (!isset($this->config['server']['monitor_open']) || $this->config['server']['monitor_open'] !== 'Off') {
                $this->sendToMonitor($reqTime, $begin, $event->getIp(), $monitorType, $event->getTraceId(), $message, $result, $trace, $err);
            }
        }
    }

    /**
     * @description monitor
     *
     * @param int $reqTime
     *
     * @param float $begin
     *
     * @param string $ip
     *
     * @param string $type
     *
     * @param string $traceId
     *
     * @param Array $message
     *
     * @param Array $result
     *
     * @return void
     */
    private function sendToMonitor(int $reqTime, float $begin, string $ip, string $type, string $traceId, Array $message, Array $result, string $trace, string $err) : void
    {
        $end = microtime(true);

        if (!empty($result['message'])) {
            if ($result['message'] instanceof Message) {
                $result['message'] = $result['message']->serializeToJsonString();
            }
        }

        $data = array(
            'delay' => round(($end - $begin) * 1000, 2),
            'request_time' => $begin * 10000,
            'class' => $message['handler'] ?? '',
            'method' => $message['method'] ?? '',
            'service' => $this->config['server']['name'],
            'service_type' => 'websocket',
            'type' => $type,
            'params' => Json::encode($message),
            'response' => Json::encode($result),
            'ip' => $ip,
            'time' => $reqTime,
            'timestamp' => date('Y-m-d H:i:s', $reqTime),
            'minute' => date('YmdHi', $reqTime),
            'traceId' => $traceId,
            'from' => $this->config['server']['name'],
            'end' => $end * 10000,
            'trace' => $trace,
            'err' => $err
        );

        $this->monitor($data);
    }

    /**
     * @description register autoload
     *
     * @param Autoload $autoload
     *
     * @return App
     */
    public function registerAutoload(Autoload $autoload) : App
    {
        $this->autoload = $autoload;
        return $this;
    }

    /**
     * @description register server
     *
     * @param Server $server
     *
     * @return App
     */
    public function registerServer(Server $server) : App
    {
        $this->server = $server;
        $this->server
            ->on('handler', array($this, 'handler'))
            ->on('pipeMessage', array($this, 'pipeMessage'))
            ->on('initPool', array($this, 'initPool'));

        return $this;
    }

    /**
     * @description pipe message
     *
     * @param Event\PipeMessage $event
     *
     * @return void
     */
    public function pipeMessage(Event\PipeMessage $event) : void
    {
        try {
            $this->dispatch->dispatch($event);
        } catch (\Exception $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $event->getTraceId());
        } catch (\Throwable $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e, $event->getTraceId());
        }
    }

    /**
     * @description init pools
     *
     * @param Event\InitPool $event
     *
     * @return void
     */
    public function initPool(Event\InitPool $event) : void
    {
        try {
            foreach ($this->pools as $pool) {
                if (is_array($pool)) {
                    foreach ($pool as $pl) {
                        $pl->init();
                        if (count($pl->getErrors()) > 0) {
                            Logger::writeErrorLog(__LINE__, __FILE__, implode(';', $pl->getErrors()));
                        }
                    }
                    continue;
                }
                $pool->init();
                if (count($pool->getErrors()) > 0) {
                    Logger::writeErrorLog(__LINE__, __FILE__, implode(';', $pool->getErrors()));
                }
            }
        } catch (\Exception $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e);
        } catch (\Throwable $e) {
            Logger::writeExceptionLog(__LINE__, __FILE__, $e);
        }
    }

    /**
     * @description monitor
     *
     * @param Array $data
     *
     * @return void
     */
    private function monitor(Array $data) : void
    {
        Monitor::write($data);
        $this->dispatch->dispatch(new Event\Monitor($data));
    }

    /**
     * @description register container
     *
     * @param ContainerInterface $container
     *
     * @return App
     */
    public function registerContainer(ContainerInterface $container) : App
    {
        $this->container = $container;
        return $this;
    }

    /**
     * @description check config
     *
     * @return App
     *
     * @throws KoveyException
     */
    public function checkConfig() : App
    {
        $fields = array(
            'server' => array(
                'host', 'port', 'logger_dir', 'pid_file'
            ), 
            'websocket' => array(
                'name', 'handler'
            )
        );

        foreach ($fields as $key => $field) {
            if (!isset($this->config[$key])) {
                throw new KoveyException("$key is not exists", 500);
            }

            foreach ($field as $fe) {
                if (!isset($this->config[$key][$fe])) {
                    throw new KoveyException("$fe of $key is not exists", 500);
                }
            }
        }

        return $this;
    }

    /**
     * @description register bootstrap
     *
     * @param mixed Bootstrap
     *
     * @return App
     */
    public function registerBootstrap(mixed $bootstrap) : App
    {
        $this->bootstrap = $bootstrap;
        return $this;
    }

    /**
     * @description register custom bootstrap
     *
     * @param mixed Bootstrap
     *
     * @return App
     */
    public function registerCustomBootstrap(mixed $bootstrap) : App
    {
        $this->customBootstrap = $bootstrap;
        return $this;
    }

    /**
     * @description user process
     *
     * @param UserProcess $userProcess
     *
     * @return App
     */
    public function registerUserProcess(UserProcess $userProcess) : App
    {
        $this->userProcess = $userProcess;
        return $this;
    }

    /**
     * @description get user process
     *
     * @return UserProcess
     */
    public function getUserProcess() : UserProcess
    {
        return $this->userProcess;
    }

    /**
     * @description register process
     *
     * @param string $name
     *
     * @param ProcessAbstract $process
     *
     * @return App
     */
    public function registerProcess(string $name, ProcessAbstract $process) : App
    {
        if (!is_object($this->server)) {
            return $this;
        }

        $process->setServer($this->server->getServ());
        $this->userProcess->addProcess($name, $process);
        return $this;
    }

    /**
     * @description register local library path
     *
     * @param string $path
     *
     * @return App
     */
    public function registerLocalLibPath(string $path) : App
    {
        if (!is_object($this->autoload)) {
            return $this;
        }

        $this->autoload->addLocalPath($path);
        return $this;
    }

    /**
     * @description register pool
     *
     * @param string $name
     *
     * @param PoolInterface $pool
     *
     * @param int $partition
     *
     * @return AppInterface
     */
    public function registerPool(string $name, PoolInterface $pool, int $partition = 0) : AppInterface
    {
        $this->pools[$name] ??= array();
        $this->pools[$name][$partition] = $pool;
        return $this;
    }

    /**
     * @description get pool
     *
     * @param string $name
     *
     * @param int $partition
     *
     * @return PoolInterface | null
     */
    public function getPool(string $name, int $partition = 0) : ? PoolInterface
    {
        return $this->pools[$name][$partition] ?? null;
    }

    /**
     * @description get container
     *
     * @return ContainerInterface
     */
    public function getContainer() : ContainerInterface
    {
        return $this->container;
    }

    /**
     * @description app start
     *
     * @return void
     *
     * @throws Exception
     */
    public function run() : void
    {
        if (!is_object($this->server)) {
            throw new KoveyException('server not register');
        }

        $this->server->start();
    }

    /**
     * @description send data to client
     *
     * @param Message $packet
     *
     * @param int $fd
     *
     * @return bool
     */
    public function send(Message $packet, int $action, int $fd) : bool
    {
        $this->server->send($packet, $action, $fd);
    }

    /**
     * @description event listen on server
     *
     * @param string $name
     *
     * @param callable $callable
     *
     * @return App
     */
    public function serverOn(string $event, callable | Array $callable) : App
    {
        $this->server->on($event, $callable);
        return $this;
    }

    /**
     * @description register other app object
     *
     * @param string $name
     *
     * @param mixed $app
     *
     * @return App
     */
    public function registerOtherApp(string $name, mixed $app) : App
    {
        $this->otherApps[$name] = $app;
        return $this;
    }

    /**
     * @description get other app object
     *
     * @param string $name
     *
     * @return mixed
     */
    public function getOtherApp($name) : mixed
    {
        return $this->otherApps[$name] ?? null;
    }

    /**
     * @description get server
     *
     * @return Server
     */
    public function getServer() : Server
    {
        return $this->server;
    }
}
