<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : Autoload.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/7
 *
 *                       更新 :
 *
 *                       说明 : 自动载入文件
 *
 *********************************************************************************
 * Functions:
 *      run :   执行自动加载
 *********************************************************************************/

namespace eapi;

final class Autoload{

    private static array $map = [];
    // 定义别名
    private static array $aliases = [
        'Router'        => 'eapi\Router',
        'api\Conf'      => 'eapi\facade\Conf',
        'api\Rpc'       => 'eapi\facade\Rpc',
        'api\Log'       => 'eapi\facade\Log',
        'api\Http'      => 'eapi\facade\Http',
        'api\Io'        => 'eapi\facade\Io',
        'api\Mysql'     => 'eapi\facade\Mysql',
        'api\Redis'     => 'eapi\facade\Redis',
        'api\Mcq'       => 'eapi\facade\Mcq',
        'api\Rmq'       => 'eapi\facade\Rmq',
        'api\Mongodb'   => 'eapi\facade\Mongodb',
        'api\Cache'     => 'eapi\facade\Cache',
        'api\Es'        => 'eapi\facade\Es',
        'api\Upload'    => 'eapi\facade\Upload',
        'api\Filter'    => 'eapi\facade\Filter',
        'api\Jweixin'   => 'eapi\facade\Jweixin'
    ];

    /*****************************************************************************
     * run -- 自动加载
     *
     *
     * 输入 : 1个
     * @param string $class
     *
     * 输出 : @return bool
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public static function run(string $class): bool
    {
        // 检测是否是别名
        if (isset(self::$aliases[$class]))
        {
            class_alias(self::$aliases[$class], $class);
            return TRUE;
        }
        // 载入文件映射关系
        if ( ! defined('AUTOLOAD_MAP'))
            self::$map = require 'Map.php';
        // 获取根空间
        $root = str_contains($class, '\\') ? strstr($class, '\\', TRUE) : $class;
        // 加载对应文件
        $root == 'eapi'
            ? self::_loadFm($class)
            : self::_loadWork($class);
        return TRUE;
    }

    /*****************************************************************************
     * vendor -- 载入vendor自动加载文件
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public static function vendor()
    {
        // 载入VENDOR自动载入脚本文件
        if (is_file(_EAPI . '/vendor/autoload.php'))
            require _EAPI . '/vendor/autoload.php';
    }

    /*****************************************************************************
     * _loadFm -- 加载框架文件
     *
     *
     * 输入 : 1个
     * @param string $class
     *
     * 输出 : @return void
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    private static function _loadFm(string $class): void
    {
        // 根据映射载入
        if (isset(self::$map[$class]))
            $realFile = _EAPI . '/' . self::$map[$class];
        else
            $realFile = str_replace('\\', DIRECTORY_SEPARATOR, $class).'.php';
        if (is_file($realFile))
        {
            require $realFile;
            return;
        }
        fail(1001, [
            'file' => $realFile,
            'tip'  => '该文件并没有系统映射，请检查书写是否正确'
        ]);
    }

    /*****************************************************************************
     * _loadWork -- 加载应用文件
     *
     *
     * 输入 : 1个
     * @param string $class
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    private static function _loadWork(string $class)
    {
        $class = str_replace('\\', DIRECTORY_SEPARATOR, $class);
        // 设置完整路径
        $realFile = _WORKPATH.DIRECTORY_SEPARATOR._APP.DIRECTORY_SEPARATOR.$class.'.php';
        if (is_file($realFile))
        {
            require $realFile;
            return;
        }
        fail(1001, [
            'file' => $realFile,
            'tip'  => '文件不存在，请检查'
        ]);
    }

}