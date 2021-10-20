<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : Map.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/7
 *
 *                       更新 :
 *
 *                       说明 : 文件映射关系
 *
 *********************************************************************************
 * Functions:
 *
 *********************************************************************************/

define('AUTOLOAD_MAP', 1);

return [
    'eapi\Facade'            => 'core/facade/Facade.php',
    'eapi\Conf'              => 'core/conf/Conf.php',
    'eapi\facade\Conf'       => 'core/facade/Conf.php',
    'eapi\Router'            => 'core/router/Router.php',
    'eapi\Model'             => 'core/model/Model.php',
    'eapi\facade\Model'      => 'core/facade/Model.php',
    'eapi\Http'              => 'core/http/Http.php',
    'eapi\facade\Http'       => 'core/facade/Http.php',
    'eapi\Log'               => 'core/log/Log.php',
    'eapi\facade\Log'        => 'core/facade/Log.php',
    'eapi\Middleware'        => 'core/middleware/Middleware.php',
    'eapi\facade\Middleware' => 'core/facade/Middleware.php',
    'eapi\Controller'        => 'core/controller/Controller.php',
    'eapi\Init'              => 'core/init/Init.php',
    'eapi\Cli'               => 'core/cli/Cli.php',
    'eapi\facade\Io'         => 'core/facade/Io.php',
    'eapi\lib\Io'            => 'lib/Io.class.php',
    'eapi\facade\Mysql'      => 'core/facade/Mysql.php',
    'eapi\lib\Mysql'         => 'lib/Mysql.class.php',
    'eapi\facade\Redis'      => 'core/facade/Redis.php',
    'eapi\lib\Redis'         => 'lib/Redis.class.php',
    'eapi\facade\Mcq'        => 'core/facade/Mcq.php',
    'eapi\lib\Mcq'           => 'lib/Mcq.class.php',
    'eapi\facade\Rmq'        => 'core/facade/Rmq.php',
    'eapi\lib\Rmq'           => 'lib/Rmq.class.php',
    'eapi\facade\Mongodb'    => 'core/facade/Mongodb.php',
    'eapi\lib\Mongodb'       => 'lib/Mongodb.class.php',
    'eapi\facade\Es'         => 'core/facade/Es.php',
    'eapi\lib\Es'            => 'lib/Es.class.php',
    'eapi\facade\Filter'     => 'core/facade/Filter.php',
    'eapi\lib\Filter'        => 'lib/Filter.class.php',
    'eapi\facade\Jweixin'    => 'core/facade/Jweixin.php',
    'eapi\lib\Jweixin'       => 'lib/Jweixin.class.php',
    'eapi\facade\Upload'     => 'core/facade/Upload.php',
    'eapi\lib\Upload'        => 'lib/Upload.class.php',
    'eapi\Cache'             => 'core/cache/Cache.php',
    'eapi\facade\Cache'      => 'core/facade/Cache.php',
    'eapi\Auth'              => 'core/auth/Auth.php'
];