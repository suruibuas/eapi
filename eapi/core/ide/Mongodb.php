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
 * MONGODB的IDE自动完成
 * ===============================================
 */

namespace api;

/**
 * Class Mongodb
 * @package api
 * @method static|Mongodb select(string $field = '')
 * @method static|Mongodb from(string $table = '')
 * @method Mongodb where(array $where = [])
 * @method Mongodb order(array $order = [])
 * @method Mongodb limit(int $skip = 0, int $limit = 0)
 * @method one(bool $noId = FALSE)
 * @method all(bool $noId = FALSE)
 * @method add(array $data = [], bool $many = FALSE)
 * @method edit(array $data = [], bool $inc = FALSE)
 * @method inc(array $data = [])
 * @method delete()
 * @method count()
 * @method createIndex(string $key = '')
 * @method getIndex()
 * @method dropIndex(string $key = '')
 */
class Mongodb{}