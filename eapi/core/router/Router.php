<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : Router.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/7
 *
 *                       更新 :
 *
 *                       说明 : 路由类
 *
 *********************************************************************************
 * Functions:
 *      group   :   分组路由
 *      set     :   精准路由
 *      run     :   路由匹配
 *      release :   释放路由资源
 *********************************************************************************/

namespace eapi;

class Router{

    private static array    $_map       = [];
    private static array    $_key       = [];
    // 用于多次设置的中间件匹配使用
    private static int      $_mapNum    = 1;
    private static int      $_keyNum    = 1;
    private static array    $_mapMiddle = [];
    private static array    $_keyMiddle = [];

    /*****************************************************************************
     * group -- 分组创建路由
     *
     *
     * 输入 : 3个
     * @param array $preg       路由规则
     * @param string $route     路由目的地
     * @param array $middleware 中间件
     *
     * 输出 : @return bool
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public static function group(array $preg, string $route, array $middleware = []): bool
    {
        if (empty($preg) || ! $route) return FALSE;
        foreach ($preg as $v) self::$_map[self::$_mapNum][$v]['router'] = $route;
        // 注册中间件
        if ( ! empty($middleware)) self::$_mapMiddle[self::$_mapNum] = $middleware;
        self::$_mapNum += 1;
        return TRUE;
    }

    /*****************************************************************************
     * set -- 精准路由
     *
     *
     * 输入 : 2个
     * @param array $key
     * @param array $middleware
     *
     * 输出 : @return bool
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public static function set(array $key, array $middleware = []): bool
    {
        if (empty($key)) return FALSE;
        self::$_key[self::$_keyNum] = $key;
        // 注册中间件
        if ( ! empty($middleware)) self::$_keyMiddle[self::$_keyNum] = $middleware;
        self::$_keyNum += 1;
        return TRUE;
    }

    /*****************************************************************************
     * run -- 执行路由匹配
     *
     *
     * 输入 : 2个
     * @param string $url
     * @param string $action
     *
     * 输出 : @return array
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public static function run(string $url, string $action): array
    {
        $result = [];
        // 进行KEY值快速匹配
        if ( ! empty(self::$_key))
        {
            foreach (self::$_key as $key => $route)
            {
                if ( ! isset($route[$action])) continue;
                $arr = explode('/', $route[$action]);
                $action = array_pop($arr);
                $result['router'] = implode('/', $arr);
                // 如果有中间件则注册
                if ( ! isset(self::$_keyMiddle[$key])) break;
                $middleware = self::$_keyMiddle[$key];
                if (isset($middleware['BEFORE']))
                    Middleware::before($middleware['BEFORE']);
                if (isset($middleware['AFTER']))
                    Middleware::after($middleware['AFTER']);
                break;
            }
        }
        if (empty($result) && ! empty(self::$_map))
        {
            $url = preg_replace('#/'._APP.'/#', '', $url);
            // 进行分组匹配
            foreach (self::$_map as $key => $route)
            {
                if ( ! empty($result)) break;
                foreach ($route as $preg => $row)
                {
                    if ( ! preg_match('#/?'.$preg.'/?#i', $url, $data)) continue;
                    if ( ! isset($data[1])) fail(1009);
                    $result = $row;
                    // 修正action参数
                    $action = rtrim($data[1], '/');
                    if (strpos($action, '/') != FALSE)
                        $action = substr($action, (strripos($action, '/') + 1), strlen($action));
                    // 如果有中间件则注册
                    if ( ! isset(self::$_mapMiddle[$key])) break;
                    $middleware = self::$_mapMiddle[$key];
                    if (isset($middleware['BEFORE']))
                        Middleware::before($middleware['BEFORE']);
                    if (isset($middleware['AFTER']))
                        Middleware::after($middleware['AFTER']);
                    break;
                }
            }
        }
        if (empty($result))
        {
            fail(404, [
                'action' => $action
            ]);
        }
        // 组装命名空间
        $namespace = 'spi\\'.str_replace('/', '\\', $result['router']);
        return [
            'namespace' => $namespace,
            'action'    => $action
        ];
    }

    /*****************************************************************************
     * release -- 释放路由资源
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public static function release()
    {
        self::$_map = [];
        self::$_key = [];
        self::$_mapMiddle = [];
        self::$_keyMiddle = [];
    }

}