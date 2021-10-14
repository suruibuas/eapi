<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : Es.class.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/12
 *
 *                       更新 :
 *
 *                       说明 : ES类库
 *
 *********************************************************************************
 * Functions:
 *      select  :   查询字段
 *      from    :   指定索引
 *      where   :   查询条件
 *      order   :   排序规则
 *      limit   :   条数
 *      get     :   查询单条
 *      all     :   查询多条
 *      add     :   写入数据
 *      edit    :   编辑数据
 *      delete  :   删除数据
 *********************************************************************************/

namespace eapi\lib;

use api\Log;
use Elasticsearch\ClientBuilder;
use Exception;
use stdClass;

class Es{

    // 配置信息
    private array $_conf;
    // 句柄
    private object $_client;
    // 查询变量
    private string $_select;
    private array  $_where;
    private string $_from;
    private array  $_order;
    private int    $_limit;
    private int    $_len;

    public function __construct()
    {
        // 初始化数据库链接配置信息
        $this->_initConfig();
        // 初始化连接
        $this->_initLink();
        $this->_select = '';
        $this->_where  = [];
        $this->_from   = '';
        $this->_order  = [];
        $this->_limit  = 0;
        $this->_len    = 10;
    }

    /*****************************************************************************
     * select -- 指定查询字段
     *
     *
     * 输入 : 1个
     * @param string $select
     *
     * 输出 : @return Es
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    public function select(string $select = ''): Es
    {
        if ( ! $select) return $this;
        $this->_select = $select;
        return $this;
    }

    /*****************************************************************************
     * from -- 指定操作的索引
     *
     *
     * 输入 : 1个
     * @param string $name
     *
     * 输出 : @return Es
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    public function from(string $name = ''): Es
    {
        if ($name == '') fail(9015);
        $this->_from = $name;
        return $this;
    }

    /*****************************************************************************
     * where -- 查询条件
     *
     *
     * 输入 : 1个
     * @param array $where
     *
     * 输出 : @return Es
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    public function where(array $where = []): Es
    {
        if (empty($where)) return $this;
        $this->_where = [
            'bool' => $where
        ];
        return $this;
    }

    /*****************************************************************************
     * order -- 排序规则
     *
     *
     * 输入 : 1个
     * @param array $order
     *
     * 输出 : @return Es
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    public function order(array $order = []): Es
    {
        $this->_order = $order;
        return $this;
    }

    /*****************************************************************************
     * limit -- 查询条数
     *
     *
     * 输入 : 2个
     * @param int $limit
     * @param int $len
     *
     * 输出 : @return Es
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    public function limit(int $limit = 10, int $len = 0): Es
    {
        $this->_len = $limit;
        if ($len !== 0)
        {
            $this->_limit = $limit;
            $this->_len   = $len;
        }
        return $this;
    }

    /*****************************************************************************
     * get -- 查询单条数据
     *
     *
     * 输入 : 1个
     * @param string $id
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    public function get(string $id = '')
    {
        if ( ! $id) fail(9016);
        $stime  = microtime(TRUE);
        $params = [
            'index'  => $this->_from,
            'type'   => 'normal',
            'id'     => $id,
            'client' => [
                'timeout' => $this->_conf['TIMEOUT'],
                'connect_timeout' => $this->_conf['CONNECT_TIMEOUT']
            ]
        ];
        if ( ! empty($this->_select))
        {
            $this->_select = str_replace(' ', '', $this->_select);
            $params['_source'] = $this->_select;
        }
        try{
            $result = $this->_client->get($params);
            $etime  = microtime(TRUE);
            Log::add('ES', [
                'TYPE'  => 'read',
                'TIME'  => round($etime - $stime, 4),
                'PARAM' => $params
            ]);
            return $result['_source'];
        } catch (Exception $e) {
            fail(500, 'ES：'.$e->getMessage());
        }
        return TRUE;
    }

    /*****************************************************************************
     * all -- 查询多条数据
     *
     *
     * 输入 : 1个
     * @param array $highlight  高亮字段
     *
     * 输出 : @return array|bool
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    public function all(array $highlight = []): array|bool
    {
        $stime = microtime(TRUE);
        $body  = [];
        // 条件
        if ( ! empty($this->_where))
            $body['query'] = $this->_where;
        $body['highlight'] = [
            'pre_tags'  => ['<span class="es-highlight">'],
            'post_tags' => ['</span>']
        ];
        // 高亮字段
        if ( ! empty($highlight))
        {
            foreach($highlight as $field)
                $body['highlight']['fields'][$field] = new stdClass();
        }
        // 排序
        if ( ! empty($this->_order)) $body['sort'] = $this->_order;
        // 条数
        $body['size'] = $this->_len;
        if ($this->_limit !== 0) $body['from'] = $this->_limit;
        $params = [
            'index'  => $this->_from,
            'type'   => 'normal',
            'client' => [
                'timeout' => $this->_conf['TIMEOUT'],
                'connect_timeout' => $this->_conf['CONNECT_TIMEOUT']
            ]
        ];
        if ( ! empty($body)) $params['body'] = $body;
        if ( ! empty($this->_select))
        {
            $this->_select = str_replace(' ', '', $this->_select);
            $params['_source'] = $this->_select;
        }
        try{
            $result = $this->_client->search($params);
            $etime  = microtime(TRUE);
            Log::add('ES', [
                'TYPE'  => 'read',
                'TIME'  => round($etime - $stime, 4),
                'PARAM' => $params
            ]);
            $total = $result['hits']['total'];
            if ($total == 0)
            {
                return [
                    'TOTAL' => 0
                ];
            }
            $tmp = [];
            foreach ($result['hits']['hits'] as $key => $row)
            {
                $tmp[$key] = $row['_source'];
                $tmp[$key]['_id'] = $row['_id'];
                if (isset($row['highlight']))
                {
                    $highlight = [];
                    foreach ($row['highlight'] as $k => $r)
                        $highlight[$k] = $r[0];
                    $tmp[$key]['highlight'] = $highlight;
                }
            }
            $result = [];
            $result['DATA']  = $tmp;
            $result['TOTAL'] = $total;
            return $result;
        } catch(Exception $e) {
            fail(500, 'ES：'.$e->getMessage());
        }
        return TRUE;
    }

    /*****************************************************************************
     * add -- 插入数据
     *
     *
     * 输入 : 2个
     * @param array $data
     * @param bool $many
     *
     * 输出 : @return mixed
     *
     * 历史 :
     *     2021/10/12 : created
     ***************************************************************************
     */
    public function add(array $data = [], bool $many = FALSE): mixed
    {
        if (empty($data)) fail(9019);
        $stime = microtime(TRUE);
        if ( ! $many)
        {
            $data['timestamp'] = time();
            $params = [
                'index' => $this->_from,
                'type'  => 'normal'
            ];
            if (isset($data['_id']))
            {
                $params['id'] = $data['_id'];
                unset($data['_id']);
            }
            $params['body'] = $data;
            try{
                $result = $this->_client->index($params);
                $etime = microtime(TRUE);
                Log::add('ES', [
                    'TYPE'  => 'add',
                    'TIME'  => round($etime - $stime, 4),
                    'PARAM' => $params
                ]);
                return $result['_id'];
            } catch (Exception $e) {
                fail(500, 'ES：'.$e->getMessage());
            }
        }
        $params = [];
        foreach ($data as $row)
        {
            $index = [
                '_index' => $this->_from,
                '_type'  => 'normal'
            ];
            if (isset($row['_id']))
            {
                $index['_id'] = $row['_id'];
                unset($row['_id']);
            }
            $params['body'][] = [
                'index' => $index
            ];
            $row['timestamp'] = time();
            $params['body'][] = $row;
        }
        try{
            $this->_client->bulk($params);
            $etime = microtime(TRUE);
            Log::add('ES', [
                'TYPE'  => 'add',
                'TIME'  => round($etime - $stime, 4),
                'PARAM' => $params
            ]);
            return count($data);
        } catch(Exception $e) {
            fail(500, 'ES：'.$e->getMessage());
        }
        return TRUE;
    }

    /*****************************************************************************
     * edit -- 更新数据
     *
     *
     * 输入 : 2个
     * @param string $id
     * @param array $data
     *
     * 输出 : @return mixed
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    public function edit(string $id = '', array $data = []): mixed
    {
        if (empty($data)) fail(9019);
        if ($id == '') fail(9020);
        $stime  = microtime(TRUE);
        $params = [
            'index' => $this->_from,
            'type'  => 'normal',
            'id'    => $id
        ];
        $body = [];
        // 更新的数据
        $script = [];
        $inline = '';
        $doc    = '';
        foreach ($data as $k => $v)
        {
            $inline .= $doc . 'ctx._source.' . $k;
            $inline .= isInt($v) ? '=' . $v : '="' . $v . '"';
            $doc     = ';';
        }
        $script['inline'] = $inline;
        $body['script']   = $script;
        $params['body']   = $body;
        try{
            $result = $this->_client->update($params);
            $etime  = microtime(TRUE);
            Log::add('ES', [
                'TYPE'  => 'edit',
                'TIME'  => round($etime - $stime, 4),
                'PARAM' => $params
            ]);
            return $result['_shards']['successful'];
        } catch(Exception) {
            return FALSE;
        }
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
     *     2021/10/12 : created
     *****************************************************************************/
    public function delete()
    {
        $stime  = microtime(TRUE);
        $params = [
            'index' => $this->_from,
            'type'  => 'normal'
        ];
        $body = [];
        // 条件
        if ( ! empty($this->_where)) $body['query'] = $this->_where;
        if ( ! empty($body)) $params['body'] = $body;
        try{
            $result = $this->_client->deleteByQuery($params);
            $etime  = microtime(TRUE);
            Log::add('ES', [
                'TYPE'  => 'delete',
                'TIME'  => round($etime - $stime, 4),
                'PARAM' => $params
            ]);
            return $result['deleted'];
        } catch (Exception $e) {
            fail(500, 'ES：'.$e->getMessage());
        }
        return TRUE;
    }

    /*****************************************************************************
     * createIndex -- 创建索引
     *
     *
     * 输入 : 3个
     * @param string $name
     * @param array $field
     * @param array $settings
     *
     * 输出 : @return string
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    public function createIndex(string $name = '',
                                array $field = [],
                                array $settings = []): string
    {
        if ($name == '') fail(9013);
        if (empty($field)) fail(9014);
        $params = [
            'index' => $name,
            'body'  => [
                'mappings' => [
                    'normal' => [
                        '_all'=> [
                            'enabled' => FALSE
                        ],
                        'properties' => $field
                    ]
                ]
            ]
        ];
        // 自定义设置
        if ( ! empty($settings)) $params['body']['settings'] = $settings;
        try{
            return $this->_client->indices()->create($params);
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }

    /*****************************************************************************
     * deleteIndex -- 删除索引
     *
     *
     * 输入 : 1个
     * @param string $name
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    public function deleteIndex(string $name = '')
    {
        $params = [
            'index' => $name
        ];
        return $this->_client
                    ->indices()
                    ->delete($params);
    }

    /*****************************************************************************
     * getMapping -- 获取索引映射信息
     *
     *
     * 输入 : 1个
     * @param string $name
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    public function getMapping(string $name = '')
    {
        $params = [
            'index'  => $name,
            'client' => [
                'ignore' => 404
            ]
        ];
        return $this->_client
                    ->indices()
                    ->getMapping($params);
    }

    /*****************************************************************************
     * putMapping -- 修改mapping信息
     *
     *
     * 输入 : 2个
     * @param string $name
     * @param array $field
     *
     * 输出 : @return mixed
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    public function putMapping(string $name = '', array $field = []): mixed
    {
        $params = [
            'index' => $name,
            'type'  => 'normal',
            'body'  =>  [
                'normal' => [
                    'properties' => $field
                ]
            ]
        ];
        return $this->_client
                    ->indices()
                    ->putMapping($params);
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
     *     2021/10/12 : created
     *****************************************************************************/
    private function _initConfig()
    {
        //加载配置
        $conf = conf('ES');
        if ( ! $conf) fail(9011);
        if ( ! isset($conf['HOSTS']) || ! $conf['HOSTS'])
        {
            fail(9012, [
                'config' => 'HOSTS'
            ]);
        }
        $conf['RETRY'] = $conf['RETRY'] ?? 2;
        $conf['CONNECT_TIMEOUT'] = $conf['CONNECT_TIMEOUT'] ?? 10;
        $conf['TIMEOUT'] = $conf['TIMEOUT'] ?? 10;
        $this->_conf = $conf;
    }

    /*****************************************************************************
     * _initLink -- 初始化连接句柄                                                            
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    private function _initLink()
    {
        $stime = microtime(TRUE);
        $tmp   = [];
        foreach ($this->_conf['HOSTS'] as $host)
            $tmp[] = array_change_key_case($host, CASE_LOWER);
        $client = ClientBuilder::create();
        $this->_client = $client->setHosts($tmp)
                                ->setRetries($this->_conf['RETRY'])
                                ->build();
        $etime = microtime(TRUE);
        Log::add('ES', [
            'CONNECTION' => round($etime - $stime, 4)
        ]);
    }

}