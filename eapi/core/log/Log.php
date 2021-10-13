<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : Log.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/7
 *
 *                       更新 :
 *
 *                       说明 : 日志操作类
 * 
 *********************************************************************************
 * Functions:
 *      save    :   保存日志
 *      add     :   追加日志
 *      info    :   输出日志
 *********************************************************************************/

namespace eapi;

class Log{

    /*****************************************************************************
     * save -- 保存日志
     *
     *
     * 输入 : 2个
     * @param string|array $content     日志数据
     * @param string $path              日志保存目录
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function save(string|array $content, string $path = 'default')
    {
        // 如果日志内容是数组则转JSON存储
        $content = is_array($content) ? json_encode($content) : $content;
        $path = _LOGPATH . '/' . $path;
        if ( ! is_dir($path)) mkdir($path);
        $file  = $path.'/'.date('Ymd').'.log';
        $data  = '日志写入时间：'.date('Y-m-d H:i:s')."\r\n";
        $data .= $content;
        $data .= "\r\n=====================================\r\n";
        file_put_contents($file, $data, FILE_APPEND);
    }

    /*****************************************************************************
     * set -- 写入日志
     *
     *
     * 输入 : 2个
     * @param string $name  KEY
     * @param array $data   内容
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function set(string $name, array $data)
    {
        if ( ! isset($GLOBALS['_LOGS'][$name]))
            $GLOBALS['_LOGS'][$name] = $data;
    }

    /*****************************************************************************
     * add -- 追加日志
     *
     *
     * 输入 : 2个
     * @param string $name  KEY
     * @param array $data   内容
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function add(string $name, array $data)
    {
        if ( ! isset($GLOBALS['_LOGS'][$name]))
            $GLOBALS['_LOGS'][$name] = [];
        array_push($GLOBALS['_LOGS'][$name], $data);
    }

    /*****************************************************************************
     * info -- 输出LOG
     *
     *
     * 输入 : 1个
     * @param string $name  KEY
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function info(string $name = '')
    {
        return $name == '' ? $GLOBALS['_LOGS'] : $GLOBALS['_LOGS'][$name];
    }

}