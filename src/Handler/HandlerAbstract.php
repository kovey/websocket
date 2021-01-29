<?php
/**
 * @description handler
 *
 * @package Kovey\Websocket\Handler
 *
 * @author kovey
 *
 * @time 2019-11-14 22:58:02
 *
 */
namespace Kovey\Websocket\Handler;

abstract class HandlerAbstract
{
    /**
     * @description client ip
     *
     * @var string
     */
    protected string $clientIp;

    /**
     * @description set client ip
     *
     * @param string $clientIp
     *
     * @return void
     */
    public function setClientIp(string $clientIp) : void
    {
        $this->clientIp = $clientIp;
    }
}
