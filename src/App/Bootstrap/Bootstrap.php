<?php
/**
 * @description bootstrap
 *
 * @package     App\Bootstrap
 *
 * @time        Tue Sep 24 09:00:10 2019
 *
 * @author      kovey
 */
namespace Kovey\Websocket\App\Bootstrap;

use Kovey\Process\Process;
use Kovey\Library\Config\Manager;
use Kovey\Logger\Logger;
use Kovey\Logger\Monitor;
use Kovey\Logger\Db;
use Kovey\Container\Container;
use Kovey\Websocket\App\App;
use Kovey\Websocket\Server\Server;
use Kovey\Process\UserProcess;

class Bootstrap
{
    /**
     * @description init logger
     *
     * @param App $app
     *
     * @return void
     */
    public function __initLogger(App $app) : void
    {
        ko_change_process_name(Manager::get('server.websocket.name') . ' websocket root');
        Logger::setLogPath(Manager::get('server.server.logger_dir'));
        Logger::setCategory(Manager::get('server.websocket.name'));
        Db::setLogDir(Manager::get('server.server.logger_dir'));
        Monitor::setLogDir(Manager::get('server.server.logger_dir'));
    }

    /**
     * @description init app
     *
     * @param App $app
     *
     * @return void
     */
    public function __initApp(App $app) : void
    {
        $app->registerServer(new Server($app->getConfig()['server']))
            ->registerContainer(new Container())
            ->registerUserProcess(new UserProcess($app->getConfig()['server']['worker_num']));
    }

    /**
     * @description init custom process
     *
     * @param App $app
     *
     * @return void
     */
    public function __initProcess(App $app) : void
    {
        $app->registerProcess('kovey_config', (new Process\Config())->setProcessName(Manager::get('server.websocket.name') . ' config'));
    }

    /**
     * @description init custom bootstrap
     *
     * @param App $app
     *
     * @return void
     */
    public function __initCustomBoot(App $app) : void
    {
        $bootstrap = $app->getConfig()['websocket']['boot'] ?? 'application/Bootstrap.php';
        $file = APPLICATION_PATH . '/' . $bootstrap;
        if (!is_file($file)) {
            return;
        }

        require_once $file;

        $app->registerCustomBootstrap(new \Bootstrap());
    }

    public function __initParseInject(App $app) : void
    {
        $app->registerLocalLibPath(APPLICATION_PATH . '/application');

        $handler = $app->getConfig()['websocket']['handler'];
        $app->getContainer()->parse(APPLICATION_PATH . '/application/' . $handler, $handler);
    }
}
