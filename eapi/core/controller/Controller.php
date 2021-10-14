<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : eapi
 *
 *                       文件 : Controller.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/6
 *
 *                       更新 :
 *
 *                       说明 : 控制器基类
 *
 *********************************************************************************
 * Functions:
 *      run :   执行控制器
 *********************************************************************************/

namespace eapi;

use eapi\Init as Init;
use eapi\Middleware as Middleware;
use eapi\Cli as Cli;
use api\Conf;
use ReflectionClass;
use ReflectionException;

class Controller{

    // 当前URL
    protected string $_url;
    // URL拆分的数组
    protected array $_arr;

    /*****************************************************************************
     * run -- 执行控制器
     *
     *
     * 输入 : Nothing
     *
     * 输出 : @return bool
     *
     * 历史 :
     *     2021/10/6 : created
     *****************************************************************************/
    public function run(): bool
    {
        // CLI模式
        if (_PHPCLI)
        {
            global $argv;
            if ( ! isset($argv[1]))
                fail(101, '请指定访问的服务，例如：/demo/demo/');
            // 系统内置命令
            if (in_array($argv[1], ['model', 'work']))
            {
                // 根据参数定义项目
                define('_APP', $argv[2] ?? conf('DEFAULT'));
                $Cli = new Cli();
                switch ($argv[1])
                {
                    // 创建模型文件
                    case 'model':
                        $Cli->createModel();
                        return TRUE;
                }
            }
            $this->_url = $argv[1];
        }
        else
        {
            // URL地址
            $this->_url = $_SERVER['REQUEST_URI'];
        }
        preg_match('/(.+)\/index.php(.*)/', $_SERVER['PHP_SELF'], $match);
        // 项目不在根目录则过滤
        if ( ! empty($match))
            $this->_url = str_replace($match[1], '', $this->_url);
        // 执行框架初始化
        if ($this->_url == '/')
        {
            $Init = new Init();
            $Init->run();
            return TRUE;
        }
        // 过滤QUERY参数
        $this->_url = preg_replace('/\?.*/', '', $this->_url);
        // 解析应用目录
        $this->_arr = explode('/', trim($this->_url, '/'));
        // 如果没有写项目，则用默认项目补全
        if (count($this->_arr) < 3)
            array_unshift($this->_arr, conf('DEFAULT'));
        /**
         * 如果第一位不在允许的项目配置中，则用默认项目补全
         * 这种情况会出现在省略项目名称，但是又有pathinfo参数的情况下
         * 需要手动补全
         */
        if ( ! in_array($this->_arr[0], conf('ALLOW')))
            array_unshift($this->_arr, conf('DEFAULT'));
        // 检查有没有pathinfo参数
        if (count($this->_arr) > 3)
        {
            // 设置pathinfo参数
            $pathinfo = array_slice($this->_arr, 3, count($this->_arr));
            // 格式化pathinfo到$_GET中
            foreach ($pathinfo as $k => $v)
            {
                if ($k % 2 != 0) continue;
                $_GET[$v] = $pathinfo[$k + 1];
            }
            $this->_arr = array_slice($this->_arr, 0, 3);
            $this->_url = implode('/', $this->_arr);
        }
        // 定义项目目录
        define('_APP', $this->_arr[0]);
        // 加载项目内配置文件，覆盖通用配置文件内容
        $appConfFile = _WORKPATH . '/' . _APP . '/conf/Conf.php';
        if ( ! is_file($appConfFile))
        {
            fail(1016, [
                'config' => $appConfFile
            ]);
        }
        // 合并项目内配置覆盖全局Conf.php的配置
        Conf::merge(require $appConfFile);
        // 没有开启路由则直接走pathinfo模式解析
        if ( ! conf('ROUTER'))
        {
            $this->_pathinfo();
        }
        else
        {
            // 注册路由
            require _WORKPATH . '/' . _APP . '/router/Router.php';
            // 执行路由匹配
            $router = Router::run($this->_url, end($this->_arr));
            // 执行控制器
            $this->_do($router['namespace'], $router['action']);
            // 释放路由资源
            Router::release();
        }
        return TRUE;
    }

    /*****************************************************************************
     * _pathinfo -- 默认模式，根据路径解析控制器
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    private function _pathinfo()
    {
        // 执行控制器
        $action = array_pop($this->_arr);
        define('_ACTION', $action);
        $count  = count($this->_arr);
        // 如果只有2级说明控制器直接放在spi目录下，无需处理
        if ($count == 2)
        {
            $spi = ucfirst(array_pop($this->_arr));
        }
        else
        {
            $spi = '';
            $doc = '';
            foreach ($this->_arr as $k => $v)
            {
                if ($k == 0) continue;
                $spi .= $doc;
                $spi .= ($k + 1 == $count) ? ucfirst($v) : $v;
                $doc  = '\\';
            }
        }
        // 组合完整命名空间并执行
        $this->_do('spi\\' . $spi, $action);
    }

    /*****************************************************************************
     * _do -- 执行控制器
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    private function _do(string $namespace, string $action): void
    {
        // 处理REST自动路由
        $action = $this->_restRouter($action);
        define('_ACTION', $action);
        // 检测默认中间件
        $middleware = conf('MIDDLEWARE');
        if ( ! empty($middleware))
        {
            if (isset($middleware['BEFORE']))
                Middleware::default($middleware['BEFORE'], 1);
            if (isset($middleware['AFTER']))
                Middleware::default($middleware['AFTER'], 0);
        }
        // 获取控制器类的实例化
        try {
            $class = new ReflectionClass($namespace);
            // 检测控制器类中是否有指定的方法
            if ( ! $class->hasMethod($action))
            {
                fail(404, [
                    'action' => $action
                ]);
            }
            // 获取构造函数，优先级高于函数
            $construct = $class->getConstructor();
            if ($construct != FALSE)
                self::_getMiddleWareByDoc($construct->getDocComment());
            // 获取具体执行的方法
            $do = $class->getMethod($action);
            // 获取函数中间件
            self::_getMiddleWareByDoc($do->getDocComment());
            // 执行前置中间件
            $return = Middleware::runBefore($this->_url, $class, $namespace);
            if (is_bool($return))
            {
                if ($return) return;
                else
                {
                    // 没有前置中间件
                    $instance = $class->newInstance();
                    // 获取控制器返回值
                    $return   = $do->invoke($instance);
                }
            }
            elseif (is_array($return))
            {
                $class  = $return['class'];
                $body   = $return['return'];
                $return = $class->$action($body);
            }
            // 执行后置中间件
            if (is_array($return))
                Middleware::runAfter($this->_url, $return);
            else
                Middleware::runAfter($this->_url);
        } catch (ReflectionException $e) {
            fail(500, $e);
        }
    }

    /*********************************************************************************
     * _getMiddleWareByDoc -- 从注释中提取中间件
     *
     *
     * 输入 : 1个
     * @param string|bool $doc   注释内容
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *********************************************************************************/
    private function _getMiddleWareByDoc(string|bool $doc): void
    {
        if ($doc === FALSE) return;
        preg_match_all('#@([befor|at]+) : (.*)#', $doc, $data);
        if ( ! empty($data[1]))
        {
            foreach ($data[1] as $k => $v)
                Middleware::$v($data[2][$k]);
        }
    }

    /*****************************************************************************
     * _restRouter -- 处理REST路由
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    private function _restRouter(string $action): string
    {
        if ( ! conf('REST_ROUTER') || _METHOD == 'GET')
            return $action;
        // 检测别名
        $alias = conf('REST_ALIAS');
        if ( ! is_array($alias) || ! isset($alias[_METHOD]))
            return $action.ucfirst(strtolower(_METHOD));
        return $action.$alias[_METHOD];
    }

}