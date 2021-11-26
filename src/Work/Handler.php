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
use Kovey\Container\Keyword\Fields;

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
            $protobuf->mergeFromString($base->getPacket());
        }

        $class = $router->getHandler();
        $keywords = $this->container->getKeywords($class, $router->getMethod());
        try {
            $instance = $this->container->get($class, $event->getTraceId(), $event->getSpanId(), $keywords['ext']);
            if (!$instance instanceof HandlerAbstract) {
                throw new CloseConnectionException("$class is not implements HandlerAbstract");
            }

            $instance->setClientIp($event->getIp());

            if ($keywords[Fields::KEYWORD_OPEN_TRANSACTION]) {
                $keywords[Fields::KEYWORD_DATABASE]->beginTransaction();
                try {
                    $result = $this->triggerHandler($instance, $router->getMethod(), $protobuf, $event->getFd(), $base, $event);
                    $keywords[Fields::KEYWORD_DATABASE]->commit();
                } catch (\Throwable $e) {
                    $keywords[Fields::KEYWORD_DATABASE]->rollBack();
                    throw $e;
                }
            } else {
                $result = $this->triggerHandler($instance, $router->getMethod(), $protobuf, $event->getFd(), $base, $event);
            }

            if (empty($result)) {
                $result = array();
            }

            $result['class'] = $router->getHandler();
            $result['method'] = $router->getMethod();
            $result['params'] = $protobuf->serializeToJsonString();
            $result['req_action'] = $base->getAction();
            $result['base'] = $base->serializeToJsonString();

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

    private function triggerHandler(HandlerAbstract $instance, string $method, Message $message, int $fd, Message $base, Event\RunHandler $event) : Array
    {
        if ($this->event->listened('run_handler')) {
            return $this->event->dispatchWithReturn(new Event\RunHandler($instance, $method, $message, $fd, $base, $event->getTraceId(), $event->getSpanId()));
        }

        return call_user_func(array($instance, $method), $message, $fd, $base);
    }

    public function pack(Event\Pack $event) : string
    {
        $base = $this->routers->getBase();
        if (empty($base)) {
            return '';
        }

        $base = new $base;
        $base->setPacket($event->getPacket()->serializeToString())
            ->setAction($event->getAction());

        return $base->serializeToString();
    }
}
