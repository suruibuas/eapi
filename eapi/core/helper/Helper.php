<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : eapi
 *
 *                       文件 : Helper.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/5
 *
 *                       更新 : 2021/10/5
 *
 *                       说明 : 助手函数
 *
 *********************************************************************************
 * Functions:
 *      fail        :   输出错误信息
 *      json        :   返回json格式数据
 *      random      :   根据规则生成随机字符串
 *      encode      :   字符串加密
 *      decode      :   解密通过encode加密的字符串
 *      isInt       :   安全的判断是不是数字
 *      conf        :   读取、删除、更新配置信息
 *      logs        :   写入本地文件日志
 *      cache       :   根据配置中的缓存类型进行读取、删除、写入操作
 *      get         :   安全的获取GET参数
 *      post        :   安全的获取POST参数
 *      httpHeader  :   安全的获取header头参数
 *      mysql       :   返回mysql实例
 *      redis       :   返回redis实例
 *      mongodb     :   返回mongodb实例
 *      rmq         :   返回rabbitmq实例
 *      es          :   返回es实例
 *      model       :   返回模型实例
 *      formatTime  :   格式化时间为优化的显示格式
 *      arrayInt    :   格式化数组中的数字为int类型
 *      getIp       :   获取客户端真实IP地址
 *      createJwt   :   根据用户ID生成JWT数据
 *      createUUID  :   根据用户ID生成UUID
 *      orderNum    :   生成唯一订单号
 *      numDays     :   计算今天到指定日期之间有多少天
 *      createIndex :   生成唯一索引
 *      jsonDecode  ：  带抛出异常的json解析
 *
 *********************************************************************************/

use api\Cache;
use api\Conf;
use api\Es;
use api\Io;
use api\Log;
use api\Mysql;
use api\Mongodb;
use api\Redis;
use api\Rmq;

/*********************************************************************************
 * fail -- 输出错误信息
 *
 *
 * 输入 : 2个
 * @param int      $code   状态码
 * @param mixed    $param  msg和msg中需要替换的参数数组
 *
 * 输出 : Nothing
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function fail(int $code, ...$param)
{
    // 加载系统语言包
    if ( ! $GLOBALS['_LANG'])
        $GLOBALS['_LANG'] = require _LANG . '/Zh-cn.php';
    /**
     * 如果第二个参数是数组，说明msg来自语言包中
     * 第二个参数是语言包中需要替换成变量的参数
     */
    $param[0] = $param[0] ?? [];
    if (is_array($param[0]))
    {
        $msg  = $GLOBALS['_LANG'][$code] ?? '执行错误';
        $args = $param[0];
    }
    else
    {
        $msg  = $param[0];
        $args = $param[1] ?? [];
    }
    // 替换参数
    if ( ! empty($args))
    {
        foreach($args as $k => $v)
            $msg = preg_replace("/\[$k]/", $v, $msg);
    }
    Io::out([
        'code' => $code,
        'msg'  => $msg,
        'data' => [
            'fail' => 1
        ]
    ]);
    exit;
}

/*********************************************************************************
 * json -- 返回json格式的数据
 *
 *
 * 输入 : 1个
 * @param mixed $param     code、msg、data
 *
 * 输出 : Nothing
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function json(...$param)
{
    /**
     * 待返回的完整数据，完整格式如下
     * {
     *      code : 100,
     *      msg  : '',
     *      data : {}
     * }
     */
    $data = [
        'code' => 100,
        'msg'  => '执行成功',
        'data' => [
            'success' => 1
        ]
    ];
    foreach ($param as $key => $val)
    {
        /**
         * 先判断如果是数组，则直接跳出循环，减少无用判断
         * json([
         *      'list' => []
         * ]);
         */
        if (is_array($val))
        {
            $data['data'] = $val;
            break;
        }
        if ($key === 0 && is_int($val))
            $data['code'] = $val;
        if ($key < 2 && is_string($val))
            $data['msg'] = $val;
    }
    Io::out($data);
}

/*********************************************************************************
 * random -- 根据类型生成随机字符串
 *
 *
 * 输入 : 2个
 * @param string $type  类型
 * @param int $len      长度
 *
 * 输出 : @return string
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function random(string $type = 'numletter', int $len = 4): string
{
    /**
     * 根据规则给出字符串池
     *
     *      numletter   :   数字和字母
     *      num         :   纯数字
     *      numnozero   :   纯数字排除0
     *      letter      :   纯字母
     */
    $pool = match ($type) {
        'numletter' => '0123456789abcdefghijklmnopqrstuvwxyz',
        'num'       => '0123456789',
        'numnozero' => '123456789',
        'letter'    => 'abcdefghijklmnopqrstuvwxyz',
        default     => ''
    };
    //初始化字符串
    $str = '';
    for ($i = 0; $i < $len; $i++)
        $str .= substr($pool, mt_rand(0, strlen($pool) - 1), 1);
    return $str;
}

/*********************************************************************************
 * encode -- 字符串加密
 *
 *
 * 输入 : 2个
 * @param string $str  待加密字符串
 * @param string $key  参与加密的KEY，默认在/conf/Define.php中配置
 *
 * 输出 : @return string
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function encode(string $str = '', string $key = _STRKEY): string
{
    // 编码字符串
    $encodeStr = base64_encode($str);
    // 编码KEY
    $encodeKey = base64_encode($key);
    // 取得KEY的长度
    $keyLength = strlen($encodeKey);
    // 加密后返回的字符串
    $string    = '';
    // 循环字符串并生成新的加密字符串
    for($i = 0; $i < strlen($encodeStr); $i++)
        $string .= ($i < $keyLength) ? $encodeStr[$i].$encodeKey[$i] : $encodeStr[$i];
    // 替换"="，避免还原出错
    return str_replace('=', '@||@', $string);
}

/*********************************************************************************
 * decode -- 字符串解密
 *
 *
 * 输入 : 2个
 * @param string $str   待解密字符串
 * @param string $key   参与解密的KEY
 *
 * 输出 : @return string
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function decode(string $str = '', string $key = _STRKEY): string
{
    // 还原"="
    $string    = str_split(str_replace('@||@', '=', $str));
    // 编码KEY
    $encodeKey = str_split(base64_encode($key));
    // 取得KEY的长度
    $keyLength = count($encodeKey);
    // 遍历已加密字符
    foreach ($string as $k => $v)
    {
        $key = $k + $k + 1;
        if ($k >= $keyLength || ! isset($string[$key]))
            break;
        if ($string[$key] == $encodeKey[$k])
            unset($string[$key]);
    }
    //反编译
    return base64_decode(implode('', $string));
}

/*********************************************************************************
 * isInt -- 安全的判断数字类型
 *
 *
 * 输入 : 1个
 * @param mixed $val   待校验的内容
 *
 * 输出 : @return bool
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function isInt(mixed $val): bool
{
    return gettype($val) == 'integer';
}

/*********************************************************************************
 * conf -- 操作配置
 *
 *
 * 输入 : 2个
 * @param string $key   配置key
 * @param null $val     配置val
 *
 * 输出 : @return bool|array|string
 *
 * 历史 :
 *     2021/10/6 : created
 *******************************************************************************
 */
function conf(string $key = '', $val = null): bool|array|string
{
    // 如果key为空则返回所有配置
    if ($key == '')
        return Conf::get();
    // 如果val不为null，则更新配置为传入的val
    if ( ! is_null($val))
    {
        Conf::set($key, $val);
        return TRUE;
    }
    // 根据KEY返回配置
    return Conf::get($key);
}

/*********************************************************************************
 * logs -- 写文件日志
 *
 *
 * 输入 : 2个
 * @param string|array $data 日志内容
 * @param string $path 日志存放目录
 *
 * 输出 : Nothing
 *
 * 历史 :
 *     2021/10/6 : created
 *******************************************************************************
 */
function logs(string|array $data = '', string $path = 'default'): void
{
    Log::save($data, $path);
}

/*********************************************************************************
 * cache -- 操作缓存
 *
 *
 * 输入 : 3个
 * @param string $key 缓存KEY
 * @param mixed $val 缓存内容
 * @param int $time 缓存时间
 *
 * 输出 : @return mixed
 *
 * 历史 :
 *     2021/10/6 : created
 *******************************************************************************
 * @return mixed
 *
 * 历史 :
 *     2021/10/6 : created
 *******************************************************************************
 */
function cache(string $key = '', mixed $val = '', int $time = 0): mixed
{
    // 读取缓存
    if ($val === '')
        return Cache::get($key);
    // 删除缓存
    elseif (is_null($val))
        return Cache::del($key);
    // 设置缓存
    else
        return Cache::set($key, $val, $time);
}

/*********************************************************************************
 * get -- 获取GET参数
 *
 *
 * 输入 : 2个
 * @param string $key           key
 * @param bool|string $default  如果没有传递则赋予默认值
 *
 * 输出 : @return mixed
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function get(string $key = '', mixed $default = FALSE): mixed
{
    return Io::get($key, $default);
}

/*********************************************************************************
 * post -- 获取POST参数
 *
 *
 * 输入 : 2个
 * @param string $key       key
 * @param bool $default     默认值
 *
 * 输出 : @return mixed
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function post(string $key = '', mixed $default = FALSE): mixed
{
    return Io::post($key, $default);
}

/*********************************************************************************
 * httpHeader -- 获取HEADER参数
 *
 *
 * 输入 : 2个
 * @param string $key
 * @param bool|string $default
 *
 * 输出 : @return mixed
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function httpHeader(string $key = '', mixed $default = FALSE): mixed
{
    return Io::header($key, $default);
}

/*********************************************************************************
 * mysql -- 返回MYSQL实例
 *
 *
 * 输入 : 1个
 * @param string $table 数据表名
 *
 * 输出 : @return Mysql|\eapi\lib\Mysql
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function mysql(string $table = ''): Mysql|\eapi\lib\Mysql
{
    return Mysql::from($table);
}

/*********************************************************************************
 * mongodb -- 返回MONGODB实例
 *
 *
 * 输入 : 1个
 * @param string $table 数据表明
 *
 * 输出 : @return Mongodb|\eapi\lib\Mongodb
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function mongodb(string $table = ''): Mongodb|\eapi\lib\Mongodb
{
    return Mongodb::from($table);
}

/*********************************************************************************
 * redis -- 返回REDIS实例
 *
 *
 * 输入 : 1个
 * @param array $param key、操作类型、前缀
 *
 * 输出 : @return Redis|eapi\lib\Redis
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function redis(array $param = []): Redis|eapi\lib\Redis
{
    if (empty($param))
        fail(101, '请指定redis的key值');
    // 缓存KEY
    $key    = $param[0];
    // 执行的操作，例如：zset、hget等等，默认为set
    $action = $param[1] ?? '';
    $prefix = isset($param[2]) ? $param[2].':' : '';
    return Redis::key($key, $action, $prefix);
}

/*********************************************************************************
 * rmq -- 返回rmq实例
 *
 *
 * 输入 : 3个
 * @param string|bool $exchange  交换机
 * @param string $queue          队列
 * @param bool $delayed          是否延迟队列
 *
 * 输出 : @return Rmq|\eapi\lib\Rmq
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function rmq(string|bool $exchange = '',
             string $queue = '',
             bool $delayed = FALSE): Rmq|\eapi\lib\Rmq
{
    /**
     * 如果第一个参数是bool，则第一个参数表示是否是延迟队列
     * 这种情况出现在 exchange和queue都默认使用了Rmq.php中的配置，
     * 不需要手动指定，并且又需要开启延迟队列的情况下
     *
     * 例如：
     * rmq(TRUE)->add();
     */
    if (is_bool($exchange) && $exchange)
        return Rmq::exchange('', TRUE)->queue('', TRUE)->bind();
    return Rmq::exchange($exchange, $delayed)->queue($queue, $delayed)->bind();
}

/*********************************************************************************
 * es -- 返回ES实例
 *
 *
 * 输入 : 1个
 * @param string $index 索引名称
 *
 * 输出 : @return Es|\eapi\lib\Es
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function es(string $index = ''): Es|\eapi\lib\Es
{
    return Es::from($index);
}

/*********************************************************************************
 * formatTime -- 格式化时间为多久之前的模式
 *
 *
 * 输入 : 2个
 * @param int|string $time  待格式的时间
 * @param string $format    格式化
 *
 * 输出 : @return string
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function formatTime(int|string $time, string $format = 'Y/m/d H:i') :string
{
    //开始时间
    $start = (is_numeric($time)) ? $time : strtotime($time);
    //现在时间
    $now   = time();
    //计算时间差
    $diff  = $now - $start;
    //格式化后的时间
    if ($diff < 60)
        $time = '刚刚';
    else if ($diff <= 3600)
        $time = floor($diff / 60).'分钟前';
    else if ($diff <= 86400)
        $time = floor($diff / 3600).'小时前';
    else if ($diff <= 604800)
        $time = floor($diff / 86400).'天前';
    else
        $time = date($format, $start);
    return $time;
}

/*********************************************************************************
 * arrayInt -- 格式化数组数字
 *
 *
 * 输入 : 2个
 * @param array $array      待格式化的数组
 * @param array $exclude    排除的字段数组
 *
 * 输出 : @return array
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function arrayInt(array $array, array $exclude = []): array
{
    if ( ! is_array($array))
        return $array;
    foreach ($array as $key => $val)
    {
        if (in_array($key, $exclude) ||
            ( ! is_numeric($val) && ! is_bool($val))) continue;
        if (is_bool($val))
            $val = $val === FALSE ? 0 : 1;
        else
            $val = (int) $val;
        $array[$key] = $val < 0 ? 0 : $val;
    }
    return $array;
}

/*********************************************************************************
 * getIp -- 获取访问来源IP
 *
 *
 * 输入 : Nothing
 *
 * 输出 : @return string
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function getIp(): string
{
    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['REMOTE_ADDR'];
}

/*********************************************************************************
 * createJwt -- 根据用户ID生成用户JWT信息
 *
 *
 * 输入 : 1个
 * @param int $id   用户ID
 *
 * 输出 : @return bool|string
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function createJwt(int $id = 0): bool|string
{
    if ( ! $id) return TRUE;
    $jwt = [
        'UID' => $id,
        'TOK' => conf('TOKEN'),
        'IAT' => time()
    ];
    return encode(json_encode($jwt));
}

/*********************************************************************************
 * createUUID -- 根据用户ID生成UUID
 *
 *
 * 输入 : 1个
 * @param int $id   用户ID
 *
 * 输出 : @return string
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function createUUID(int $id): string
{
    return md5(md5((string) $id).conf('TOKEN'));
}

/*********************************************************************************
 * orderNum -- 生成订单号
 *
 *
 * 输入 : Nothing
 *
 * 输出 : @return string
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function orderNum(): string
{
    return substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13)))), 0, 12).rand(10000, 99999);
}

/*********************************************************************************
 * creaetIndex -- 生成唯一索引
 *
 *
 * 输入 : Nothing
 *
 * 输出 : @return string
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function createIndex(): string
{
    $date  = date('YmdHis');
    $index = microtime();
    $index = explode(' ', $index);
    $index = explode('.', $index[0]);
    return $date.'_'.$index[1].'_'.rand(100000, 999999);
}

/*********************************************************************************
 * jsonDecode -- 带异常的解析JSON数据
 *
 *
 * 输入 : 1个
 * @param string $data  待解析内容
 *
 * 输出 : @return mixed
 *
 * 历史 :
 *     2021/10/6 : created
 *********************************************************************************/
function jsonDecode(string $data): mixed
{
    $data = json_decode($data, TRUE);
    if (JSON_ERROR_NONE !== json_last_error())
    {
        throw new InvalidArgumentException(
            'error:'.json_last_error_msg()
        );
    }
    return $data;
}