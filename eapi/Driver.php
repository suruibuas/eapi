<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : eapi
 *
 *                       文件 : Driver.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/5
 *
 *                       更新 :
 *
 *                       说明 : eapi驱动文件，如无必要请勿修改此文件
 * 
 *********************************************************************************
 * Functions:
 *                                                                               
 *********************************************************************************/

// 运行时数据
use api\Log;

$GLOBALS['_RUNTIME'] = [
    // 记录程序开始运行时间
    'MICROTIME' => microtime(TRUE)
];
// 日志埋点数据
$GLOBALS['_LOGS']  = [];
// 配置信息
$GLOBALS['_CONF']  = [];
// 语言
$GLOBALS['_LANG']  = [];
// 模型
$GLOBALS['_MODEL'] = [];

// 载入系统常量
require ROOTPATH . '/eapi/conf/Define.php';
// 载入公用函数
require _WORKPATH . '/Global.php';

// 设置时区
date_default_timezone_set(_TIMEZONE);
// 开启报错信息
error_reporting(_DEBUG ? E_ALL : 0);
// 捕获系统异常
register_shutdown_function(function(){
    $error = error_get_last();
    // 如果不是致命异常则忽略
    if ( ! $error || $error['type'] > 4 || ! _LOG)
        return TRUE;
    // 记录运行时错误日志
    Log::add('RUNTIME_ERROR', [
        'TYPE' => $error['type'],
        'MSG'  => $error['message'],
        'FILE' => $error['file'],
        'LINE' => $error['line']
    ]);
    // 如果是致命错误，则终止程序继续运行
    if ($error['type'] == 1)
        fail(500, '系统发生致命错误');
    return TRUE;
});
// 载入IOC容器
require _EAPI . '/core/ioc/Di.php';
// 运行容器
eapi\Di::run();