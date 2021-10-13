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
 *                       开始 : 2021/10/5
 *
 *                       更新 :
 *
 *                       说明 : 提供全局的配置信息
 *
 *********************************************************************************
 * Functions:
 *
 *********************************************************************************/

return [
    // 默认项目
    'DEFAULT' => 'api',
    // 允许访问的项目，如果访问的项目不在以下设置内则使用默认项目，防止被重试
    'ALLOW'   => [
        'api'
    ],
    // 输入过滤
    'INPUTFILTER' => 'escape|xss',
    // 开启路由
    'ROUTER'      => FALSE,
    // 是否启用REST自动路由，启用后针对POST、PUT、DELETE可以实现自动路由，无需配置路由规则
    'REST_ROUTER' => FALSE,
    // REST类型路由操作别名
    'REST_ALIAS'  => [
        'POST'   => 'Add',
        'PUT'    => 'Edit',
        'DELETE' => 'Del'
    ],
    // 默认缓存类型
    'CACHE'          => 'file',
    // 默认缓存时长，单位秒
    'CACHETIME'      => 600,
    // 默认允许的上传文件类型
    'UPLOAD'         => ['jpg', 'gif', 'jpeg', 'png'],
    // 默认允许上传的文件大小，单位KB
    'UPLOAD_SIZE'    => 10240,
    // HTTP请求最大超时时间，单位秒
    'HTTP_TIMEOUT'   => 3,
    // HTTP请求最大并发数
    'HTTP_MAXTHREAD' => 10,
	// HTTP请求默认头部
    'HTTP_HEADER'    => [

    ]
];