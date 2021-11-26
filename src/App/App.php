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

use Kovey\Websocket\Server\Server;
use Kovey\Websocket\Event;
use Kovey\App\App as AA;
use Kovey\Library\Exception\KoveyException;
use Kovey\App\Components\ServerInterface;
use Kovey\Websocket\Work\Handler;
use Kovey\Websocket\App\Router\RouterInterface;
use Kovey\Websocket\App\Router\RoutersInterface;
use Kovey\Websocket\App\Bootstrap;
use Google\Protobuf\Internal\Message;

class App extends AA
{
    /**
     * @description App instance
     *
     * @var App
     */
    private static ?App $instance;

    /**
     * @description other app object
     *
     * @var Array
     */
    private Array $otherApps;

    /**
     * @description get app instance
     *
     * @return App
     */
    public static function getInstance(Array $config = array()) : App
    {
        if (empty(self::$instance)) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    protected function init() : App
    {
        $this->bootstrap
             ->add(new Bootstrap\BaseInit())
             ->add(new Bootstrap\RouterInit());

        $this->event->addSupportEvents(array(
            'run_handler' => Event\RunHandler::class,
            'error' => Event\Error::class,
            'encrypt' => Event\Encrypt::class,
        ));

        return $this;
    }

    protected function initWork() : App
    {
        $this->work = new Handler();
        $this->work->setEventManager($this->event);
        return $this;
    }

    /**
     * @description register server
     *
     * @param Server $server
     *
     * @return App
     */
    public function registerServer(ServerInterface $server) : App
    {
        $this->server = $server;
        $this->server
            ->on('handler', array($this->work, 'run'))
            ->on('console', array($this, 'console'))
            ->on('initPool', array($this, 'initPool'))
            ->on('monitor', array($this, 'monitor'));
        if (isset($this->config['auto_pack']) && $this->config['auto_pack'] == 'On') {
            $this->server->on('pack', array($this->work, 'pack'));
        }

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
     * @description send data to client
     *
     * @param Message $packet
     *
     * @param int $fd
     *
     * @return bool
     */
    public function send(Message $packet, int | string $action, int $fd, Array $ext = array()) : bool
    {
        return $this->server->send($packet, $action, $fd, $ext);
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
     * @description register router
     *
     * @param string | int $code
     *
     * @param RouterInterface $router
     *
     * @return App
     */
    public function registerRouter(string | int $code, RouterInterface $router) : App
    {
        $this->work->addRouter($code, $router);
        return $this;
    }

    /**
     * @description register routers
     *
     * @param RoutersInterface $routers
     *
     * @return Application
     */
    public function registerRouters(RoutersInterface $routers) : App
    {
        $this->work->setRouters($routers);
        return $this;
    }
}
