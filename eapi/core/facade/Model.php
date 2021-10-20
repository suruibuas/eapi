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

/**
 * @method static find(array|bool|int $cond = FALSE, array $param = [])
 * @method static first(array|bool|int $cond = FALSE, string|array $field = '')
 * @method static last(array|bool|int $cond = FALSE, string|array $field = '')
 * @method static count(array|bool|int $cond = FALSE)
 * @method static sum(array|bool|int $cond = FALSE, string $field = '', array $param = [])
 * @method static max(array|bool|int $cond = FALSE, string $field = '')
 * @method static min(array|bool|int $cond = FALSE, string $field = '')
 * @method static destory(array|int $id)
 * @method static insert(array $data = [])
 * @method static update(array|bool|int $cond = FALSE, array $data = [])
 * @method static inc(array|bool|int $cond = FALSE, array $data = [])
 * @method static dec(array|bool|int $cond = FALSE, array $data = [])
 * @method static insertOrUpdate(array|bool|int $cond = FALSE, array $insert = [], array $update = [])
 */
class Model extends \eapi\Model {}