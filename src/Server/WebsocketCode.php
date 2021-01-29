<?php
/**
 * @description websocket error
 *
 * @package Websocket\Server
 *
 * @author kovey
 *
 * @time 2020-03-18 20:08:13
 *
 */
namespace Kovey\Websocket\Server;

class WebsocketCode
{
    /**
     * @description throw close connection exception when request
     *
     * @var int
     */
    const THROW_CLOSE_CONNECTION_EXCEPTION = 4000;

    /**
     * @description stream error
     *
     * @var int
     */
    const STREAM_ERROR = 4001;

    /**
     * @description unpack stream error
     *
     * @var int
     */
    const UNPACK_STREAM_ERROR = 4002;

    /**
     * @description protocol error
     *
     * @var int
     */
    const PROTOCOL_ERROR = 4003;

    /**
     * @description handler not found
     *
     * @var int
     */
    const NO_HANDLER = 4004;
}
