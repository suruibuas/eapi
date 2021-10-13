<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : index.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/7
 *
 *                       更新 :
 *
 *                       说明 : 系统入口文件
 *
 *********************************************************************************
 * Functions:
 *
 *********************************************************************************/

if(version_compare(PHP_VERSION,'8.0.0','<'))
    exit('eapi V2.0版本要求PHP版本必须大于8.0');

// 项目根目录，项目所有涉及到目录的地方以此作为根目录
define('ROOTPATH', dirname(__FILE__));
// 引入驱动文件
require ROOTPATH . '/eapi/Driver.php';