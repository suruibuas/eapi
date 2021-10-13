<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : eapi
 *
 *                       文件 : Conf.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/6
 *
 *                       更新 :
 *
 *                       说明 : 配置文件类
 *
 *********************************************************************************
 * Functions:
 *      get     :   读取配置
 *      set     :   设置配置
 *      merge   :   合并配置
 *********************************************************************************/

namespace eapi;

class Conf{

    public function __construct()
    {
        if ( ! $GLOBALS['_CONF'])
            $GLOBALS['_CONF'] = require _EAPI . '/conf/Conf.php';
    }

    /*****************************************************************************
     * get -- 获取配置信息
     *
     *
     * 输入 : 1个
     * @param string $key   配置KEY
     *
     * 输出 : @return mixed
     *
     * 历史 :
     *     2021/10/6 : created
     *****************************************************************************/
    public function get(string $key = ''): mixed
    {
        // 不设置KEY则返回所有配置
        if ($key == '') return $GLOBALS['_CONF'];
        // 如果已经存在则直接返回
        if (isset($GLOBALS['_CONF'][$key])) return $GLOBALS['_CONF'][$key];
        // 本地配置文件中读取
        $file = './work/' . _APP . '/conf/' . (ucfirst(strtolower($key))) . '.php';
        if (is_file($file))
        {
            $conf = require $file;
            $GLOBALS['_CONF'][strtoupper($key)] = $conf;
            return $conf;
        }
        return FALSE;
    }

    /*****************************************************************************
     * set -- 设置配置信息
     *
     *
     * 输入 : 2个
     * @param string $key
     * @param mixed $val
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/6 : created
     *****************************************************************************/
    public function set(string $key, mixed $val): void
    {
        $GLOBALS['_CONF'][strtoupper($key)] = $val;
    }

    /*****************************************************************************
     * merge -- 合并配置信息
     *
     *
     * 输入 : 1个
     * @param array $conf
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/6 : created
     *****************************************************************************/
    public function merge(array $conf)
    {
        if ( ! empty($conf))
            $GLOBALS['_CONF'] = array_merge($GLOBALS['_CONF'], $conf);
    }

}