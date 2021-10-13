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
 *                       开始 : 2021/10/6
 *
 *                       更新 :
 *
 *                       说明 : 缓存类
 *
 *********************************************************************************
 * Functions:
 *      get     :   读取缓存
 *      set     :   设置缓存
 *      del     :   删除缓存
 *      clear   :   清空缓存
 *********************************************************************************/

namespace eapi;

use api\Redis;
use InvalidArgumentException;

class Cache{

    // 缓存类型
    private string $_cacheType;
    // 缓存时长
    private int $_cacheTime;

    public function __construct()
    {
        $this->_cacheType = conf('CACHE');
        $this->_cacheTime = conf('CACHETIME');
    }

    /*****************************************************************************
     * get -- 读取缓存
     *
     *
     * 输入 : 1个
     * @param string $key 缓存KEY
     *
     * 输出 : @return string|array|bool
     *
     * 历史 :
     *     2021/10/6 : created
     *****************************************************************************/
    public function get(string $key = ''): string|array|bool
    {
        $cache = ($this->_cacheType == 'file')
                    ? $this->_file($key)
                    : $this->_redis($key);
        // 尝试json解析
        try {
            return jsonDecode($cache);
        } catch(InvalidArgumentException) {
            return $cache;
        }
    }

    /*****************************************************************************
     * set -- 设置缓存
     *
     *
     * 输入 : 3个
     * @param string $key           缓存KEY
     * @param mixed $val            缓存内容
     * @param int|bool $time        缓存时间
     *
     * 输出 : @return bool|string
     *
     * 历史 :
     *     2021/10/6 : created
     *****************************************************************************/
    public function set(string $key = '',
                        mixed $val = '',
                        int|bool $time = FALSE): bool|string
    {
        $time = $time === FALSE
                    ? $this->_cacheTime
                    : $time;
        // 如果是数组则转json存储
        $val  = is_array($val)
                    ? json_encode($val, JSON_UNESCAPED_UNICODE)
                    : $val;
        return $this->_cacheType == 'file'
                    ? $this->_file($key, $val, $time)
                    : $this->_redis($key, $val, $time);
    }

    /*****************************************************************************
     * del -- 删除缓存
     *
     *
     * 输入 : 1个
     * @param string $key   缓存KEY
     *
     * 输出 : @return bool|null
     *
     * 历史 :
     *     2021/10/6 : created
     *****************************************************************************/
    public function del(string $key = ''): bool|null
    {
        return $this->_cacheType == 'file'
                    ? $this->_file($key, null)
                    : $this->_redis($key, null);
    }

    /*****************************************************************************
     * clear -- 清空缓存，只有redis缓存支持该操作
     *
     *
     * 输入 : 1个
     * @param string $keys  缓存KEY，支持“*”模糊删除
     *
     * 输出 : @return bool
     *
     * 历史 :
     *     2021/10/6 : created
     *****************************************************************************/
    public function clear(string $keys = '*'): bool
    {
        if ($this->_cacheType == 'file')
            fail(1010);
        Redis::key($keys)->clear();
        return TRUE;
    }

    /*****************************************************************************
     * _file -- 文件缓存操作
     *
     *
     * 输入 : 3个
     * @param string $key
     * @param mixed $val
     * @param int $time
     *
     * 输出 : @return bool|string
     *
     * 历史 :
     *     2021/10/6 : created
     *****************************************************************************/
    private function _file(string $key = '', mixed $val = '', int $time = 600): bool|string
    {
        // 缓存目录
        if ($key == '') return FALSE;
        $doc = '';
        // HASH缓存目录
        $pathArr = array_slice(str_split($hash = md5($key), 2), 0, 2);
        $cache   = _CACHE . '/';
        foreach ($pathArr as $path)
        {
            $cache .= $doc . $path;
            if ( ! is_dir($cache)) mkdir($cache);
            $doc = '/';
        }
        $cache .= '/' . $hash . '.php';
        // 读取缓存
        if ($val === '')
        {
            $stime = microtime(TRUE);
            if ( ! is_file($cache)) return FALSE;
            $data      = include($cache);
            $cacheTime = $data[1];
            $etime     = microtime(TRUE);
            // 记录文件操作日志
            \api\Log::add('FILE', [
                'KEY'  => $key,
                'FILE' => $cache,
                'TIME' => round($etime - $stime, 4),
                'TYPE' => 'read'
            ]);
            if ($cacheTime == 0) return $data[0];
            // 缓存超时判断
            if (time() >= $cacheTime) return FALSE;
            return $data[0];
        }
        // 删除缓存
        elseif (is_null($val))
        {
            if ( ! is_file($cache)) return FALSE;
            @unlink($cache);
            return $cache;
        }
        // 设置缓存
        else
        {
            if (is_array($val))
            {
                $data = [];
                foreach ($val as $k => $v)
                {
                    if (strlen($v) > 255)
                        $data[$k] = '该文本超过255字节，不写入日志';
                    else
                        $data[$k] = $v;
                }
                $data = json_encode($data, JSON_UNESCAPED_UNICODE);
            }
            $logData = [
                'TYPE'  => 'write',
                'DATA'  => $data ?? $val,
                'KEY'   => $key,
                'CTIME' => $time
            ];
            $stime = microtime(TRUE);
            $time  = $time == 0 ? 0 : time() + $time;
            $val   = '<?PHP return '.var_export([$val, $time], TRUE).';';
            file_put_contents($cache, $val);
            $etime = microtime(TRUE);
            $logData['TIME'] = round($etime - $stime, 4);
            // 记录文件操作日志
            \api\Log::add('FILE', $logData);
            return $cache;
        }
    }

    /*****************************************************************************
     * _redis -- redis缓存操作
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/6 : created
     *****************************************************************************/
    private function _redis(string $key = '', mixed $val = '', int $time = 600)
    {
        // 读取缓存
        if ($val === '')
            return Redis::key($key)->get();
        // 删除缓存
        elseif (is_null($val))
            return Redis::key($key)->del();
        // 设置缓存
        else
        {
            return $time === 0
                        ? Redis::key($key)->set($val)
                        : Redis::key($key, 'setex')->set($time, $val);
        }
    }

}