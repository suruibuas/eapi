<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : eapi
 *
 *                       文件 : Auth.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/5
 *
 *                       更新 : 2021/10/5
 *
 *                       说明 : API鉴权类
 *
 *********************************************************************************
 * Functions:
 *      run : 执行鉴权
 *********************************************************************************/

namespace eapi;

use api\Log;
use Yac;

class Auth{

    /*****************************************************************************
     * run -- 执行API鉴权
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/5 : created
     *****************************************************************************/
    public static function run(): bool
    {
        // 定义请求模式，接口强请求模式校验通过该值来判断
        define('_METHOD', $_SERVER['REQUEST_METHOD'] ?? 'GET');
        // 忽略前端跨域的预请求
        if (_METHOD == 'OPTIONS')
        {
            http_response_code(204);
            return TRUE;
        }
        // 如果是cli模式（队列监听一般都是在cli中）则给相关参数默认值
        if (_PHPCLI)
        {
            $URI    = '队列';
            $header = [];
            define('_IP', '127.0.0.1');
        }
        else
        {
            // 过滤参数后的URI地址
            $URI = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);
            $arr = explode('/', trim($URI, '/'));
            // 排除pathinfo格式参数
            if (count($arr) > 3)
            {
                $arr = array_slice($arr, 0, 3);
                $URI = '/'.implode('/', $arr).'/';
            }
            $header = httpHeader();
            // 请求IP
            define('_IP', getIp());
        }
        $GLOBALS['_RUNTIME']['URI'] = $URI;
        // 保存header头参数
        Log::set('HEADER', $header);
        // 保存POST参数
        if (_METHOD == 'POST')
            Log::set('POST', $_POST);
        // 过滤伪静态参数
        unset($_GET['s']);
        // 保存GET参数
        Log::set('GET', $_GET);
        /**
         * 三种情况不需要校验
         * 1、cli模式下
         * 2、关闭了校验开关
         * 3、在校验白名单中
         */
        if (_PHPCLI || ! _AUTH || in_array($URI, _AUTH_WHITE))
            return TRUE;
        // 客户端标识列表
        $client = [
            'IOS',
            'ANDROID',
            'WEB',
            'H5',
            'MICRO',
	        'NODEJS'
        ];
        // 没有指定平台标识
        if ( ! isset($header['CLIENT']) ||
            ! in_array($header['CLIENT'], $client))
        {
            fail(10001, '客户端类型不合法');
        }
        if ( ! isset($header['NONCE']))
        {
            fail(10002, 'NONCE参数不合法');
        }
        $nonce = $header['NONCE'];
        if ( ! isset($header['CURTIME']))
        {
            fail(10003, 'CURTIME参数不合法');
        }
        $curtime = $header['CURTIME'];
        if ( ! isset($header['OPENKEY']))
        {
            fail(10004, 'OPENKEY参数不合法');
        }
        $openkey = $header['OPENKEY'];
        if ($openkey != md5($nonce.$curtime._AUTHSECRET))
        {
            fail(10005, '鉴权失败');
        }
        // 判断是否是使用过的openkey
        $Yac = new Yac();
        if ($Yac->get($openkey) !== FALSE)
        {
            fail(10005, 'OPENKEY已过期');
        }
        else
        {
            $Yac->set($openkey, 1);
        }
        return TRUE;
    }

}