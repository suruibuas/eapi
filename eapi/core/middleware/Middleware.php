<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : Middleware.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/7
 *
 *                       更新 :
 *
 *                       说明 : 中间件操作类
 *
 *********************************************************************************
 * Functions:
 *      before      :   注册前置中间件
 *      after       :   注册后置中间件
 *      default     :   默认中间件
 *      runBefore   :   执行前置中间件
 *      runAfter    :   执行后置中间件
 *********************************************************************************/

namespace eapi;

class Middleware{

    // 前置中间件
    private static array $_before = [];
    // 后置中间件
    private static array $_after  = [];
    // 参数
    private static array|null $_args = null;
    // 中间件返回值
    private static array $_return = [];
    // 控制器的实例
    private static object $_class;
    // 项目中间件
    private static object|null $_middleware = null;

    /*****************************************************************************
     * before -- 注册前置中间件
     *
     *
     * 输入 : 1个
     * @param array|string $name    中间件
     *
     * 输出 : @return bool
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public static function before(array|string $name): bool
    {
        if (empty($name)) return FALSE;
        if (is_array($name))
            self::$_before = array_merge(self::$_before, $name);
        else
            array_push(self::$_before, $name);
        return TRUE;
    }

    /*****************************************************************************
     * after -- 注册后置中间件
     *
     *
     * 输入 : 1个
     * @param array|string $name    中间件
     *
     * 输出 : @return bool
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public static function after(array|string $name): bool
    {
        if (empty($name)) return FALSE;
        if (is_array($name))
            self::$_after = array_merge(self::$_after, $name);
        else
            array_push(self::$_after, $name);
        return TRUE;
    }

    /*****************************************************************************
     * default -- 默认中间件
     *
     *
     * 输入 : 2个
     * @param array $name
     * @param int $type
     *
     * 输出 : @return bool
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public static function default(array $name, int $type): bool
    {
        if (empty($name)) return FALSE;
        // 前置中间件反转
        $name = array_reverse($name);
        foreach ($name as $middleware)
        {
            $type == 1
                ? array_unshift(self::$_before, $middleware)
                : array_unshift(self::$_after, $middleware);
        }
        return TRUE;
    }

    /*****************************************************************************
     * runBefore -- 执行前置中间件
     *
     *
     * 输入 : 3个
     * @param string $url
     * @param $class
     * @param $namespace
     *
     * 输出 : @return bool|array
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public static function runBefore(string $url, $class, $namespace): bool|array
    {
        if (empty(self::$_before)) return FALSE;
        // 实例化控制器
        self::$_class = new $namespace();
        // 实例化中间件
        self::$_middleware = new \middleware\Middleware();
        // 提取需要排除的配置
        $exclude = [];
        if (isset(self::$_before['_exclude']))
        {
            $exclude = self::$_before['_exclude'];
            unset(self::$_before['_exclude']);
        }
        // 遍历并执行前置
        foreach (self::$_before as $name)
        {
            if (in_array(_ACTION, $exclude)) continue;
            $name = trim($name);
            if (self::_args($name, $class))
                $name = str_replace('(...)', '', $name);
            if ( ! method_exists(self::$_middleware, $name))
            {
                fail(1006, [
                    'middleware' => $name
                ]);
            }
            $return = self::$_middleware->$name(md5($url), is_null(self::$_args) ? [] : self::$_args);
            if ($return === TRUE)
                return $return;
            elseif (is_array($return))
                self::$_return = array_merge(self::$_return, $return);
        }
        return [
            'class'  => self::$_class,
            'return' => self::$_return
        ];
    }

    /*****************************************************************************
     * runAfter -- 执行后置中间件
     *
     *
     * 输入 : 2个
     * @param string $url
     * @param array $args
     *
     * 输出 : @return bool
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public static function runAfter(string $url, array $args = []): bool
    {
        if (empty(self::$_after)) return FALSE;
        self::$_after = array_reverse(self::$_after);
        // 实例化中间件
        if (is_null(self::$_middleware))
            self::$_middleware = new \middleware\Middleware();
        // 提取需要排除的配置
        $exclude = [];
        if (isset(self::$_after['_exclude']))
        {
            $exclude = self::$_after['_exclude'];
            unset(self::$_after['_exclude']);
        }
        foreach (self::$_after as $name)
        {
            if (in_array(_ACTION, $exclude)) continue;
            $name = trim($name);
            if (str_contains($name, '...'))
                $name = str_replace('(...)', '', $name);
            if ( ! method_exists(self::$_middleware, $name))
            {
                fail(1006, [
                    'middleware' => $name
                ]);
            }
            $return = self::$_middleware->$name(md5($url), is_null(self::$_args) ? [] : self::$_args, $args);
            if ($return === TRUE) return $return;
        }
        // 释放资源
        self::$_before = [];
        self::$_after  = [];
        self::$_args   = null;
        return TRUE;
    }

    /*****************************************************************************
     * _args -- 检查中间件参数
     *
     *
     * 输入 : Nothing
     *
     * 输出 : @return bool
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    private static function _args(string $name, $class): bool
    {
        if ( ! str_contains($name, '...')) return FALSE;
        if ( ! is_null(self::$_args)) return TRUE;
        $args = $class->getProperties();
        self::$_args = [];
        if (empty($args)) return TRUE;
        foreach ($args as $arg)
        {
            $fieldName = $arg->getName();
            $field = $class->getProperty($fieldName);
            if ( ! $field->isPublic()) $field->setAccessible(TRUE);
            self::$_args[$fieldName] = $field->getValue(self::$_class);
        }
        return TRUE;
    }

}