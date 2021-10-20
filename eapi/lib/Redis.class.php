<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : Redis.class.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/9
 *
 *                       更新 :
 *
 *                       说明 : REDIS类库
 *
 *********************************************************************************
 * Functions:
 *      db      :   选择数据库
 *      key     :   设置KEY
 *      get     :   读取
 *      set     :   写入
 *      del     :   删除KEY
 *      clear   :   清空指定的KEY，模糊删除
 *      dbSize  :   获取KEY数量
 *      info    :   获取REDIS信息
 *      flush   :   清空库
 *      save    :   落地数据
 *      lock    :   加锁
 *********************************************************************************/

namespace eapi\lib;

use api\Log;
use Exception;

class Redis{

    // 连接句柄
    private mixed $_conn;
    // 配置信息
    private array $_conf;
    // 操作的索引KEY
    private array|string $_key;
    // 操作类型
    private string $_action;

    public function __construct()
    {
        // 初始化数据库链接配置信息
        $this->_initConfig();
        // 链接数据库
        $this->_linkDb();
        $this->_key    = '';
        $this->_action = '';
    }

    /*****************************************************************************
     * pipe -- 返回操作管道符
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/11 : created
     *****************************************************************************/
    public function pipe()
    {
        return $this->_conn->multi($this->_conn::PIPELINE);
    }

    /*****************************************************************************
     * db -- 选择数据库
     *
     *
     * 输入 : Nothing
     *
     * 输出 : @return Redis
     *
     * 历史 :
     *     2021/10/9 : created
     *****************************************************************************/
    public function db(int $num = 0): Redis
    {
        $this->_conn->select($num);
        return $this;
    }

    /*****************************************************************************
     * key -- 设置KEY
     *
     *
     * 输入 : 3个
     * @param string|array $key     操作的KEY
     * @param string $action        操作
     * @param bool|string $prefix   KEY前缀
     *
     * 输出 : @return Redis
     *
     * 历史 :
     *     2021/10/9 : created
     *****************************************************************************/
    public function key(string|array $key = '',
                        string $action = '',
                        bool|string $prefix = FALSE): Redis
    {
        $prefix = ( ! $prefix) ? $this->_conf['PREFIX'] : $prefix;
        if (is_array($key))
        {
            $this->_key = [];
            foreach ($key as $v)
                $this->_key[] = $prefix . $v;
        }
        else
        {
            $this->_key = $prefix . $key;
        }
        $this->_action = $action;
        return $this;
    }

    /*****************************************************************************
     * get -- 获取数据
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/9 : created
     *****************************************************************************/
    public function get(...$params)
    {
        $stime = microtime(TRUE);
        if ($this->_action == '') $this->_action = 'get';
        $handle = [$this->_key];
        $handle = array_merge($handle, $params);
        $data   = call_user_func_array([
            $this->_conn,
            $this->_action
        ], $handle);
        $etime = microtime(TRUE);
        Log::add('REDIS', [
            'TYPE'   => 'read',
            'ACTION' => $this->_action,
            'KEY'    => $this->_key,
            'TIME'   => round($etime - $stime, 4)
        ]);
        $this->_action = '';
        return $data;
    }

    /*****************************************************************************
     * set -- 写入数据
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/9 : created
     *****************************************************************************/
    public function set(...$params)
    {
        $stime = microtime(TRUE);
        if ($this->_action == '') $this->_action = 'set';
        // 根据类型获取写入值
        if (in_array($this->_action, [
            'zadd', 'hincrby', 'lset', 'zadd', 'hset', 'setex'
        ]))
        {
            $data = $params[0].'：'.$params[1];
        }
        elseif (in_array($this->_action, [
            'decr', 'incr'
        ]))
        {
            $data = 1;
        }
        else
        {
            $data = $params[0];
            if ($this->_action == 'set' && is_array($params[0]))
                $params[0] = json_encode($params[0], JSON_UNESCAPED_UNICODE);
        }
        $handle = [$this->_key];
        $handle = array_merge($handle, $params);
        $result = call_user_func_array([
            $this->_conn,
            $this->_action
        ], $handle);
        $etime = microtime(TRUE);
        Log::add('REDIS', [
            'TYPE'   => 'write',
            'ACTION' => $this->_action,
            'KEY'    => $this->_key,
            'DATA'   => $data,
            'TIME'   => round($etime - $stime, 4)
        ]);
        $this->_action = '';
        return $result;
    }

    /*****************************************************************************
     * del -- 删除数据                                                            
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/9 : created
     *****************************************************************************/
    public function del(...$params)
    {
        $stime = microtime(TRUE);
        if ($this->_action == '') $this->_action = 'del';
        if ($this->_action == 'del' && $this->_key == '')
        {
            $handle = [];
        }
        else
        {
            $handle = [$this->_key];
        }
        $handle = array_merge($handle, $params);
        call_user_func_array([
            $this->_conn,
            $this->_action
        ], $handle);
        $etime = microtime(TRUE);
        $log = [
            'TYPE'   => 'del',
            'ACTION' => $this->_action,
            'KEY'    => $this->_key,
            'TIME'   => round($etime - $stime, 4)
        ];
        if (isset($params[0]))
            $log['DATA'] = $params[0];
        Log::add('REDIS', $log);
        $this->_action = '';
    }

    /*****************************************************************************
     * clear -- 清空                                                            
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/9 : created
     *****************************************************************************/
    public function clear()
    {
        $stime = microtime(TRUE);
        $this->_conn->del($this->_conn->keys($this->_key));
        $etime = microtime(TRUE);
        $log = [
            'TYPE'   => 'clear',
            'ACTION' => 'del',
            'KEY'    => $this->_key,
            'TIME'   => round($etime - $stime, 4)
        ];
        Log::add('REDIS', $log);
    }

    /*****************************************************************************
     * dbSize -- 返回KEY数量                                                            
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/9 : created
     *****************************************************************************/
    public function dbSize()
    {
        return $this->_conn->dbSize();
    }

    /*****************************************************************************
     * info -- 返回REDIS相关信息                                                            
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/9 : created
     *****************************************************************************/
    public function info()
    {
        return $this->_conn->info();
    }

    /*****************************************************************************
     * flush -- 清空库                                                            
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/9 : created
     *****************************************************************************/
    public function flush()
    {
        $this->_conn->flushDB();
    }

    /*****************************************************************************
     * save -- 同步数据到硬盘                                                            
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/9 : created
     *****************************************************************************/
    public function save()
    {
        $this->_conn->save();
    }

    /*****************************************************************************
     * lock -- 加锁
     *
     *
     * 输入 : 2个
     * @param string $key
     * @param int $expire
     *
     * 输出 : @return mixed
     *
     * 历史 :
     *     2021/10/9 : created
     ***************************************************************************
     */
    public function lock(string $key = '', int $expire = 1): mixed
    {
        // 设置过期时间
        $expire = time() + $expire;
        // 设置排他KEY
        $lock   = $this->key($key, 'setnx', 'lock:')->set($expire);
        // 如果锁存在，则判断是否过期
        if ( ! $lock)
        {
            $time = $this->get();
            // 如果已经过期，则重新加锁
            if ($time < time())
            {
                $this->del();
                $lock = $this->set($expire);
            }
        }
        return $lock;
    }

    /*****************************************************************************
     * unlock -- 释放锁
     *
     *
     * 输入 : 1个
     * @param string $key
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/11 : created
     *****************************************************************************/
    public function unlock(string $key = '')
    {
        $this->key($key, 'del', 'lock:')->del();
    }

    /*****************************************************************************
     * _initConfig -- 初始化配置信息
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/9 : created
     *****************************************************************************/
    private function _initConfig()
    {
        //加载配置
        $conf = conf('REDIS');
        if ( ! $conf) fail(3001);
        if ( ! isset($conf['HOST']))
        {
            fail(3002, [
                'config' => 'HOST'
            ]);
        }
        $conf['PASSWORD'] = ( ! isset($conf['PASSWORD']) || ! $conf['PASSWORD']) ? '' : $conf['PASSWORD'];
        $conf['TIMEOUT']  = ( ! isset($conf['TIMEOUT']) || ! $conf['TIMEOUT']) ? 1 : $conf['TIMEOUT'];
        $conf['DATABASE'] = ( ! isset($conf['DATABASE']) || ! $conf['DATABASE']) ? 0 : $conf['DATABASE'];
        $conf['PORT']     = ( ! isset($conf['PORT']) || ! $conf['PORT']) ? 6379 : $conf['PORT'];
        $conf['PREFIX']   = ( ! isset($conf['PREFIX']) || ! $conf['PREFIX']) ? '' : $conf['PREFIX'];
        $this->_conf = $conf;
    }

    /*****************************************************************************
     * _linkDb -- 连接REDIS
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/9 : created
     *****************************************************************************/
    private function _linkDb()
    {
        $stime = microtime(TRUE);
        $this->_conn = new \Redis();
        try {
            $this->_conn->pconnect($this->_conf['HOST'], $this->_conf['PORT'], $this->_conf['TIMEOUT']);
        } catch (Exception) {
            fail(3003);
        }
        if ($this->_conf['PASSWORD'] != '')
        {
            try {
                $this->_conn->auth($this->_conf['PASSWORD']);
            } catch (Exception) {
                fail(3004);
            }
        }
        $this->_conn->select($this->_conf['DATABASE']);
        $etime = microtime(TRUE);
        Log::add('REDIS', [
            'CONNECTION' => round($etime - $stime, 4)
        ]);
    }

}