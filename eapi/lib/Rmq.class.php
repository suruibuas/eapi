<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : Rmq.class.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/12
 *
 *                       更新 :
 *
 *                       说明 : RABBITMQ类库
 *
 *********************************************************************************
 * Functions:
 *      exchange    :   指定交换机
 *      queue       :   指定队列
 *      bind        :   绑定交换机和队列
 *      consume     :   消费队列
 *      get         :   读取单条队列
 *      ack         :   手动ACK确认
 *      add         :   添加任务
 *      publish     :   提交并发任务
 *********************************************************************************/

namespace eapi\lib;

use api\Log;
use Exception;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class Rmq{

    // 配置信息
    private array $_conf = [];
    // 句柄
    private object $_conn;
    // 通道
    private object $_channel;
    // 交换机
    private string $_exchange;
    // 队列
    private string $_queue;
    // 路由
    private string $_route;

    public function __construct()
    {
        // 初始化配置信息
        $this->_initConfig();
        // 初始化连接
        $this->_initLink();
        $this->_exchange = '';
        $this->_queue    = '';
        $this->_route    = '';
    }

    /*****************************************************************************
     * exchange -- 指定交换机
     *
     *
     * 输入 : 6个
     * @param string $name
     * @param string $type
     * @param bool $delayed
     * @param bool $passive
     * @param bool $durable
     * @param bool $autoDelete
     *
     * 输出 : @return Rmq
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    public function exchange(
        string $name     = '',
        bool $delayed    = FALSE,
        string $type     = 'direct',
        bool $passive    = FALSE,
        bool $durable    = TRUE,
        bool $autoDelete = FALSE
    ): Rmq
    {
        $name = ($name == '') ? $this->_conf['EXCHANGE'] : $name;
        // 延迟队列
        if ($delayed)
        {
            $this->_exchange = $name.'_delayed';
            $this->_channel->exchange_declare(
                $this->_exchange,
                'x-delayed-message',
                $passive,
                $durable,
                $autoDelete,
                FALSE,
                FALSE,
                new AMQPTable([
                    'x-delayed-type' => 'fanout'
                ])
            );
            return $this;
        }
        $this->_exchange = $name;
        $this->_channel->exchange_declare(
            $this->_exchange,
            $type,
            $passive,
            $durable,
            $autoDelete
        );
        return $this;
    }

    /*****************************************************************************
     * queue -- 指定队列
     *
     *
     * 输入 : 6个
     * @param string $name      队列名称
     * @param bool $delayed     是否延迟
     * @param bool $passive     当队列不存在时不自动创建
     * @param bool $durable     持久化
     * @param bool $exclusive   队列允许其他通道消费
     * @param bool $autoDelete  队列执行完毕后自动删除
     *
     * 输出 : @return Rmq
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    public function queue(
        string $name     = '',
        bool $delayed    = FALSE,
        bool $passive    = FALSE,
        bool $durable    = TRUE,
        bool $exclusive  = FALSE,
        bool $autoDelete = FALSE
    ): Rmq
    {
        $name = ($name == '') ? $this->_conf['QUEUE'] : $name;
        if ($delayed)
        {
            $this->_queue = $name.'_delayed';
            $this->_channel->queue_declare(
                $this->_queue,
                $passive,
                $durable,
                $exclusive,
                $autoDelete,
                FALSE,
                new AMQPTable([
                    'x-dead-letter-exchange' => 'delayed'
                ])
            );
            return $this;
        }
        $this->_queue = $name;
        $this->_channel->queue_declare(
            $this->_queue,
            $passive,
            $durable,
            $exclusive,
            $autoDelete
        );
        return $this;
    }

    /*****************************************************************************
     * bind -- 交换机和队列绑定
     *
     *
     * 输入 : 1个
     * @param string $route 路由
     *
     * 输出 : @return Rmq
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    public function bind(string $route = ''): Rmq
    {
        if ($this->_exchange == '')
            $this->_exchange = $this->_conf['EXCHANGE'];
        if ($this->_queue == '')
            $this->_queue = $this->_conf['QUEUE'];
        // 绑定交换机和队列
        $this->_channel->queue_bind($this->_queue, $this->_exchange, $route);
        $this->_route = $route;
        return $this;
    }
    
    /*****************************************************************************
     * consume -- 消费队列
     *
     *
     * 输入 : 3个
     * @param callable $callback
     * @param bool $autoAck
     * @param string $tag
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    public function consume(
        callable $callback,
        bool $autoAck = FALSE,
        string $tag = ''
    )
    {
        if ($this->_queue == '')
            $this->_queue = $this->_conf['QUEUE'];
        // 消费队列
        $this->_channel->basic_consume(
            $this->_queue,
            $tag,
            FALSE,
            $autoAck,
            FALSE,
            FALSE,
            $callback
        );
        while(count($this->_channel->callbacks))
            $this->_channel->wait();
    }

    /*****************************************************************************
     * get -- 读取单条队列
     *
     *
     * 输入 : Nothing
     *
     * 输出 : @return bool|string
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    public function get(): bool|string
    {
        $stime = microtime(TRUE);
        if ($this->_queue == '')
            $this->_queue = $this->_conf['QUEUE'];
        $msg = $this->_channel->basic_get($this->_queue);
        if ( ! $msg) return FALSE;
        $this->_channel->basic_ack($msg->delivery_info['delivery_tag']);
        $this->_channel->close();
        $this->_conn->close();
        $etime = microtime(TRUE);
        Log::add('RMQ', [
            'TYPE'     => 'get',
            'EXCHANGE' => $this->_exchange,
            'QUEUE'    => $this->_queue,
            'ROUTE'    => $this->_route,
            'TIME'     => round($etime - $stime, 4)
        ]);
        return $msg->body;
    }

    /*****************************************************************************
     * ack -- 手动应答
     *
     *
     * 输入 : 1个
     * @param object $message
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    public function ack(object $message)
    {
        $tag = $message->delivery_info['delivery_tag'];
        $message->delivery_info['channel']->basic_ack($tag);
    }

    /*****************************************************************************
     * add -- 写入队列
     *
     *
     * 输入 : 3个
     * @param string|array $data
     * @param bool $batch
     * @param bool|int $delayed
     *
     * 输出 : @return bool|Rmq
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    public function add(string|array $data,
                        bool $batch = FALSE,
                        bool|int $delayed = FALSE): bool|Rmq
    {
        $stime = microtime(TRUE);
        $log = [
            'TYPE'     => 'add',
            'EXCHANGE' => $this->_exchange,
            'QUEUE'    => $this->_queue,
            'ROUTE'    => $this->_route,
            'DATA'     => $data
        ];
        // 数组转化
        if (is_array($data))
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        $msg = new AMQPMessage($data, [
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ]);
        // 是否需要延迟
        if ($delayed !== FALSE)
        {
            $headers = new AMQPTable([
                'x-delay' => $delayed * 1000
            ]);
            $msg->set('application_headers', $headers);
        }
        // 批量写入
        if ($batch)
        {
            $this->_channel->batch_basic_publish($msg, $this->_exchange, $this->_route);
            $etime = microtime(TRUE);
            $log['TIME']  = round($etime - $stime, 4);
            $log['BATCH'] = 1;
            Log::add('RMQ', $log);
            return $this;
        }
        else
        {
            $this->_channel->basic_publish($msg, $this->_exchange, $this->_route);
            $etime = microtime(TRUE);
            $log['TIME']  = round($etime - $stime, 4);
            $log['BATCH'] = 0;
            Log::add('RMQ', $log);
            return TRUE;
        }
    }

    /*****************************************************************************
     * publish -- 提交到队列
     *
     *
     * 输入 : Nothing
     *
     * 输出 : @return bool
     *
     * 历史 :
     *     2021/10/12 : created
     *****************************************************************************/
    public function publish(): bool
    {
        $this->_channel->publish_batch();
        return TRUE;
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
        $conf = conf('RMQ');
        if ( ! $conf) fail(9032);
        if ( ! isset($conf['HOST']) || ! $conf['HOST'])
        {
            fail(9033, [
                'config' => 'HOST'
            ]);
        }
        if ( ! isset($conf['USERNAME']) || ! $conf['USERNAME'])
        {
            fail(9033, [
                'config' => 'USERNAME'
            ]);
        }
        if ( ! isset($conf['PASSWORD']) || ! $conf['PASSWORD'])
        {
            fail(9033, [
                'config' => 'PASSWORD'
            ]);
        }
        if ( ! isset($conf['EXCHANGE']) || ! $conf['EXCHANGE'])
        {
            fail(9033, [
                'config' => 'EXCHANGE'
            ]);
        }
        if ( ! isset($conf['QUEUE']) || ! $conf['QUEUE'])
        {
            fail(9033, [
                'config' => 'QUEUE'
            ]);
        }
        $conf['PORT']  = $conf['PORT'] ?? 5672;
        $conf['VHOST'] = $conf['VHOST'] ?? '/';
        $conf['QOS']   = ( ! isset($conf['QOS'])) ? 0 : $conf['QOS'];
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
        try {
            $this->_conn = new AMQPStreamConnection(
                $this->_conf['HOST'],
                $this->_conf['PORT'],
                $this->_conf['USERNAME'],
                $this->_conf['PASSWORD'],
                $this->_conf['VHOST'],
                [
                    'read_write_timeout' => 3000,
                    'heartbeat' => 1000
                ]
            );
            $this->_channel = $this->_conn->channel();
            $this->_channel->basic_qos(0, $this->_conf['QOS'], FALSE);
            $etime = microtime(TRUE);
            Log::add('RMQ', [
                'CONNECTION' => round($etime - $stime, 4)
            ]);
        } catch(Exception){
            fail(9031);
        }
    }

}