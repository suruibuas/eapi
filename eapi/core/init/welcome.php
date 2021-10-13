<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>欢迎使用eapi2.0极速API开发框架（PHP8.0+）</title>
    <link rel="stylesheet" href="https://cdn.bootcss.com/minireset.css/0.0.2/minireset.min.css">
    <style>
        h1{
            margin: 20px 20px 40px 20px;
            font-size: 38px;
        }
        h4{
            margin:0 0 20px 20px;
        }
    </style>
</head>

<body>
    <H1>欢迎使用：eapi - 极速API开发框架</H1>
    <h4>环境需求：PHP8.0+</h4>
    <h4>框架版本：<?PHP echo _VERSION;?></h4>
    <h4>默认项目：<?PHP echo api\Conf::get('DEFAULT');?></h4>
    <h4>已经自动为您创建好默认项目，如需重新创建，请删除 eapi/runtime/init.lock 文件后刷新页面</h4>
    <h4>开发文档：<a href="https://www.kancloud.cn/suruibuas/phpcan/544303" target="_blank">开发文档地址</a></h4>
    <h4>GIT地址：<a href="https://github.com/suruibuas/phpcan">https://github.com/suruibuas/phpcan</a></h4>
</body>
</html>