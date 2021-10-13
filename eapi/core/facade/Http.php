<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : eapi
 *
 *                       文件 : Cache.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/7
 *
 *                       更新 :
 *
 *                       说明 : 外观文件
 *
 *********************************************************************************
 * Functions:
 *
 *********************************************************************************/

namespace eapi\facade;
use eapi\Facade;

class Http extends Facade {

    public static function getFacadeAccessor(): string
    {
        return 'eapi\Http';
    }

}