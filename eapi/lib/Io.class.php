<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : Io.class.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/7
 *
 *                       更新 :
 *
 *                       说明 : 输入输出类
 *
 *********************************************************************************
 * Functions:
 *      post    :   获取POST参数
 *      get     :   获取GET参数
 *      header  :   获取HEADER参数
 *      out     :   输出
 *********************************************************************************/

namespace eapi\lib;

use api\Log;
use SeasLog;

class Io{

    /*****************************************************************************
     * post -- 获取POST参数
     *
     *
     * 输入 : 2个
     * @param string $key
     * @param bool $default
     *
     * 输出 : @return mixed
     *
     * 历史 :
     *     2021/10/7 : created
     ***************************************************************************
     */
    public function post(string $key = '', string|bool $default = FALSE): mixed
    {
        return $this->_input($key, $default, $_POST);
    }

    /*****************************************************************************
     * get -- 获取GET参数
     *
     *
     * 输入 : 2个
     * @param string $key
     * @param bool $default
     *
     * 输出 : @return mixed
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function get(string $key = '', string|bool $default = FALSE): mixed
    {
        return $this->_input($key, $default, $_GET);
    }

    /*****************************************************************************
     * header -- 获取HEADER参数
     *
     *
     * 输入 : 2个
     * @param string $key
     * @param bool $default
     *
     * 输出 : @return string|array|bool
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function header(string $key = '', string|bool $default = FALSE): string|array|bool
    {
        // 如果KEY为空，则返回所有
        if ($key == '')
        {
            $headers = [];
            // 黑名单字段，不返回以下HEADER头信息
            $black   = [
                'COOKIE', 'ACCEPT_LANGUAGE', 'ACCEPT_ENCODING', 'ORIGIN', 'ACCEPT',
                'CONTENT_TYPE', 'USER_AGENT', 'CONTENT_LENGTH', 'CONNECTION', 'HOST',
                'X_REQUESTED_WITH', 'REFERER', 'CACHE_CONTROL', 'PRAGMA', 'REMOTE_HOST',
                'X_FORWARDED_FOR', 'X_REAL_IP'
            ];
            foreach ($_SERVER as $name => $value)
            {
                if ( ! str_starts_with($name, 'HTTP')) continue;
                $name = str_replace('HTTP_', '', $name);
                if (in_array($name, $black)) continue;
                $headers[$name] = $value;
            }
            return $headers;
        }
        $key = 'HTTP_'.strtoupper($key);
        if ( ! isset($_SERVER[$key]))
            return ($default !== FALSE) ? $default : FALSE;
        return trim($_SERVER[$key]);
    }

    /*****************************************************************************
     * out -- 输出
     *
     *
     * 输入 : 1个
     * @param array $content
     *
     * 输出 : @return bool
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function out(array $content = []): bool
    {
        $data['code'] = $content['code'] ?? 100;
        $data['msg']  = $content['msg'] ?? '执行成功';
        $data['data'] = $content['data'] ?? [];
        // 记录通用信息日志
        Log::set('COMMON', [
            'URI'    => $GLOBALS['_RUNTIME']['URI'],
            'METHOD' => _METHOD,
            'CLIENT' => _CLIENT,
            'IP'     => _IP,
            'TIME'   => time(),
            'TOTAL'  => round(microtime(TRUE) - $GLOBALS['_RUNTIME']['MICROTIME'], 4)
        ]);
        if ($data['code'] != 100)
        {
            Log::set('ERROR', [
                'CODE' => $data['code'],
                'MSG'  => $data['msg']
            ]);
        }
        // 如果开启LOG支持则返回日志
        if (_LOG) $data['log'] = Log::info();
        // 保存日志
        if (_SAVELOG) Log::save(Log::info(), 'api');
        // 推送日志
        if (_PUSHLOG && _CLIENT != 'UNKNOW') SeasLog::info(json_encode(Log::info()));
        header('Content-type: application/json');
        // 发送状态码
        if ( ! headers_sent())
            http_response_code(200);
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        return TRUE;
    }

    /*****************************************************************************
     * _replace -- 执行输入过滤
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    private function _replace($val, $conf)
    {
        // 过滤配置
        $rules = explode('|', $conf);
        foreach ($rules as $rule)
        {
            $val = $rule == 'escape'
                    ? $this->_escape($val)
                    : $this->_xssClear($val);
        }
        return $val;
    }

    /*****************************************************************************
     * _escape -- 字符转义
     *
     *
     * 输入 : Nothing
     *
     * 输出 : @return string
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    private function _escape($val): string
    {
        $val = stripslashes(addslashes($val));
        return strip_tags($val);
    }

    /*****************************************************************************
     * _xssClear -- XSS注入清理
     *
     *
     * 输入 : Nothing
     *
     * 输出 : @return array|string
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    private function _xssClear($val): array|string
    {
        $val     = rawurldecode($val);
        $search  = 'abcdefghijklmnopqrstuvwxyz';
        $search .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $search .= '1234567890!@#$%^&*()';
        $search .= '~`";:?+/={}[]-_|\'\\';
        for ($i = 0; $i < strlen($search); $i++)
        {
            $val = preg_replace('/(&#[xX]0{0,8}'.dechex(ord($search[$i])).';?)/i', $search[$i], $val);
            $val = preg_replace('/(&#0{0,8}'.ord($search[$i]).';?)/', $search[$i], $val);
        }
        // 屏蔽危险DOM
        $dom = [
            'javascript', 'vbscript', 'expression', 'applet', 'meta',
            'xml', 'blink', 'link', 'script', 'embed',
            'object', 'iframe', 'frame', 'frameset', 'ilayer',
            'layer', 'bgsound', 'base'
        ];
        foreach ($dom as $d)
            $val = preg_replace('/(<|&lt;)+'.$d.'(.*)(>|&gt;)+(.*)(<|&lt;)+\/'.$d.'(>|&gt;)+/', '', $val);
        // 屏蔽事件
        $event = [
            'onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate',
            'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste',
            'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce',
            'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect',
            'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete',
            'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter',
            'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror',
            'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin',
            'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup',
            'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter',
            'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup',
            'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste',
            'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend',
            'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted',
            'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart',
            'onstop', 'onsubmit', 'onunload'
        ];
        $val = preg_replace('/'.implode('|', $event).'/is', '', $val);
        // 字符串黑名单
        $black = [
            'document.cookie' => '',
            'document.write'  => '',
            '.parentNode'     => '',
            '.innerHTML'      => '',
            'window.location.href' => '',
            'location.href'   => '',
            '-moz-binding'    => '',
            'alert'           => '',
            '<!--'            => '&lt;!--',
            '-->'             => '--&gt;',
            '<![CDATA['       => '&lt;![CDATA[',
            '<comment>'       => '&lt;comment&gt;',
        ];
        // 替换黑名单字符串
        return str_replace(array_keys($black), array_values($black), $val);
    }

    /*****************************************************************************
     * _input -- 通用获取输入（GET或者POST）
     *
     *
     * 输入 : 3个
     * @param string $key
     * @param bool|string $default
     * @param array $input
     *
     * 输出 : @return mixed
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    private function _input(string $key, bool|string $default, array $input): mixed
    {
        // 如果没有设置KEY，则返回所有POST值
        if ($key == '')
        {
            $data = $input;
        }
        else
        {
            if ( ! isset($input[$key]))
                return ($default !== FALSE) ? $default : FALSE;
            $data = $input[$key];
        }
        // 如果是整型或者是空则直接返回
        if (is_numeric($data) || empty($data))
            return is_numeric($data) ? abs($data) : $data;
        // 如果是数组则遍历
        if (is_array($data))
        {
            $tmp = [];
            foreach ($data as $val)
                $tmp[] = (is_int($val) || empty($val)) ? $val : $this->_replace(trim($val), conf('INPUTFILTER'));
            return $tmp;
        }
        else
        {
            return $this->_replace(trim($data), conf('INPUTFILTER'));
        }
    }

}