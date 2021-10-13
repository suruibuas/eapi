<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : Jweixin.class.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/7
 *
 *                       更新 :
 *
 *                       说明 : 生成微信JSSDK
 *
 *********************************************************************************
 * Functions:
 *
 *********************************************************************************/

namespace eapi\lib;

class Jweixin{

    /*****************************************************************************
     * run -- 生成数据
     *
     *
     * 输入 : 2个
     * @param array $param
     * @param bool $accessToken
     *
     * 输出 : @return mixed
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function run(array $param = [], bool $accessToken = FALSE): mixed
    {
        $ticket = $this->_createTicket($param, $accessToken);
        if ($accessToken) return $ticket;
        $return = [
            'appid'   => $param['appid'],
            'nonce'   => random('num', 6),
            'curtime' => time()
        ];
        $tmp = [
            'noncestr'     => $return['nonce'],
            'timestamp'    => $return['curtime'],
            'jsapi_ticket' => $ticket,
            'url'          => $param['url']
        ];
        ksort($tmp, SORT_STRING);
        $str = $doc = '';
        foreach ($tmp as $key => $val)
        {
            $str .= $doc.$key.'='.$val;
            $doc  = '&';
        }
        $sign = sha1($str);
        $return['sign'] = $sign;
        return $return;
    }

    /*****************************************************************************
     * _createTicket -- 生成票据
     *
     *
     * 输入 : 2个
     * @param array $param
     * @param bool $token
     *
     * 输出 : @return mixed
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    private function _createTicket(array $param = [], bool $token = FALSE): mixed
    {
        $ticket = cache('ticket');
        if ($ticket != FALSE) return $ticket;
        $key = $token ? 'accessTokenRemind' : 'accessToken';
        $accessToken = cache($key);
        if ( ! $accessToken)
        {
            $api  = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid='.$param['appid'].'&secret='.$param['secret'];
            $data = \api\Http::get($api);
            $accessToken = $data['access_token'];
            cache($key, $accessToken, 600);
        }
        if ($token) return $accessToken;
        $api  = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token='.$accessToken.'&type=jsapi';
        $data = \api\Http::get($api);
        cache('ticket', $data['ticket'], 610);
        return $data['ticket'];
    }

}