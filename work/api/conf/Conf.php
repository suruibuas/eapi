<?PHP

/**
 * ===============================================
 * eapi - 极速API开发框架
 * ===============================================
 * 版本：PHP8.0 +
 * 作者: 
 * 日期: 2021-10-07 16:25
 * ===============================================
 * 服务自定义配置文件
 * ===============================================
 */

return [
    // 输入过滤
    'INPUTFILTER' => 'escape|xss',
    // 开启路由
    'ROUTER'      => TRUE,
    // 是否启用REST自动路由，启用后针对POST、PUT、DELETE可以实现自动路由，无需配置路由规则
    'REST_ROUTER' => FALSE,
    // REST类型路由操作别名
    'REST_ALIAS'  => [
       'POST'   => 'Add',
       'PUT'    => 'Edit',
       'DELETE' => 'Del'
    ],
    // 默认中间件
    'MIDDLEWARE' => [
        'BEFORE' => [

        ],
        'AFTER'  => [

        ]
    ],
    // 默认缓存类型，支持 file、redis
    'CACHE'      => 'file',
    // 默认缓存时长，单位秒
    'CACHETIME'  => 600,
    // 当前服务版本
    'VERSION'    => '1.0.0',
    // HTTP请求最大超时时间
    'HTTP_TIMEOUT' => 3,
    // HTTP请求最大并发数
    'HTTP_MAXTHREAD' => 10
];