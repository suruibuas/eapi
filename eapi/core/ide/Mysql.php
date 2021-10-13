<?php

/**
 * ===============================================
 * eapi微服务框架 - docker版本
 * ===============================================
 * 版本：PHP7.0 +
 * 作者: suruibuas / 317953536@qq.com
 * 日期: 2018/5/2 16:50
 * 官网：http://www.eapi.cn
 * ===============================================
 * MYSQL的IDE自动完成
 * ===============================================
 */

namespace api;

/**
 * Class Mysql
 * @package api
 * @method static|Mysql pk(string $pk = '')
 * @method static|Mysql select(string $field = '')
 * @method static|Mysql from(string $table)
 * @method Mysql where(array $where = [])
 * @method Mysql order(string $order = '')
 * @method Mysql group(string $group = '')
 * @method Mysql limit(int $from = 1, int $size = 0)
 * @method Mysql join(string $join = '', string $on = '', string $model = 'INNER')
 * @method one()
 * @method all()
 * @method add(array $data = [], bool $multi = FALSE)
 * @method edit(array $data = [])
 * @method delete()
 * @method static|Mysql query(string $sql)
 * @method replace(array $insert = [], array $update = [])
 * @method static lastSql()
 */
class Mysql{}