<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : Mongodb.class.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/11
 *
 *                       更新 :
 *
 *                       说明 : MONGODB类库
 *
 *********************************************************************************
 * Functions:
 *      select      :   指定查询字段
 *      from        :   指定操作的集合
 *      where       :   指定条件
 *      order       :   排序
 *      limit       :   条数
 *      one         :   查询单条
 *      all         :   查询多条
 *      add         :   单条/批量添加
 *      edit        :   编辑
 *      inc         :   递增/递减
 *      delete      :   删除
 *      count       :   查询总数
 *      createIndex :   创建索引
 *      getIndex    :   查询索引
 *      dropIndex   :   删除索引
 *********************************************************************************/

namespace eapi\lib;
use api\Log;
use Exception;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\Regex;
use MongoDB\Client;

class Mongodb{

    // 连接句柄
    private object $_conn;
    // 配置信息
    private array $_conf;

    private array  $_select = [];
    private string $_from   = '';
    private array  $_where  = [];
    private array  $_order  = [];
    private int    $_limit  = 0;
    private int    $_skip   = 0;

    public function __construct()
    {
        // 初始化数据库链接配置信息
        $this->_initConfig();
        // 链接数据库
        $this->_linkDb();
    }

    /*****************************************************************************
     * select -- 指定查询字段
     *
     *
     * 输入 : 1个
     * @param string $field
     *
     * 输出 : @return Mongodb
     *
     * 历史 :
     *     2021/10/11 : created
     *****************************************************************************/
    public function select(string $field = ''): Mongodb
    {
        $this->_select = [];
        if ($field != '')
        {
            $arr = explode(',', $field);
            foreach ($arr as $val)
                $this->_select[trim($val)] = 1;
        }
        return $this;
    }

    /*****************************************************************************
     * from -- 指定数据表
     *
     *
     * 输入 : 1个
     * @param string $table
     *
     * 输出 : @return Mongodb
     *
     * 历史 :
     *     2021/10/11 : created
     *****************************************************************************/
    public function from(string $table = ''): Mongodb
    {
        $this->_from = $table;
        return $this;
    }

    /*****************************************************************************
     * where -- 指定查询条件
     *
     *
     * 输入 : 1个
     * @param array $cond
     *
     * 输出 : @return Mongodb
     *
     * 历史 :
     *     2021/10/11 : created
     *****************************************************************************/
    public function where(array $cond = []): Mongodb
    {
        $tmp = [];
        $map = [
            '>'     => '$gt',
            '<'     => '$lt',
            '>='    => '$gte',
            '<='    => '$lte',
            '<>'    => '$ne',
            'IN'    => '$in',
            'NOTIN' => '$nin'
        ];
        foreach ($cond as $k => $v)
        {
            $k = trim($k);
            // 主键查询
            if ($k == '_id')
            {
                $tmp[$k] = new ObjectId($v);
            }
            else
            {
                if (preg_match('/(.*)\s+(.*)/i', $k, $data))
                    $tmp[$data[1]] = ($data[2] == 'LIKE') ? new Regex($v, 'i') : [$map[trim($data[2])] => $v];
                else
                    $tmp[$k] = $v;
            }
        }
        $this->_where = $tmp;
        return $this;
    }

    /*****************************************************************************
     * order -- 指定排序条件
     *
     *
     * 输入 : 1个
     * @param array $order
     *
     * 输出 : @return Mongodb
     *
     * 历史 :
     *     2021/10/11 : created
     *****************************************************************************/
    public function order(array $order = []): Mongodb
    {
        $tmp = [];
        $map = [
            'DESC' => -1,
            'ASC'  => 1
        ];
        foreach ($order as $k => $v)
            $tmp[$k] = $map[strtoupper($v)];
        $this->_order = $tmp;
        return $this;
    }

    /*****************************************************************************
     * limit -- 指定条数限制
     *
     *
     * 输入 : 2个
     * @param int $skip
     * @param int $limit
     *
     * 输出 : @return Mongodb
     *
     * 历史 :
     *     2021/10/11 : created
     *****************************************************************************/
    public function limit(int $skip = 0, int $limit = 0): Mongodb
    {
        if ($limit == 0)
        {
            $this->_skip  = 0;
            $this->_limit = $skip;
        }
        else
        {
            $this->_skip  = $skip;
            $this->_limit = $limit;
        }
        return $this;
    }

    /*****************************************************************************
     * one -- 获取单条
     *
     *
     * 输入 : 1个
     * @param bool $noId    返回结果是否需要包含_id，默认FALSE
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/11 : created
     *****************************************************************************/
    public function one(bool $noId = FALSE)
    {
        $stime  = microtime(TRUE);
        $result = $this->_collection()->findOne($this->_where, [
            'projection' => $this->_select,
            'typeMap'    => ['root' => 'array']
        ]);
        $etime = microtime(TRUE);
        Log::add('MONGODB', [
            'TYPE'  => 'read',
            'TIME'  => round($etime - $stime, 4),
            'TABLE' => $this->_conf['PREFIX'].$this->_from,
            'WHERE' => $this->_where,
            'PARAM' => [
                'FIELD' => $this->_select,
                'LIMIT' => 1
            ]
        ]);
        if ($noId) unset($result['_id']);
        // 重置
        $this->_select = [];
        $this->_from   = '';
        $this->_order  = [];
        $this->_where  = [];
        return $result;
    }

    /*****************************************************************************
     * all -- 获取多条
     *
     *
     * 输入 : 1个
     * @param bool $noId    返回结果是否需要包含_id
     *
     * 输出 : @return array
     *
     * 历史 :
     *     2021/10/11 : created
     *****************************************************************************/
    public function all(bool $noId = FALSE): array
    {
        $stime = microtime(TRUE);
        $param = [
            'projection' => $this->_select,
            'sort'       => $this->_order,
            'skip'       => $this->_skip,
            'typeMap'    => ['root' => 'array']
        ];
        if ($this->_limit != 0)
            $param['limit'] = $this->_limit;
        $result = $this->_collection()->find($this->_where, $param);
        $etime  = microtime(TRUE);
        Log::add('MONGODB', [
            'TYPE'  => 'read',
            'TIME'  => round($etime - $stime, 4),
            'TABLE' => $this->_conf['PREFIX'].$this->_from,
            'WHERE' => $this->_where,
            'PARAM' => [
                'FIELD' => $param['projection'],
                'SORT'  => $param['sort'],
                'SKIP'  => $param['skip'],
                'LIMIT' => $param['limit'] ?? 0
            ]
        ]);
        $tmp = [];
        foreach ($result as $row)
        {
            if ($noId) unset($row['_id']);
            $tmp[] = $row;
        }
        // 重置
        $this->_select = [];
        $this->_from   = '';
        $this->_order  = [];
        $this->_limit  = 0;
        $this->_skip   = 0;
        $this->_where  = [];
        return $tmp;
    }

    /*****************************************************************************
     * add -- 写入数据
     *
     *
     * 输入 : 2个
     * @param array $data   写入的数据
     * @param bool $many    是否批量写入
     *
     * 输出 : @return mixed
     *
     * 历史 :
     *     2021/10/11 : created
     *****************************************************************************/
    public function add(array $data = [], bool $many = FALSE): mixed
    {
        if (empty($data)) fail(5005);
        try
        {
            $stime  = microtime(TRUE);
            $result = $many
                        ? $this->_collection()->insertMany($data)
                        : $this->_collection()->insertOne($data);
            $etime  = microtime(TRUE);
            Log::add('MONGODB', [
                'TYPE'  => 'add',
                'TIME'  => round($etime - $stime, 4),
                'TABLE' => $this->_conf['PREFIX'] . $this->_from,
                'DATA'  => $data
            ]);
            $this->_from = '';
            return $many
                        ? $result->getInsertedIds()
                        : $result->getInsertedId();
        }
        catch (Exception $exception)
        {
            fail(0, $exception->getMessage());
        }
        return TRUE;
    }

    /*****************************************************************************
     * edit -- 更新数据
     *
     *
     * 输入 : 2个
     * @param array $data
     * @param bool $inc 是否是递增递减操作，统一用inc，默认递增，如果递减则传负数
     *
     * 输出 : @return void
     *
     * 历史 :
     *     2021/10/11 : created
     ***************************************************************************
     */
    public function edit(array $data = [], bool $inc = FALSE)
    {
        if (empty($data)) fail(5006);
        try
        {
            $stime = microtime(TRUE);
            if ($inc === TRUE)
            {
                $data = [
                    '$inc' => $data
                ];
                $action = 'updateOne';
            }
            else
            {
                $data = [
                    '$set' => $data
                ];
                $action = 'updateMany';
            }
            $result = $this->_collection()->$action($this->_where, $data, [
                'upsert' => TRUE
            ]);
            $etime  = microtime(TRUE);
            Log::add('MONGODB', [
                'TYPE'  => $inc === TRUE ? 'inc' : 'edit',
                'TIME'  => round($etime - $stime, 4),
                'TABLE' => $this->_conf['PREFIX'] . $this->_from,
                'WHERE' => $this->_where,
                'DATA'  => $data
            ]);
            $this->_from  = '';
            $this->_where = [];
            return $result->getModifiedCount();
        }
        catch (Exception $exception)
        {
            fail(0, $exception->getMessage());
        }
    }

    /*****************************************************************************
     * inc -- 递增递减数据
     *
     *
     * 输入 : 1个
     * @param array $data
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/11 : created
     *****************************************************************************/
    public function inc(array $data = [])
    {
        $this->edit($data, TRUE);
    }

    /*****************************************************************************
     * delete -- 删除数据                                                            
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/11 : created
     *****************************************************************************/
    public function delete()
    {
        $stime  = microtime(TRUE);
        $result = $this->_collection()->deleteMany($this->_where);
        $etime  = microtime(TRUE);
        Log::add('MONGODB', [
            'TYPE'  => 'del',
            'TIME'  => round($etime - $stime, 4),
            'TABLE' => $this->_conf['PREFIX'].$this->_from,
            'WHERE' => $this->_where
        ]);
        $this->_from  = '';
        $this->_where = [];
        return $result->getDeletedCount();
    }

    /*****************************************************************************
     * count -- 查询总数                                                            
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/11 : created
     *****************************************************************************/
    public function count()
    {
        $result = $this->_collection()->count($this->_where);
        $this->_from  = '';
        $this->_where = [];
        return $result;
    }

    /*****************************************************************************
     * createIndex -- 创建索引                                                            
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/11 : created
     *****************************************************************************/
    public function createIndex(string $key = '')
    {
        return $this->_collection()->createIndex([
            $key => 1
        ]);
    }

    /*****************************************************************************
     * getIndex -- 返回索引
     *
     *
     * 输入 : Nothing
     *
     * 输出 : @return array
     *
     * 历史 :
     *     2021/10/11 : created
     *****************************************************************************/
    public function getIndex(): array
    {
        return $this->_collection()->listIndexes();
    }

    /*****************************************************************************
     * dropIndex -- 删除索引
     *
     *
     * 输入 : 1个
     * @param string $key
     *
     * 输出 : @return mixed
     *
     * 历史 :
     *     2021/10/11 : created
     *****************************************************************************/
    public function dropIndex(string $key = ''): mixed
    {
        try{
            return $this->_collection()->dropIndex($key);
        }
        catch(Exception $e)
        {
            return $e->getMessage();
        }
    }

    /*****************************************************************************
     * _initConfig -- 初始化数据库链接配置信息
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/11 : created
     *****************************************************************************/
    private function _initConfig()
    {
        // 加载配置
        $conf = conf('MONGODB');
        if ( ! $conf) fail(5001);
        if ( ! isset($conf['HOST']) || ! $conf['HOST'])
        {
            fail(5004, [
                'config' => 'HOST'
            ]);
        }
        if ( ! isset($conf['USERNAME']) || ! $conf['USERNAME'])
        {
            fail(5004, [
                'config' => 'USERNAME'
            ]);
        }
        if ( ! isset($conf['DATABASE']) || ! $conf['DATABASE'])
        {
            fail(5004, [
                'config' => 'DATABASE'
            ]);
        }
        $conf['PORT']     = ( ! isset($conf['PORT']) || ! $conf['PORT']) ? 27017 : $conf['PORT'];
        $conf['PASSWORD'] = $conf['PASSWORD'] ?? '';
        $conf['PREFIX']   = $conf['PREFIX'] ?? '';
        $this->_conf = $conf;
    }

    /*****************************************************************************
     * _linkDb -- 连接数据库
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/11 : created
     *****************************************************************************/
    private function _linkDb()
    {
        $stime = microtime(TRUE);
        $dsn = 'mongodb://'.
                $this->_conf['USERNAME'].
                ':'.
                $this->_conf['PASSWORD'].
                '@'.
                $this->_conf['HOST'].
                ':'.
                $this->_conf['PORT'].
                '/'.
                $this->_conf['DATABASE'];
        $client = new Client($dsn, [], [
            'typeMap' => [
                'array'    => 'array',
                'document' => 'array',
                'root'     => 'array'
            ]
        ]);
        $db = $this->_conf['DATABASE'];
        $this->_conn = $client->$db;
        $etime = microtime(TRUE);
        Log::add('MONGODB', [
            'CONNECTION' => round($etime - $stime, 4)
        ]);
    }

    /*****************************************************************************
     * _collection -- 获取句柄
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/11 : created
     *****************************************************************************/
    private function _collection()
    {
        if ($this->_from == '') fail(5007);
        $table = $this->_conf['PREFIX'] . $this->_from;
        return $this->_conn->$table;
    }

}