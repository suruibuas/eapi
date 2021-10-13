<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : Filter.class.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/7
 *
 *                       更新 :
 *
 *                       说明 : 过滤器类库
 *
 *********************************************************************************
 * Functions:
 *      check   :   检查是否存在敏感词
 *      badWord :   获取敏感词
 *      replace :   过滤敏感词
 *      mark    :   标记敏感词
 *********************************************************************************/

namespace eapi\lib;
use DfaFilter\Exceptions\PdsBusinessException;
use DfaFilter\Exceptions\PdsSystemException;
use DfaFilter\SensitiveHelper;

class Filter{

    // 句柄
    private null|object $_init;

    public function __construct()
    {
        if ( ! is_file(_FILTER_TXT)) fail(1101);
        try {
            $this->_init = SensitiveHelper::init()->setTreeByFile(_FILTER_TXT);
        } catch (PdsBusinessException) {
        }
    }

    /*****************************************************************************
     * check -- 检测是否存在敏感词
     *
     *
     * 输入 : 1个
     * @param string $content
     *
     * 输出 : @return bool
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function check(string $content): bool
    {
        if ($content == '') return FALSE;
        try {
            return $this->_init->islegal($content);
        } catch (PdsSystemException) {
            return FALSE;
        }
    }

    /*****************************************************************************
     * badWord -- 获取敏感词
     *
     *
     * 输入 : 1个
     * @param string $content
     *
     * 输出 : @return array
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function badWord(string $content): array
    {
        if ($content == '')
            return [];
        try {
            return $this->_init->getBadWord($content);
        } catch (PdsSystemException) {
            return [];
        }
    }

    /*****************************************************************************
     * replace -- 过滤替换敏感词
     *
     *
     * 输入 : 3个
     * @param $content
     * @param string $replace
     * @param bool $samelen
     *
     * 输出 : @return string
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function replace($content,
                            string $replace = '*',
                            bool $samelen = TRUE): string
    {
        if ($content == '') return $content;
        try {
            return ($samelen === TRUE)
                        ? $this->_init->replace($content, $replace, $samelen)
                        : $this->_init->replace($content, $replace);
        } catch (PdsBusinessException | PdsSystemException) {
            return '';
        }
    }

    /*****************************************************************************
     * mark -- 标记敏感词
     *
     *
     * 输入 : 3个
     * @param $content
     * @param string $begin
     * @param string $end
     *
     * 输出 : @return string
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function mark($content,
                         string $begin = '<mark>',
                         string $end = '</mark>'): string
    {
        if ($content == '') return $content;
        try {
            return $this->_init->mark($content, $begin, $end);
        } catch (PdsBusinessException | PdsSystemException) {
            return '';
        }
    }

}