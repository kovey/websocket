<?php
/**
 *
 * @description 整个运用启动前的初始化
 *
 * @package     App\Bootstrap
 *
 * @time        Tue Sep 24 09:00:10 2019
 *
 * @author      kovey
 */
namespace Kovey\Websocket\App\Bootstrap;

use Kovey\Library\Process;
use Kovey\Library\Config\Manager;
use Kovey\Logger\Logger;
use Kovey\Logger\Monitor;
use Kovey\Logger\Db;
use Kovey\Library\Container\Container;
use Kovey\Websocket\App\App;
use Kovey\Websocket\Server\Server;
use Kovey\Library\Process\UserProcess;

class Bootstrap
{
    /**
     * @description 初始化日志
     *
     * @param App $app
     *
     * @return null
     */
    public function __initLogger(App $app)
    {
        ko_change_process_name(Manager::get('server.websocket.name') . ' websocket root');
        Logger::setLogPath(Manager::get('server.logger.info'), Manager::get('server.logger.exception'), Manager::get('server.logger.error'), Manager::get('server.logger.warning'));
        Logger::setCategory(Manager::get('server.websocket.name'));
        Db::setLogDir(Manager::get('server.logger.db'));
        Monitor::setLogDir(Manager::get('server.logger.monitor'));
    }

    /**
     * @description 初始化APP
     *
     * @param App $app
     *
     * @return null
     */
    public function __initApp(App $app)
    {
        $app->registerServer(new Server($app->getConfig()['server']))
            ->registerContainer(new Container())
            ->registerUserProcess(new UserProcess($app->getConfig()['server']['worker_num']));
    }

    /**
     * @description 初始化自定义进程
     *
     * @param App $app
     *
     * @return null
     */
    public function __initProcess(App $app)
    {
        $app->registerProcess('kovey_config', (new Process\Config())->setProcessName(Manager::get('server.websocket.name') . ' config'));
    }

    /**
     * @description 初始化自定义的Bootsrap
     *
     * @param App $app
     *
     * @return null
     */
    public function __initCustomBoot(App $app)
    {
        $bootstrap = $app->getConfig()['websocket']['boot'] ?? 'application/Bootstrap.php';
        $file = APPLICATION_PATH . '/' . $bootstrap;
        if (!is_file($file)) {
            return;
        }

        require_once $file;

        $app->registerCustomBootstrap(new \Bootstrap());
    }
}
