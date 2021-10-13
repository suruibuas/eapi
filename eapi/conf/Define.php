<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : eapi
 *
 *                       文件 : Define.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/5
 *
 *                       更新 :
 *
 *                       说明 : 系统预定义常量文件，系统运行时所需要的常量均在此定义
 *
 *********************************************************************************
 * Functions:
 *
 *********************************************************************************/

// 框架版本
const _VERSION    = '2.0.1';

// 框架根目录
const _EAPI       = ROOTPATH . '/eapi';

// 调试模式
const _DEBUG      = TRUE;

// 是否开启日志
const _LOG        = TRUE;

// 是否保存日志到本地
const _SAVELOG    = TRUE;

// 是否推送日志到控制台
const _PUSHLOG    = TRUE;

// 是否开启鉴权
const _AUTH       = FALSE;

/**
 * 鉴权白名单，在这里配置访问路由，则不会经过AUTH验证
 */
const _AUTH_WHITE = [

];

// 字符串加密KEY
const _STRKEY     = '927a0c84698179fdcd132e539d31e050';

// 鉴权私钥
const _AUTHSECRET = 'f140edaa5cf5ae4d20b6d0d5957f1199';

// 工作目录名称
const _WORKPATH   = ROOTPATH . '/work';

// 语言包目录
const _LANG       = _EAPI . '/lang';

// RUNTIME目录
const _RUNTIME    = _EAPI . '/runtime';

// 缓存目录
const _CACHE      = _RUNTIME . '/cache';

// 日志目录
const _LOGPATH    = _RUNTIME . '/log';

// 上传文件保存目录
const _UPLOAD     = _RUNTIME . '/upload';

// 敏感词库
const _FILTER_TXT = _RUNTIME . '/filter.txt';

// 时区
const _TIMEZONE   = 'PRC';

// 当前是不是php-cli环境
define('_PHPCLI', (bool)preg_match('/cli/i', php_sapi_name()));

// 当前的访问客户端类型
define('_CLIENT', $_SERVER['HTTP_CLIENT'] ?? 'UNKNOW');
