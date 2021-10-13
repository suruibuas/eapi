<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : Mcq.class.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/13
 *
 *                       更新 :
 *
 *                       说明 : MCQ队列类库
 *
 *********************************************************************************
 * Functions:
 *      get     :   读取
 *      set     :   写入
 *      del     :   删除
 *      flush   :   清空
 *********************************************************************************/

namespace eapi\lib;

use api\Log;
use Memcache;

class Mcq{

    // 连接句柄
    private object $_conn;
    // 配置信息
    private array  $_conf;

    public function __construct()
    {
        // 初始化数据库链接配置信息
        $this->_initConfig();
        // 链接数据库
        $this->_linkDb();
    }

    /*****************************************************************************
     * get -- 获取队列值
     *
     *
     * 输入 : 1个
     * @param string $key
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/13 : created
     *****************************************************************************/
    public function get(string $key)
    {
        $stime = microtime(TRUE);
        $queue = $this->_conn->get($this->_conf['PREFIX'] . $key);
        $etime = microtime(TRUE);
        Log::add('MCQ', [
            'KEY'  => $key,
            'TYPE' => 'read',
            'TIME' => round($etime - $stime, 4)
        ]);
        return $queue;
    }

    /*****************************************************************************
     * set -- 加入队列
     *
     *
     * 输入 : 2个
     * @param string $key
     * @param string|int|array $val
     * @param int $expire
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/13 : created
     *****************************************************************************/
    public function set(string $key, string|int|array $val, int $expire = 0)
    {
        $stime = microtime(TRUE);
        // 格式化队列值
        $val = is_array($val) ? json_encode($val, JSON_UNESCAPED_UNICODE) : $val;
        $this->_conn->set($this->_conf['PREFIX'] . $key, $val, MEMCACHE_COMPRESSED, $expire);
        $etime = microtime(TRUE);
        Log::add('MCQ', [
            'KEY'  => $key,
            'TYPE' => 'write',
            'TIME' => round($etime - $stime, 4),
            'DATA' => $val
        ]);
    }

    /*****************************************************************************
     * del -- 删除队列
     *
     *
     * 输入 : 2个
     * @param string $key
     * @param int $timeout
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/13 : created
     *****************************************************************************/
    public function del(string $key, int $timeout = 0)
    {
        $stime = microtime(TRUE);
        $this->_conn->delete($key, $timeout);
        $etime = microtime(TRUE);
        Log::add('MCQ', [
            'KEY'  => $key,
            'TYPE' => 'del',
            'TIME' => round($etime - $stime, 4)
        ]);
    }

    /*****************************************************************************
     * flush -- 清空
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/13 : created
     *****************************************************************************/
    public function flush()
    {
        return $this->_conn->flush();
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
     *     2021/10/13 : created
     *****************************************************************************/
    private function _initConfig()
    {
        //加载配置
        $conf = conf('MCQ');
        if ( ! $conf) fail(4001);
        if ( ! isset($conf['HOST']) || ! $conf['HOST'])
        {
            fail(4002, [
                'config' => 'HOST'
            ]);
        }
        $conf['PORT']   = $conf['PORT'] ?? 11211;
        $conf['PREFIX'] = $conf['PREFIX'] ?? '';
        $this->_conf = $conf;
    }

    /*****************************************************************************
     * _linkDb -- 连接Mcq
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/13 : created
     *****************************************************************************/
    private function _linkDb()
    {
        $stime = microtime(TRUE);
        $this->_conn = new Memcache();
        if ( ! $this->_conn->connect($this->_conf['HOST'], $this->_conf['PORT'], $this->_conf['TIMEOUT']))
            fail(4003);
        $etime = microtime(TRUE);
        Log::add('MCQ', [
            'CONNECTION' => round($etime - $stime, 4)
        ]);
    }

}