<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : Di.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/7
 *
 *                       更新 :
 *
 *                       说明 : 依赖注入容器
 *
 *********************************************************************************
 * Functions:
 *      run     :   执行容器
 *      set     :   为容器赋值
 *      get     :   从容器中取值
 *      build   :   构建容器
 *********************************************************************************/

namespace eapi;

use Closure;
use ReflectionClass;
use eapi\Controller as Controller;
use ReflectionException;

final class Di {

    private static array $service = [];

    /*****************************************************************************
     * run -- 执行容器并注册自动加载文件
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public static function run()
    {
        require _EAPI . '/core/loader/Autoload.php';
        // 注册自动加载
        spl_autoload_register('eapi\Autoload::run');
        // 载入vendor自动加载
        Autoload::vendor();
        // 重置容器
        self::$service = [];
        // 载入助手函数包
        require _EAPI . '/core/helper/Helper.php';
        // 鉴权
        Auth::run();
        // 如果过是CLI模式则停止
        if ( ! defined('_BIN'))
        {
            // 运行控制器
            $Controller = new Controller();
            $Controller->run();
        }
    }

    /*********************************************************************************
     * set -- 为容器设置值
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *********************************************************************************/
    public static function set($name, $value)
    {
        self::$service[$name] = $value;
    }

    /*********************************************************************************
     * get -- 从容器中获取值
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *********************************************************************************/
    public static function get($name)
    {
        // 从容器中获取实例，如果没有则创建实例并注册到容器中
        return self::$service[$name] ?? self::build($name);
    }

    /*********************************************************************************
     * build -- 构建容器
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *********************************************************************************/
    public static function build($name)
    {
        // 如果是匿名函数
        if ($name instanceof Closure)
        {
            // 执行闭包函数并将结果返回
            is_callable($name) || fail(1002, ['function' => $name]);
            return $name();
        }
        try {
            $reflector = new ReflectionClass($name);
            // 检查类是否可实例化
            if ( ! $reflector->isInstantiable()) fail(1007);
            // 获取类的构造函数
            $constructor = $reflector->getConstructor();
            // 若无构造函数，直接实例化并返回
            if (is_null($constructor))
            {
                $class = new $name;
                self::set($name, $class);
                return $class;
            }
            // 取构造函数参数,通过 ReflectionParameter 数组返回参数列表
            $params = $constructor->getParameters();
            if ( ! empty($params))
            {
                // 递归解析构造函数的参数
                $depend = self::_getDepend($params);
                // 创建一个类的新实例,给出的参数将传递到类的构造函数。
                $class  = $reflector->newInstanceArgs($depend);
            }
            else
            {
                $class = new $name();
            }
            self::set($name, $class);
            return $class;
        } catch (ReflectionException) {
        }
        return TRUE;
    }

    /*********************************************************************************
     * _getDepend -- 递归解析构造参数
     *
     *
     * 输入 : 1个
     * @param array $params
     *
     * 输出 : @return array
     *
     * 历史 :
     *     2021/10/7 : created
     *********************************************************************************/
    private static function _getDepend(array $params): array
    {
        $depend = [];
        foreach ($params as $val)
        {
            $class = $params[0]->getClass();
            // 是变量,有默认值则设置默认值
            // 是类则递归解析
            $depend[] = is_null($class)
                            ? self::_resolveNonClass($val)
                            : self::build($class->name);
        }
        return $depend;
    }

    /*********************************************************************************
     * _resolveNonClass -- 设置默认值
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *********************************************************************************/
    private static function _resolveNonClass($param)
    {
        // 有默认值则返回默认值
        if ($param->isDefaultValueAvailable())
            return $param->getDefaultValue();
        return TRUE;
    }

}