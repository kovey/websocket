<?php
/**
 * @description
 *
 * @package
 *
 * @author kovey
 *
 * @time 2020-03-18 20:08:13
 *
 */
namespace Kovey\Websocket\Server;

class WebsocketCode
{
    const THROW_CLOSE_CONNECTION_EXCEPTION = 4000;

    const STREAM_ERROR = 4001;

    const UNPACK_STREAM_ERROR = 4002;

    const PROTOCOL_ERROR = 4003;

    const NO_HANDLER = 4004;
}
