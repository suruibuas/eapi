<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : Mysql.class.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/7
 *
 *                       更新 :
 *
 *                       说明 : MYSQL数据库类库
 *
 *********************************************************************************
 * Functions:
 *      pk      :   设置主键
 *      select  :   select语句
 *      from    :   from语句
 *      where   :   where条件
 *      order   :   order语句
 *      group   :   group语句
 *      limit   :   limit语句
 *      join    :   join语句
 *      one     :   查询单条
 *      all     :   查询多条
 *      add     :   添加
 *      edit    :   编辑
 *      delete  :   删除
 *      replace :   插入或更新
 *      query   :   直接执行SQL语句
 *      lastSql :   获取最后一条SQL
 *********************************************************************************/

namespace eapi\lib;

use api\Log;
use PDO;
use PDOException;

class Mysql{

    // 定义成员属性
    private mixed  $_conn;                // 数据库连接属性
    private string $_host;                // 主机
    private string $_user;                // 数据库用户名
    private string $_pass;                // 数据库密码
    private string $_data;                // 连接的数据库
    private string $_port;                // 端口
    private string $_char;                // 字符编码
    private string $_prefix;              // 数据表前缀
    private string $_table;               // 操作的数据表

    // 定于SQL语句的各个小模块
    private string $_select   = '';
    private string $_from     = '';
    private string $_where    = '';
    private string $_order    = '';
    private string $_group    = '';
    private string $_limit    = '';
    private string $_join     = '';
    private string $_pk       = '';
    private array  $_bindData = [];
    private string $_querySql = '';
    private string $_realSql  = '';
    private int    $_keyNum   = 0;
    private mixed  $_query    = FALSE;

    // 结果集
    private object $_result;

    public function __construct()
    {
        $this->_conn = null;
        // 初始化数据库链接配置信息
        $this->_initConfig();
        // 链接数据库
        $this->_linkDb();
    }

    public function __destruct()
    {
        $this->_conn = null;
    }

    /*****************************************************************************
     * pk -- 设置主键字段
     *
     *
     * 输入 : 1个
     * @param string $pk
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/8 : created
     *****************************************************************************/
    public function pk(string $pk = ''): Mysql
    {
        if ($pk == '') return $this;
        $this->_pk = $pk;
        return $this;
    }

    /*****************************************************************************
     * select -- 组成SQL语句：SELECT `field`
     *
     *
     * 输入 : 1个
     * @param string $field
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/8 : created
     *****************************************************************************/
    public function select(array|string $field = ''): Mysql
    {
        $this->_bindData = [];
        if (is_array($field)) $field = implode(',', $field);
        $this->_select = 'SELECT ' . ($field == '' ? '*' : $field);
        return $this;
    }

    /*****************************************************************************
     * from -- 组成SQL语句：FROM `table`
     *
     *
     * 输入 : 1个
     * @param string $table
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/8 : created
     *****************************************************************************/
    public function from(string $table): Mysql
    {
        if ($table == '') fail(2003);
        $this->_table = $this->_prefix . $table;
        $this->_from  = ' FROM ' . $this->_table;
        return $this;
    }

    /*****************************************************************************
     * where -- 组成SQL语句：WHERE `field` = ?
     *
     *
     * 输入 : 1个
     * @param array $where
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/8 : created
     *****************************************************************************/
    public function where(array $where = []): Mysql
    {
        if ( ! $where) return $this;
        $this->_where .= ' WHERE ';
        if ( ! empty($this->_bindData)) $this->_bindData = [];
        $i = 0;
        foreach ($where as $key => $val)
        {
            if ($i == 0)
            {
                $and = '';
            }
            else
            {
                $and = preg_match('/ or/i', $key) ? '' : ' AND ';
            }
            $this->_where .= $and . $key;
            /**
             * $val == FALSE的情况出现在key已经把条件拼接好了
             */
            if ($val === FALSE) continue;
            if (isInt($val) || is_string($val))
            {
                if (preg_match('/\s/', $key))
                    $this->_where .= preg_match('/^or\s+([\w]+)$/i', $key) ? ' = ? ' : ' ? ';
                else
                    $this->_where .= ' = ? ';
                $this->_bindData[] = $val;
            }
            else
            {
                $this->_where .= is_array($val) ? " ('".implode("','", $val)."') " : ' IS NULL ';
            }
            $i++;
        }
        return $this;
    }

    /*****************************************************************************
     * order -- 组成SQL语句：ORDER BY `field` DESC/ASC
     *
     *
     * 输入 : 1个
     * @param string $order
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/8 : created
     *****************************************************************************/
    public function order(string $order = ''): Mysql
    {
        if ($order == '') return $this;
        $this->_order = ' ORDER BY '.$order;
        return $this;
    }

    /*****************************************************************************
     * group -- 组成SQL语句：GROUP BY `field`
     *
     *
     * 输入 : 1个
     * @param string $group
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/8 : created
     *****************************************************************************/
    public function group(string $group = ''): Mysql
    {
        if ($group == '') return $this;
        $this->_group = ' GROUP BY '.$group;
        return $this;
    }

    /*****************************************************************************
     * limit -- 组成SQL语句：LIMIT 10,1
     *
     *
     * 输入 : 2个
     * @param int $limit
     * @param int $len
     *
     * 输出 : @return Mysql
     *
     * 历史 :
     *     2021/10/8 : created
     ***************************************************************************
     */
    public function limit(int $limit = 1, int $len = 0): Mysql
    {
        $this->_limit = ' LIMIT '.$limit;
        if ($len > 0) $this->_limit .= ','.$len;
        return $this;
    }

    /*****************************************************************************
     * join -- 组成SQL语句：INNER JOIN `table` ON ....
     *
     *
     * 输入 : 3个
     * @param string $join
     * @param string $on
     * @param string $model
     *
     * 输出 : @return Mysql
     *
     * 历史 :
     *     2021/10/8 : created
     *****************************************************************************/
    public function join(string $join = '',
                         string $on = '',
                         string $model = 'INNER'): Mysql
    {
        if ($join == '' OR $on == '') return $this;
        $this->_join .= ' '.$model.' JOIN '.$this->_prefix.$join.' ON '.$on;
        return $this;
    }

    /*****************************************************************************
     * one -- 获取单条记录
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/8 : created
     *****************************************************************************/
    public function one()
    {
        $stime = microtime(TRUE);
        if ( ! $this->_query)
        {
            $this->_limit = ' LIMIT 1';
            // 组装执行SQL
            $this->_createSql();
        }
        $data = $this->_result->fetch(PDO::FETCH_ASSOC);
        $this->_query = FALSE;
        $etime = microtime(TRUE);
        Log::add('MYSQL', [
            'SQL'  => $this->lastSql(),
            'TIME' => round($etime - $stime, 4)
        ]);
        return $data;
    }

    /*****************************************************************************
     * all -- 查询多条记录
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/8 : created
     *****************************************************************************/
    public function all()
    {
        $stime = microtime(TRUE);
        if ( ! $this->_query)
        {
            // 组装执行SQL
            $this->_createSql();
        }
        $data = $this->_result->fetchAll(PDO::FETCH_ASSOC);
        $this->_query = FALSE;
        $etime = microtime(TRUE);
        Log::add('MYSQL', [
            'SQL'  => $this->lastSql(),
            'TIME' => round($etime - $stime, 4)
        ]);
        return $data;
    }

    /*****************************************************************************
     * add -- 插入数据
     *
     *
     * 输入 : 2个
     * @param array $data
     * @param bool  $multi
     *
     * 输出 : @return bool|int
     *
     * 历史 :
     *     2021/10/8 : created
     *****************************************************************************/
    public function add(array $data = [], bool $multi = FALSE): bool|int
    {
        $stime = microtime(TRUE);
        // 组成SQL
        $this->_querySql = 'INSERT INTO `'.$this->_table.'` ';
        // 单条插入
        if ($multi === FALSE)
        {
            // 遍历字段
            $field = $value  = '(';
            $doc   = '';
            $this->_bindData = [];
            foreach ($data as $key => $val)
            {
                $field .= $doc . '`' . $key . '`';
                $value .= $doc . ' ? ';
                $doc    = ',';
                $this->_bindData[] = $val;
            }
            $field .= ')';
            $value .= ')';
            $this->_querySql .= $field . ' VALUES ' . $value;
            // 执行SQL
            $this->_exec();
            $etime = microtime(TRUE);
            Log::add('MYSQL', [
                'SQL'  => $this->lastSql(),
                'TIME' => round($etime - $stime, 4)
            ]);
            return $this->_conn->lastInsertId();
        }
        foreach ($data as $key => $row)
        {
            // 拼接数据表字段
            if ($key == 0)
            {
                $this->_querySql .= '(';
                $field = [];
                foreach ($row as $fkey => $val)
                    array_push($field, $fkey);
                $this->_querySql .= implode(',', $field).') VALUES ';
            }
            if ($key > 0)
                $this->_querySql .= ',';
            $this->_querySql .= '(';
            $doc = '';
            foreach ($row as $val)
            {
                $this->_querySql .= $doc . '"' . $val . '"';
                $doc = ',';
            }
            $this->_querySql .= ')';
        }
        return $this->query($this->_querySql);
    }

    /*****************************************************************************
     * edit -- 更新数据
     *
     *
     * 输入 : 1个
     * @param array $data
     *
     * 输出 : @return bool|int
     *
     * 历史 :
     *     2021/10/8 : created
     *****************************************************************************/
    public function edit(array $data = []): bool|int
    {
        $stime = microtime(TRUE);
        // 组成SQL
        $this->_querySql = 'UPDATE `'.$this->_table.'` SET ';
        $doc = '';
        foreach ($data as $key => $val)
        {
            $this->_querySql .= $doc.'`'.$key.'` = ';
            if (isInt($val) || preg_match('/`[\w]+`\s?[+|-]\s?[\d]+/', $val))
                $this->_querySql .= $val;
            else
                $this->_querySql .= '"'.$val.'"';
            $doc = ',';
        }
        $this->_querySql .= $this->_where . $this->_limit;
        // 执行SQL
        $this->_exec();
        $this->_where = '';
        $etime = microtime(TRUE);
        Log::add('MYSQL', [
            'SQL'  => $this->lastSql(),
            'TIME' => round($etime - $stime, 4)
        ]);
        return $this->_result->rowcount();
    }

    /*****************************************************************************
     * delete -- 删除数据
     *
     *
     * 输入 : Nothing
     *
     * 输出 : @return bool|int
     *
     * 历史 :
     *     2021/10/8 : created
     *****************************************************************************/
    public function delete(): bool|int
    {
        $stime = microtime(TRUE);
        $this->_querySql  = 'DELETE FROM `' . $this->_table . '` ' . $this->_where;
        // 执行SQL
        $this->_exec();
        $etime = microtime(TRUE);
        Log::add('MYSQL', [
            'SQL'  => $this->lastSql(),
            'TIME' => round($etime - $stime, 4)
        ]);
        return $this->_result->rowcount();
    }

    /*****************************************************************************
     * query -- 执行SQL语句
     *
     *
     * 输入 : 1个
     * @param string $sql
     *
     * 输出 : @return Mysql|bool|int
     *
     * 历史 :
     *     2021/10/8 : created
     *****************************************************************************/
    public function query(string $sql): Mysql|bool|int
    {
        if ($sql == '') return FALSE;
        $stime = microtime(TRUE);
        $this->_querySql = str_replace('[prefix]', $this->_prefix, $sql);
        // 执行SQL
        try{
            $this->_result = $this->_conn->query($this->_querySql);
        }
        catch (PDOException $exception)
        {
            if ( ! _PHPCLI)
            {
                fail(2004, [
                    'error' => $this->_querySql,
                    'msg'   => $exception->getMessage()
                ]);
            }
            $this->_conn = null;
            $this->_linkDb();
            $this->query($sql);
        }
        $etime = microtime(TRUE);
        Log::add('MYSQL', [
            'SQL'  => $this->_querySql,
            'TIME' => round($etime - $stime, 4)
        ]);
        // 判断操作类型
        if (preg_match('/^select|show/i', $this->_querySql))
        {
            $this->_query = TRUE;
            return $this;
        }
        else
        {
            return $this->_result->rowcount();
        }
    }

    /*****************************************************************************
     * replace -- 插入或更新数据
     *
     *
     * 输入 : 2个
     * @param array $insert
     * @param array $update
     *
     * 输出 : @return mixed
     *
     * 历史 :
     *     2021/10/8 : created
     ***************************************************************************
     */
    public function replace(array $insert = [], array $update = []): mixed
    {
        $pk = $this->_pk == '' ? '*' : $this->_pk;
        $this->_querySql = 'SELECT ' . $pk . ' FROM ' . $this->_table . $this->_where . ' LIMIT 1';
        // 执行SQL
        $this->_exec();
        $data = $this->_result->fetch();
        if (empty($data))
        {
            $this->add($insert);
            $this->_where = '';
        }
        else
        {
            if ( ! empty($update)) $this->edit($update);
        }
        return $this->_result->rowcount();
    }

    /*****************************************************************************
     * lastSql -- 获取执行的最后一条SQL
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/8 : created
     *****************************************************************************/
    public function lastSql(): string
    {
        return $this->_realSql;
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
     *     2021/10/8 : created
     *****************************************************************************/
    private function _initConfig()
    {
        //加载配置
        $conf = conf('MYSQL');
        if ( ! $conf) fail(2001);
        if ( ! isset($conf['HOST']))
        {
            fail(2005, [
                'config' => 'HOST'
            ]);
        }
        if ( ! isset($conf['USERNAME']))
        {
            fail(2005, [
                'config' => 'USERNAME'
            ]);
        }
        if ( ! isset($conf['DATABASE']))
        {
            fail(2005, [
                'config' => 'DATABASE'
            ]);
        }
        $conf['PORT']    = ( ! isset($conf['PORT']) || ! $conf['PORT']) ? 3306 : $conf['PORT'];
        $conf['CHARSET'] = ( ! isset($conf['CHARSET']) || ! $conf['CHARSET']) ? 'utf8' : $conf['CHARSET'];
        $conf['PREFIX']  = ( ! isset($conf['PREFIX'])) ? '' : $conf['PREFIX'];
        $this->_host     = $conf['HOST'];
        $this->_user     = $conf['USERNAME'];
        $this->_pass     = $conf['PASSWORD'];
        $this->_data     = $conf['DATABASE'];
        $this->_port     = $conf['PORT'];
        $this->_char     = $conf['CHARSET'];
        $this->_prefix   = $conf['PREFIX'];
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
     *     2021/10/8 : created
     *****************************************************************************/
    private function _linkDb()
    {
        $stime = microtime(TRUE);
        $dsn   = 'mysql:host='.$this->_host.';dbname='.$this->_data.';port='.$this->_port.';charset='.$this->_char;
        try {
            $this->_conn = new PDO($dsn, $this->_user, $this->_pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch(PDOException $err)
        {
            fail(2002, [
                'error' => $err->getMessage()
            ]);
        }
        $this->_conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, FALSE);
        $this->_conn->exec('SET NAMES '.$this->_char);
        $etime = microtime(TRUE);
        Log::add('MYSQL', [
            'CONNECTION' => round($etime - $stime, 4)
        ]);
    }

    /*****************************************************************************
     * _creaetSql -- 组装SQL语句
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/8 : created
     *****************************************************************************/
    private function _createSql()
    {
        // 拼接SQL语句
        $this->_querySql = $this->_select.
                           $this->_from.
                           $this->_join.
                           $this->_where.
                           $this->_group.
                           $this->_order.
                           $this->_limit;
        // 执行SQL
        $this->_exec();
        // 重置
        $this->_join  = '';
        $this->_where = '';
        $this->_bindData = [];
		$this->_group = '';
        $this->_order = '';
        $this->_limit = '';
    }

    /*****************************************************************************
     * _exec -- 执行SQL                                                            
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/8 : created
     *****************************************************************************/
    private function _exec()
    {
        // 获取真实SQL
        $this->_realSql = $this->_realSql();
        // 执行预查询
        $this->_result = $this->_conn->prepare($this->_querySql);
        // 占位符赋值
        if ( ! empty($this->_bindData))
        {
            foreach ($this->_bindData as $k => $v)
                $this->_result->bindValue(($k + 1), $v, (is_int($v)) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        try {
            $this->_result->execute();
        } catch (PDOException $exception){
            if ( ! _PHPCLI)
            {
                fail(2004, [
                    'error' => $this->_realSql,
                    'msg'   => $exception->getMessage()
                ]);
            }
            $this->_conn = null;
            $this->_linkDb();
            $this->_exec();
        }
    }

    /*****************************************************************************
     * _realSql -- 获取执行的SQL语句
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/8 : created
     *****************************************************************************/
    private function _realSql(): array|string|null
    {
        $sql = preg_replace_callback('/\s+\?\s+/i', [$this, '_sqlReplace'], $this->_querySql);
        $this->_keyNum = 0;
        return $sql;
    }

    /*****************************************************************************
     * _sqlReplace -- 组装真实SQL
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/8 : created
     *****************************************************************************/
    private function _sqlReplace(): string
    {
        $val = $this->_bindData[$this->_keyNum];
        if (is_array($val))
            $val = ' ('.implode(',', $val).') ';
        elseif (is_numeric($val))
            $val = ' '.$val.' ';
        else
            $val = " '".$val."' ";
        $this->_keyNum++;
        return $val;
    }

}