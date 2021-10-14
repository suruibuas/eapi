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
 *                       开始 : 2021/10/7
 *
 *                       更新 :
 *
 *                       说明 : HTTP操作类
 *
 *********************************************************************************
 * Functions:
 *      get         :   发送GET请求
 *      post        :   发送POST请求
 *      put         :   发送PUT请求
 *      delete      :   发送DELETE请求
 *      stream      :   发送STREAM请求
 *      download    :   下载文件
 *      add         :   添加并发任务
 *      run         :   执行并发任务
 *
 *********************************************************************************/

namespace eapi;
use api\Log;
use Ares333\Curl\Toolkit;
use Exception;

final class Http{

    // 句柄
    private mixed $_client;
    // 请求头
    private array $_header;
    // 最大超时时间
    private int $_timeOut;
    // 并发请求地址
    private array $_multi;

    public function __construct()
    {
        $this->_client = new Toolkit();
        $this->_client->setCurl();
        $this->_client = $this->_client->getCurl();
        $this->_client->onInfo = null;
        // 设置最大并发数
        $this->_client->maxThread = conf('HTTP_MAXTHREAD');
        // 初始化HEADER头信息
        $this->_header  = conf('HTTP_HEADER');
        // 初始化最大超时时间
        $this->_timeOut = conf('HTTP_TIMEOUT');
    }

    /*****************************************************************************
     * get -- 提供GET操作
     *
     *
     * 输入 : 2个
     * @param string $url       请求地址
     * @param array $params     请求参数
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function get(string $url, array $params = []): array|string
    {
        // 准备必要参数
        [
            'baseurl'  => $url,
            'timeout'  => $timeOut,
            'header'   => $header,
            'response' => $response
        ] = $this->_prepare($url, $params);
        // 发送请求
        $this->_client->add(['opt' => [
            CURLOPT_URL            => $url,
            CURLOPT_TIMEOUT        => $timeOut,
            CURLOPT_HTTPHEADER     => $header,
            CURLOPT_CUSTOMREQUEST  => 'GET',
            CURLOPT_FOLLOWLOCATION => FALSE,
        ]], function($result) use (&$response, $header){
            // 保存HTTP请求返回值
            $response = $this->_response($result['body']);
            // 记录HTTP请求日志
            Log::add('HTTP', [
                'URL'    => $result['info']['url'],
                'TIME'   => round($result['info']['total_time'] * 1000),
                'METHOD' => 'GET',
                'CODE'   => $result['info']['http_code'],
                'SIZE'   => $result['info']['size_download'],
                'HEADER' => $header
            ]);
        }, function($result) use (&$response){
            // 错误
            $response = $this->_response($result);
        })->start();
        // 重置HEADER头
        $this->_header = [];
        // 返回数据
        return $response;
    }

    /*****************************************************************************
     * post -- 提供POST操作
     *
     *
     * 输入 : 4个
     * @param string $url           请求地址
     * @param array|string $data    POST提交的参数，数组格式
     * @param array $params         参数
     * @param string $method        请求类型，默认POST
     *
     * 输出 : @return array|string
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function post(string $url,
                         array|string $data = [],
                         array $params = [],
                         string $method = 'POST'): array|string
    {
        // 准备必要参数
        [
            'baseurl'  => $url,
            'timeout'  => $timeOut,
            'header'   => $header,
            'response' => $response
        ] = $this->_prepare($url, $params);
        // POST参数
        $post = (is_array($data)) ? http_build_query($data) : $data;
        // 发送请求
        $this->_client->add(['opt' => [
            CURLOPT_URL            => $url,
            CURLOPT_TIMEOUT        => $timeOut,
            CURLOPT_HTTPHEADER     => $header,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_FOLLOWLOCATION => FALSE,
            CURLOPT_POST           => TRUE,
            CURLOPT_POSTFIELDS     => $post
        ]], function($result) use (&$response, $header, $data, $method){
            if (is_array($data))
            {
                // 格式化POST提交的数据
                foreach ($data as $key => $val)
                    if (strlen($val) > 255) $data[$key] = mb_substr($val, 0, 255, 'utf8');
            }
            // 保存HTTP请求返回值
            $response = $this->_response($result['body']);
            // 记录HTTP请求日志
            Log::add('HTTP', [
                'URL'    => $result['info']['url'],
                'TIME'   => round($result['info']['total_time'] * 1000),
                'METHOD' => $method,
                'CODE'   => $result['info']['http_code'],
                'SIZE'   => $result['info']['size_download'],
                'DATA'   => $data,
                'HEADER' => $header
            ]);
        }, function($result) use (&$response){
            // 错误
            $response = $this->_response($result);
        })->start();
        // 重置HEADER头
        $this->_header = [];
        // 返回数据
        return $response;
    }

    /*****************************************************************************
     * put -- 发送PUT请求
     *
     *
     * 输入 : 3个
     * @param string $url           请求地址
     * @param array|string $data    POST提交的参数，数组格式
     * @param array $params         参数
     *
     * 输出 : @return array|string
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function put(string $url,
                        array|string $data = [],
                        array $params = []): array|string
    {
        return $this->post($url, $data, $params, 'PUT');
    }

    /*****************************************************************************
     * delete -- 发送DELETE请求
     *
     *
     * 输入 : 3个
     * @param string $url           请求地址
     * @param array|string $data    POST提交的参数，数组格式
     * @param array $params         参数
     *
     * 输出 : @return array|string
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function delete(string $url,
                           array|string $data = [],
                           array $params = []): array|string
    {
        return $this->post($url, $data, $params, 'DELETE');
    }

    /*****************************************************************************
     * stream -- 发送流数据
     *
     *
     * 输入 : 3个
     * @param string $url       请求地址
     * @param string $data      数据流内容
     * @param array $params     参数
     *
     * 输出 : @return array|string
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function stream(string $url,
                           string $data = '',
                           array $params = []): array|string
    {
        // 准备必要参数
        [
            'baseurl'  => $url,
            'timeout'  => $timeOut,
            'header'   => $header,
            'response' => $response
        ] = $this->_prepare($url, $params);
        // 构建流数据
        $stream = fopen('php://temp','r+');
        fwrite($stream, $data);
        $length = ftell($stream);
        rewind($stream);
        // 发送请求
        $this->_client->add(['opt' => [
            CURLOPT_URL            => $url,
            CURLOPT_TIMEOUT        => $timeOut,
            CURLOPT_HTTPHEADER     => $header,
            CURLOPT_CUSTOMREQUEST  => 'POST',
            CURLOPT_INFILE         => $stream,
            CURLOPT_FOLLOWLOCATION => FALSE,
            CURLOPT_INFILESIZE     => $length,
            CURLOPT_UPLOAD         => 1
        ]], function($result) use (&$response, $header){
            $response = $this->_response($result['body']);
        }, function($result) use (&$response){
            $response = $this->_response($result);
        })->start();
        // 重置header头
        $this->_header = [];
        // 返回数据
        return $response;
    }

    /*****************************************************************************
     * download -- 下载文件
     *
     *
     * 输入 : 2个
     * @param string $url   下载文件地址
     * @param string $file  本地保存文件路径
     *
     * 输出 : @return string
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function download(string $url, string $file): string
    {
        $fp = fopen($file, 'w');
        // 初始化结果
        $response = [];
        $this->_client->add([
            'opt' => [
                CURLOPT_URL    => $url,
                CURLOPT_FILE   => $fp,
                CURLOPT_HEADER => FALSE
            ],
            'args' => [
                'file' => $file
            ]
        ], function($result) use(&$response){
            $response = $result['info']['http_code'];
        })->start();
        return $response;
    }

    /*****************************************************************************
     * add -- 添加并发任务
     *
     *
     * 输入 : 2个
     * @param string $key
     * @param array $params
     *
     * 输出 : @return Http
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function add(string $key, array $params = []): Http
    {
        if ( ! isset($params)) fail(6001);
        $this->_multi[$key] = [
            'url'     => $params['url'],
            'query'   => $params['query'] ?? [],
            'post'    => $params['post'] ?? [],
            'timeout' => $params['timeout'] ?? $this->_timeOut,
            'header'  => $params['header'] ?? []
        ];
        return $this;
    }

    /*****************************************************************************
     * run -- 执行并发任务
     *
     *
     * 输入 : Nothing
     *
     * 输出 : @return array
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function run(): array
    {
        if (empty($this->_multi)) fail(6003);
        if (count($this->_multi) == 1) fail(6004);
        $response = [];
        foreach ($this->_multi as $key => $row)
        {
            $header = $this->_setHeader($row['header']);
            $method = (isset($row['post']) && ! empty($row['post'])) ? 'POST' : 'GET';
            $opt = [];
            $opt['opt'] = [
                CURLOPT_URL            => $this->_baseUrl($row['url'], $row['query']),
                CURLOPT_TIMEOUT        => $row['timeout'],
                CURLOPT_FOLLOWLOCATION => FALSE,
                CURLOPT_HTTPHEADER     => $header,
                CURLOPT_CUSTOMREQUEST  => $method
            ];
            $post = [];
            if ($method == 'POST')
            {
                $opt['opt'][CURLOPT_POST] = TRUE;
                $opt['opt'][CURLOPT_POSTFIELDS] = $row['post'];
                $post = $row['post'];
            }
            $this->_client->add($opt,
            function($result) use (&$response, $key, $header, $method, $post){
                $response[$key] = $this->_response($result['body']);
                $log = [
                    'URL'    => $result['info']['url'],
                    'TIME'   => round($result['info']['total_time'] / 1000000, 4),
                    'METHOD' => $method,
                    'CODE'   => $result['info']['http_code'],
                    'SIZE'   => $result['info']['size_download'],
                    'HEADER' => $header,
                    'MULTI'  => 1
                ];
                if ($method == 'POST') $log['DATA'] = $post;
                // 记录HTTP操作日志
                Log::add('HTTP', $log);
            },
            function($result){
                $this->_response($result);
            });
        }
        $this->_client->start();
        $this->_multi = [];
        return $response;
    }

    /*****************************************************************************
     * _setHeader -- 设置HEADER头
     *
     *
     * 输入 : 1个
     * @param array $header 待设置的HEADER头数据
     *
     * 输出 : @return array
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    private function _setHeader(array $header = []): array
    {
        $arr = $this->_header;
        if ( ! empty($header))
            $arr = array_merge($arr, $header);
        $tmp = [];
        foreach ($arr as $k => $v)
            $tmp[] = $k.':'.$v;
        return $tmp;
    }

    /*****************************************************************************
     * _baseUrl -- 处理根域名
     *
     *
     * 输入 : 2个
     * @param string $url   URL地址
     * @param array $query  QUERY参数
     *
     * 输出 : @return string
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    private function _baseUrl(string $url = '', array $query = []): string
    {
        // 解析参数
        if ( ! empty($query))
        {
            $doc = str_contains($url, '?') ? '&' : '?';
            foreach($query as $k => $v)
            {
                $url .= $doc . $k . '=' . $v;
                $doc = '&';
            }
        }
        return $url;
    }

    /*****************************************************************************
     * _prepare -- 开始前的准备工作
     *
     *
     * 输入 : 1个
     * @param string $url
     * @param array $params
     *
     * 输出 : @return array
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    private function _prepare(string $url, array $params): array
    {
        if ($url == '') fail(6001);
        return [
            // 处理BASE_URL
            'baseurl'  => $this->_baseUrl($url, $params['query'] ?? []),
            // 超时时间
            'timeout'  => $params['timeout'] ?? $this->_timeOut,
            // 自定义HEADER头
            'header'   => $this->_setHeader($params['header'] ?? []),
            // 返回结果
            'response' => []
        ];
    }

    /*****************************************************************************
     * _response -- 输出结果
     *
     *
     * 输入 : 1个
     * @param mixed $response
     *
     * 输出 : @return array
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    private function _response(mixed $response): array
    {
        // 如果是数组则直接返回不做处理
        if (is_array($response)) return $response;
        // 尝试解析json
        try{
            $data = jsonDecode($response);
        } catch (Exception) {
            $data = [];
            $data['code'] = 100;
            $data['msg']  = '不是标准的JSON数据，已原样输出';
            $data['data'] = $response;
        }
        return $data;
    }

}