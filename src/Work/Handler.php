<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2021-04-12 10:14:34
 *
 */
namespace Kovey\Websocket\Work;

use Kovey\App\Components\Work;
use Kovey\Event\EventInterface;
use Kovey\Websocket\Handler\HandlerAbstract;
use Kovey\Connection\ManualCollectInterface;
use Google\Protobuf\Internal\Message;
use Kovey\Library\Exception\CloseConnectionException;
use Kovey\Websocket\App\Router\RouterInterface;
use Kovey\Websocket\App\Router\RoutersInterface;
use Kovey\Websocket\Event;

class Handler extends Work
{
    private RoutersInterface $routers;

    public function setRouters(RoutersInterface $routers) : Handler
    {
        $this->routers = $routers;
        return $this;
    }

    public function addRouter(int | string $code, RouterInterface $router) : Handler
    {
        $this->routers->addRouter($code, $router);
        return $this;
    }

    public function run(EventInterface $event) : Array
    {
        $base = $this->routers->getBase();
        if (empty($base)) {
            throw new CloseConnectionException('base message class not config', 1000);
        }
        $base = new $base();
        $base->mergeFromString($event->getPacket());
        $router = $this->routers->getRouter($base->getAction());
        if (empty($router)) {
            throw new CloseConnectionException('protocol number is error', 1001);
        }

        $class = $router->getProtobuf();
        $protobuf = new $class();

        if ($this->event->listened('encrypt')) {
            $message = $this->event->dispatchWithReturn(new Event\Encrypt($base));
            $protobuf->mergeFromString($message);
        } else {
            $protobuf->mergeFromString($base->getMessage());
        }

        $class = $router->getHandler();
        $keywords = $this->container->getKeywords($class, $router->getMethod());
        try {
            $instance = $this->container->get($class, $event->getTraceId(), $event->getSpanId(), $keywords['ext']);
            if (!$instance instanceof HandlerAbstract) {
                throw new CloseConnectionException("$class is not implements HandlerAbstract");
            }

            $instance->setClientIp($event->getIp());

            if ($keywords['openTransaction']) {
                $instance->database->beginTransaction();
                try {
                    $result = $this->triggerHandler($instance, $router->getMethod(), $protobuf, $event->getFd());
                    $instance->database->commit();
                } catch (\Throwable $e) {
                    $instance->database->rollBack();
                    throw $e;
                }
            } else {
                $result = $this->triggerHandler($instance, $router->getMethod(), $protobuf, $event->getFd());
            }

            if (empty($result)) {
                $result = array();
            }

            $result['class'] = $router->getHandler();
            $result['method'] = $router->getMethod();
            $result['params'] = $protobuf->serializeToJsonString();

            return $result;
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            foreach ($keywords as $value) {
                if (!$value instanceof ManualCollectInterface) {
                    continue;
                }

                $value->collect();
            }
        }
    }

    private function triggerHandler(HandlerAbstract $instance, string $method, Message $message, int $fd) : Array
    {
        if ($this->event->listened('run_handler')) {
            return $this->event->dispatchWithReturn(new Event\RunHandler($instance, $method, $message, $fd));
        }

        return call_user_func(array($instance, $method), $message, $fd);
    }

    public function pack(Event\Pack $event) : string
    {
        $base = $this->routers->getBase();
        if (empty($base)) {
            return '';
        }

        $base = new $base;
        $base->setMessage($event->getPacket())
            ->setAction($event->getAction());

        return $base->serializeToString();
    }
}
