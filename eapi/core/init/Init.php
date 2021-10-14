<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : eapi
 *
 *                       文件 : Init.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/7
 *
 *                       更新 :
 *
 *                       说明 : 框架初始化
 *
 *********************************************************************************
 * Functions:
 *      run :   执行初始化
 *********************************************************************************/

namespace eapi;

class Init{

    /*****************************************************************************
     * run -- 执行初始化操作
     *
     *
     * 输入 : Nothing
     *
     * 输出 : @return bool
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function run(): bool
    {
        require 'welcome.php';
        if (is_file(_EAPI . '/runtime/init.lock'))
            return TRUE;
        // 创建相关目录
        $path = './work/' . conf('DEFAULT');
        if ( ! mkdir($path))
        {
            fail(1003, [
                'path' => $path
            ]);
        }
        // 批量创建目录
        $dir = [
            'conf',
            'middleware',
            'model',
            'router',
            'spi'
        ];
        foreach ($dir as $d)
            mkdir($path . '/' . $d);
        // 创建空文件防止直接访问
        file_put_contents($path.'/index.html', '');
        // 通用头部
        $header  = "<?PHP\r\n";
        $header .= "\r\n";
        $header .= "/**\r\n";
        $header .= " * ===============================================\r\n";
        $header .= " * eapi - 极速API开发框架\r\n";
        $header .= " * ===============================================\r\n";
        $header .= " * 版本：PHP8.0 +\r\n";
        $header .= " * 作者: \r\n";
        $header .= " * 日期: ".date('Y-m-d H:i')."\r\n";
        $header .= " * ===============================================\r\n";
        $header .= " * [msg]\r\n";
        $header .= " * ===============================================\r\n";
        $header .= " */\r\n\r\n";
        // 批量创建配置文件
        $conf = [
            'Conf.php',
            'Mcq.php',
            'Mongodb.php',
            'Mysql.php',
            'Redis.php',
            'Es.php',
            'Rmq.php'
        ];
        foreach ($conf as $file)
        {
            $headers = match ($file) {
                'Conf.php'      => str_replace('[msg]', '服务自定义配置文件', $header),
                'Mcq.php'       => str_replace('[msg]', 'MCQ配置文件', $header),
                'Mongodb.php'   => str_replace('[msg]', 'MONGODB配置文件', $header),
                'Mysql.php'     => str_replace('[msg]', 'MYSQL配置文件', $header),
                'Redis.php'     => str_replace('[msg]', 'REDIS配置文件', $header),
                'Es.php'        => str_replace('[msg]', 'ES配置文件', $header),
                'Rmq.php'       => str_replace('[msg]', 'RABBITMQ配置文件', $header)
            };
            $content = $headers."return [\r\n";
            switch ($file)
            {
                case 'Conf.php':
                    $content .= "    // 输入过滤\r\n";
                    $content .= "    'INPUTFILTER' => 'escape|xss',\r\n";
                    $content .= "    // 开启路由\r\n";
                    $content .= "    'ROUTER'      => FALSE,\r\n";
                    $content .= "    // 是否启用REST自动路由，启用后针对POST、PUT、DELETE可以实现自动路由，无需配置路由规则\r\n";
                    $content .= "    'REST_ROUTER' => FALSE,\r\n";
                    $content .= "    // REST类型路由操作别名\r\n";
                    $content .= "    'REST_ALIAS'  => [\r\n";
                    $content .= "       'POST'   => 'Add',\r\n";
                    $content .= "       'PUT'    => 'Edit',\r\n";
                    $content .= "       'DELETE' => 'Del'\r\n";
                    $content .= "    ],\r\n";
                    $content .= "    // 默认中间件\r\n";
                    $content .= "    'MIDDLEWARE' => [\r\n";
                    $content .= "        'BEFORE' => [],\r\n";
                    $content .= "        'AFTER'  => []\r\n";
                    $content .= "    ],\r\n";
                    $content .= "    // 默认缓存类型，支持 file、redis\r\n";
                    $content .= "    'CACHE'      => 'file',\r\n";
                    $content .= "    // 默认缓存时长，单位秒\r\n";
                    $content .= "    'CACHETIME'  => 600,\r\n";
                    $content .= "    // 当前服务版本\r\n";
                    $content .= "    'VERSION'    => '1.0.0',\r\n";
                    $content .= "    // HTTP请求最大超时时间\r\n";
                    $content .= "    'HTTP_TIMEOUT' => 3,\r\n";
                    $content .= "    // HTTP请求最大并发数\r\n";
                    $content .= "    'HTTP_MAXTHREAD' => 10";
                break;
                case 'Mcq.php':
                    $content .= "    // MCQ地址\r\n";
                    $content .= "    'HOST'    => '',\r\n";
                    $content .= "    // MCQ端口\r\n";
                    $content .= "    'PORT'    => 11212,\r\n";
                    $content .= "    // KEY统一前缀，没有可为空\r\n";
                    $content .= "    'PREFIX'  => '',\r\n";
                    $content .= "    // 连接超时时间\r\n";
                    $content .= "    'TIMEOUT' => 1";
                break;
                case 'Mongodb.php':
                    $content .= "    // MONGODB地址\r\n";
                    $content .= "    'HOST'     => '',\r\n";
                    $content .= "    // MONGODB账号\r\n";
                    $content .= "    'USERNAME' => '',\r\n";
                    $content .= "    // MONGODB密码\r\n";
                    $content .= "    'PASSWORD' => '',\r\n";
                    $content .= "    // 端口号\r\n";
                    $content .= "    'PORT'     => 27017,\r\n";
                    $content .= "    // 数据库名称\r\n";
                    $content .= "    'DATABASE' => '',\r\n";
                    $content .= "    // 数据表前缀，没有可为空\r\n";
                    $content .= "    'PREFIX'   => ''";
                break;
                case 'Mysql.php':
                    $content .= "    // 数据库地址\r\n";
                    $content .= "    'HOST'     => '',\r\n";
                    $content .= "    // 数据库账号\r\n";
                    $content .= "    'USERNAME' => '',\r\n";
                    $content .= "    // 数据库密码\r\n";
                    $content .= "    'PASSWORD' => '',\r\n";
                    $content .= "    // 数据库端口\r\n";
                    $content .= "    'PORT'     => 3306,\r\n";
                    $content .= "    // 字符集\r\n";
                    $content .= "    'CHARSET'  => 'utf-8',\r\n";
                    $content .= "    // 数据库名称\r\n";
                    $content .= "    'DATABASE' => '',\r\n";
                    $content .= "    // 数据表前缀，没有可为空\r\n";
                    $content .= "    'PREFIX'   => ''";
                break;
                case 'Redis.php':
                    $content .= "    // REDIS地址\r\n";
                    $content .= "    'HOST'     => '',\r\n";
                    $content .= "    // REDIS密码，如果没有可为空\r\n";
                    $content .= "    'PASSWORD' => '',\r\n";
                    $content .= "    // REDIS端口\r\n";
                    $content .= "    'PORT'     => 6379,\r\n";
                    $content .= "    // 连接超时时间\r\n";
                    $content .= "    'TIMEOUT'  => 3,\r\n";
                    $content .= "    // 库序号，0-15，默认0\r\n";
                    $content .= "    'DATABASE' => 0,\r\n";
                    $content .= "    // KEY统一前缀，没有可为空\r\n";
                    $content .= "    'PREFIX'   => ''";
                break;
                case 'Rmq.php':
                    $content .= "    // 队列地址\r\n";
                    $content .= "    'HOST'     => '',\r\n";
                    $content .= "    // 账号\r\n";
                    $content .= "    'USERNAME' => '',\r\n";
                    $content .= "    // 密码\r\n";
                    $content .= "    'PASSWORD' => '',\r\n";
                    $content .= "    // 端口\r\n";
                    $content .= "    'PORT'     => 5672,\r\n";
                    $content .= "    // 虚拟主机\r\n";
                    $content .= "    'VHOST'    => '/',\r\n";
                    $content .= "    // 默认交换机\r\n";
                    $content .= "    'EXCHANGE' => '',\r\n";
                    $content .= "    // 默认队列\r\n";
                    $content .= "    'QUEUE'    => '',\r\n";
                    $content .= "    // 默认队列可接收的任务数，最大不能超过65535，一般默认即可\r\n";
                    $content .= "    'QOS'      => 0";
                    break;
                case 'Es.php':
                    $content .= "    // 节点配置\r\n";
                    $content .= "    'HOSTS' => [\r\n";
                    $content .= "       // 可配置多个节点\r\n";
                    $content .= "       [\r\n";
                    $content .= "           // 地址\r\n";
                    $content .= "           'HOST'     => '',\r\n";
                    $content .= "           // 账号\r\n";
                    $content .= "           'USERNAME' => '',\r\n";
                    $content .= "           // 密码\r\n";
                    $content .= "           'PASSWORD' => '',\r\n";
                    $content .= "           // 端口\r\n";
                    $content .= "           'PORT'     => 9200\r\n";
                    $content .= "       ]\r\n";
                    $content .= "    ],\r\n";
                    $content .= "    // 重试次数，0为不重试\r\n";
                    $content .= "    'RETRY' => 2,\r\n";
                    $content .= "    // 连接节点超时时间\r\n";
                    $content .= "    'CONNECT_TIMEOUT' => 1,\r\n";
                    $content .= "    // 执行请求超时时间\r\n";
                    $content .= "    'TIMEOUT' => 1";
                break;
            }
            $content .= "\r\n];";
            file_put_contents($path.'/conf/'.$file, $content);
        }
        // 生成默认中间件文件
        $file     = 'Middleware.php';
        $headers  = str_replace('[msg]', '中间件', $header);
        $content  = $headers;
        $content .= "namespace middleware;\r\n";
        $content .= "\r\n";
        $content .= "class Middleware{\r\n";
        $content .= "\r\n";
        $content .= "}\r\n";
        file_put_contents($path.'/middleware/'.$file, $content);
        // 生成默认路由文件
        $file     = 'Router.php';
        $headers  = str_replace('[msg]', '路由', $header);
        $content  = $headers;
        file_put_contents($path.'/router/'.$file, $content);
        // 生成Init.php文件
        $file      = 'Init.php';
        $headers   = str_replace('[msg]', '初始化文件', $header);
        $content   = $headers;
        $content  .= "declare (strict_types = 1);\r\n";
        $content  .= "namespace spi;\r\n";
        $content  .= "\r\n";
        $content  .= "class Init{\r\n";
        $content  .= "\r\n";
        $content  .= "    public function __construct()\r\n";
        $content  .= "    {\r\n";
        $content  .= "        \r\n";
        $content  .= "    }\r\n";
        $content  .= "}\r\n";
        file_put_contents($path.'/spi/'.$file, $content);
        // 生成演示控制器
        $file      = 'Hello.php';
        $headers   = str_replace('[msg]', '演示控制器', $header);
        $content   = $headers;
        $content  .= "declare (strict_types = 1);\r\n\r\n";
        $content  .= "namespace spi;\r\n";
        $content  .= "\r\n";
        $content  .= "class Hello extends Init{\r\n";
        $content  .= "\r\n";
        $content  .= "    public function __construct()\r\n";
        $content  .= "    {\r\n";
        $content  .= "        parent::__construct();\r\n";
        $content  .= "    }\r\n\r\n";
        $content  .= "    public function index()\r\n";
        $content  .= "    {\r\n";
        $content  .= "        echo 'hello eapi!!';\r\n";
        $content  .= "    }\r\n\r\n";
        $content  .= "}\r\n";
        file_put_contents($path.'/spi/'.$file, $content);
        // 创建锁定文件
        file_put_contents(_EAPI.'/runtime/init.lock', '');
        return TRUE;
    }

}