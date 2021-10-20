<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : Rmq.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/12
 *
 *                       更新 :
 *
 *                       说明 : RMQ的IDE自动完成
 *
 *********************************************************************************
 * Functions:
 *
 *********************************************************************************/

namespace api;

/**
 * Class Rmq
 * @package eapi
 * @method static|Rmq exchange(string $name = '', bool $delayed = FALSE, string $type = 'direct', bool $passive = FALSE, bool $durable = TRUE, bool $autoDelete = FALSE)
 * @method Rmq queue(string $name = '', bool $delayed = FALSE, bool $passive = FALSE, bool $durable = TRUE, bool $exclusive = FALSE, bool $autoDelete = FALSE)
 * @method Rmq bind(string $route = '')
 * @method consume(callable $callback, bool $autoAck = FALSE, string $tag = '')
 * @method get()
 * @method static|Rmq ack(string $message = '')
 * @method add(string|array $data, bool $batch = FALSE, bool|int $delayed = FALSE)
 * @method publish()
 *
 */
class Rmq{}