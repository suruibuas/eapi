<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : Model.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/7
 *
 *                       更新 :
 *
 *                       说明 : 模型类
 *
 *********************************************************************************
 * Functions:
 *      find            :   快速查询
 *      first           :   第一条
 *      last            :   最后一条
 *      count           :   总数
 *      sum             :   求和
 *      max             :   查最大
 *      min             :   查最小
 *      destroy         :   销毁
 *      insert          :   插入
 *      update          :   更新
 *      inc             :   递增
 *      dec             :   递减
 *      insertOrUpdate  :   新增或更新
 *
 *********************************************************************************/

namespace eapi;

use api\Mysql;

class Model
{

    // 默认数据表
    protected string $table;
    // 默认主键
    protected string $pk;
    // 打时间戳
    protected bool $timestamps;
    // 添加时间字段名
    protected string $createAt;
    // 修改时间字段名
    protected string $updateAt;

    public function __construct()
    {
        $this->table      = '';
        $this->pk         = 'id';
        $this->timestamps = FALSE;
        $this->createAt   = 'create_at';
        $this->updateAt   = 'update_at';
        Mysql::pk($this->pk);
    }

    /*****************************************************************************
     * find -- 快速查询
     *
     *
     * 输入 : 2个
     * @param array|bool|int $cond  查询条件
     * @param array $param          查询参数
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function find(array|bool|int $cond = FALSE, array $param = [])
    {
        if ( ! $cond)
        {
            $cond = [];
        }
        else
        {
            if (is_numeric($cond)) $cond = [$this->pk => $cond];
        }
        // 查询字段
        $field = $param['field'] ?? '';
        $limit = $param['limit'] ?? FALSE;
        if (is_numeric($cond))
        {
            $order = '';
        }
        else
        {
            $order = $param['order'] ?? (($limit === 1) ? '' : $this->pk . ' DESC');
        }
        $data = Mysql::select($field)
                     ->from($this->table)
                     ->where($cond)
                     ->order($order);
        $group = $param['group'] ?? '';
        if ($group != '') $data = $data->group($group);
        if ($limit !== FALSE)
        {
            if( ! strpos($limit, ','))
            {
                $data->limit($limit);
            }
            else
            {
                $arr = explode(',', $limit);
                $data->limit($arr[0], $arr[1]);
            }
        }
        return ($limit == 1 || isset($cond[$this->pk])) ? $data->one() : $data->all();
    }

    /*****************************************************************************
     * first -- 查询符合条件的第一条
     *
     *
     * 输入 : 2个
     * @param array|bool|int $cond  条件
     * @param string $field         查询的字段
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function first(array|bool|int $cond = FALSE, string $field = '')
    {
        return $this->find($cond, [
            'field' => $field,
            'order' => $this->pk . ' ASC',
            'limit' => 1
        ]);
    }

    /*****************************************************************************
     * last -- 查询符合条件的最后一条
     *
     *
     * 输入 : 2个
     * @param array|bool|int $cond
     * @param string $field
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function last(array|bool|int $cond = FALSE, string $field = '')
    {
        return $this->find($cond, [
            'field' => $field,
            'order' => $this->pk . ' DESC',
            'limit' => 1
        ]);
    }

    /*****************************************************************************
     * count -- 查询总数
     *
     *
     * 输入 : 1个
     * @param array|bool|int $cond
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function count(array|bool|int $cond = FALSE)
    {
        return $this->find($cond, [
            'field' => 'COUNT(' . $this->pk . ') AS count',
            'limit' => 1
        ]);
    }

    /*****************************************************************************
     * sum -- 计算总和
     *
     *
     * 输入 : 2个
     * @param array|bool|int $cond
     * @param string $field
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function sum(array|bool|int $cond = FALSE, string $field = '')
    {
        if ( ! $field) fail(2008);
        return $this->find($cond, [
            'field' => 'SUM(' . $field . ') AS sum',
            'limit' => 1
        ]);
    }

    /*****************************************************************************
     * max -- 获取最大值
     *
     *
     * 输入 : 2个
     * @param array|bool|int $cond
     * @param string $field
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function max(array|bool|int $cond = FALSE, string $field = '')
    {
        if ( ! $field) fail(2008);
        return $this->find($cond, [
            'field' => 'MAX(' . $field . ') AS max',
            'limit' => 1
        ]);
    }

    /*****************************************************************************
     * min -- 获取最小值
     *
     *
     * 输入 : 2个
     * @param array|bool|int $cond
     * @param string $field
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function min(array|bool|int $cond = FALSE, string $field = '')
    {
        if ( ! $field) fail(2008);
        return $this->find($cond, [
            'field' => 'MIN(' . $field . ') AS min',
            'limit' => 1
        ]);
    }

    /*****************************************************************************
     * destory -- 根据主键删除
     *
     *
     * 输入 : 1个
     * @param array|int $id
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function destory(array|int $id): bool|int
    {
        if (is_numeric($id))
            $cond = [$this->pk => $id];
        else
            $cond = [$this->pk.' IN' => $id];
        return mysql($this->table)
                    ->where($cond)
                    ->delete();
    }

    /*****************************************************************************
     * insert -- 写入数据
     *
     *
     * 输入 : 1个
     * @param array $data
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function insert(array $data = []): bool|int
    {
        if ($this->timestamps) $data[$this->createAt] = time();
        return mysql($this->table)->add($data);
    }

    /*****************************************************************************
     * update -- 更新数据
     *
     *
     * 输入 : 2个
     * @param array|bool|int $cond
     * @param array $data
     *
     * 输出 : @return bool|int
     *
     * 历史 :
     *     2021/10/7 : created
     ***************************************************************************
     */
    public function update(array|bool|int $cond = FALSE, array $data = []): bool|int
    {
        if (is_numeric($cond)) $cond = [$this->pk => $cond];
        if ($this->timestamps) $data[$this->updateAt] = time();
        return mysql($this->table)
                    ->where($cond)
                    ->edit($data);
    }

    /*****************************************************************************
     * inc -- 递增
     *
     *
     * 输入 : 2个
     * @param array|bool|int $cond
     * @param array $data
     *
     * 输出 : @return bool|int
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function inc(array|bool|int $cond = FALSE, array $data = []): bool|int
    {
        $tmp = [];
        foreach ($data as $k => $v)
            $tmp[$k] = ($v < 0) ? '`'.$k.'` - '.abs($v) : '`'.$k.'` + '.$v;
        if ($this->timestamps) $tmp[$this->updateAt] = time();
        return mysql($this->table)->where($cond)->edit($tmp);
    }

    /*****************************************************************************
     * dec -- 递减
     *
     *
     * 输入 : 2个
     * @param array|bool|int $cond
     * @param array $data
     *
     * 输出 : @return bool|int
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function dec(array|bool|int $cond = FALSE, array $data = []): bool|int
    {
        $tmp = [];
        foreach ($data as $k => $v)
            $tmp[$k] = ($v < 0) ? '`'.$k.'` + '.abs($v) : '`'.$k.'` - '.$v;
        if ($this->timestamps) $tmp[$this->updateAt] = time();
        return mysql($this->table)->where($cond)->edit($tmp);
    }

    /*****************************************************************************
     * insertOrUpdate -- 更新或写入数据
     *
     *
     * 输入 : 3个
     * @param array|bool|int $cond
     * @param array $insert
     * @param array $update
     *
     * 输出 : @return mixed
     *
     * 历史 :
     *     2021/10/7 : created
     ***************************************************************************
     */
    public function insertOrUpdate(array|bool|int $cond = FALSE,
                                   array $insert = [],
                                   array $update = []): mixed
    {
        return mysql($this->table)->where($cond)->replace($insert, $update);
    }

}