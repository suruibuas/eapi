<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : eapi
 *
 *                       文件 : Cache.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/7
 *
 *                       更新 :
 *
 *                       说明 : 外观文件处理器
 *
 *********************************************************************************
 * Functions:
 *
 *********************************************************************************/

namespace eapi;

class Facade{

    /*****************************************************************************
     * getInstance -- 获取实例
     *
     *
     * 输入 : 1个
     * @param mixed $class    类名称
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public static function getInstance(mixed $class): mixed
    {
        return Di::get($class);
    }

    /*****************************************************************************
     * getFacadeAccessor -- 作为子类重载传值的入口
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public static function getFacadeAccessor(): string
    {
        return '';
    }

    /*****************************************************************************
     * __callstatic -- 返回静态方法
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public static function __callstatic($method, $args)
    {
        $instance = static::getInstance(static::getFacadeAccessor());
        $handler  = [$instance, $method];
        if (is_callable($handler))
        {
            return call_user_func_array($handler, $args);
        }
        else
        {
            fail(1008, [
                'function' => $method
            ]);
        }
        return TRUE;
    }

}