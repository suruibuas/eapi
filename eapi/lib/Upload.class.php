<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : Upload.class.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/7
 *
 *                       更新 :
 *
 *                       说明 : UPLOAD类库
 *
 *********************************************************************************
 * Functions:
 *      local   :   文件保存本地
 *********************************************************************************/

namespace eapi\lib;

class Upload{

    /*****************************************************************************
     * local -- 将图片保存到本地
     *
     *
     * 输入 : 2个
     * @param mixed $img
     *
     * 输出 : @return string|array
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    public function local(mixed $img): string|array
    {
        $path = '/' . date('Ymd') . '/';
        // file上传模式
        if (is_array($img))
        {
            $input = $img['input'];
            if ( ! isset($_FILES[$input])) fail(1030, '上传文件不能为空');
            $file  = $_FILES[$input];
            if ( ! isset($file['name'])) fail(1030, '上传文件不能为空');
            if ( ! is_dir(_UPLOAD . $path)) mkdir(_UPLOAD . $path);
            // 单文件
            if ( ! is_array($file['name']))
            {
                $size = $file['size'];
                if ($size == 0) fail(1031, '文件大小不能为0');
                // 判断文件大小
                if ($size > conf('UPLOAD_SIZE') * 1024 * 1024) fail(1032, '文件大小超出限制');
                //截取后缀
                $doc = $this->_getDoc($file['name']);
                //判断是否允许的格式
                if ( ! in_array($doc, conf('UPLOAD'))) fail(1033, '文件格式不允许');
                $localFile = $path . md5(microtime()) . '.' . $doc;
                //上传
                if ( ! move_uploaded_file($file['tmp_name'], _UPLOAD . $localFile)) fail(1034, '文件上传失败');
                return $localFile;
            }
            $img = [];
            foreach ($file['name'] as $key => $val)
            {
                $size = $file['size'][$key];
                if ($size == 0) continue;
                // 判断文件大小
                if ($size > conf('UPLOAD_SIZE') * 1024 * 1024) continue;
                //截取后缀
                $doc = $this->_getDoc($val);
                //判断是否允许的格式
                if ( ! in_array($doc, conf('UPLOAD'))) continue;
                $localFile = $path . md5(microtime()) . '.' . $doc;
                //上传
                if ( ! move_uploaded_file($file['tmp_name'][$key], _UPLOAD . $localFile)) continue;
                array_push($img, $localFile);
            }
            return $img;
        }
        // base64图片保存
        if (preg_match('#^data:\s*image/(\w+);base64,#U', $img,$data))
        {
            // 判断文件格式
            if ( ! in_array($data[1], conf('UPLOAD'))) fail(1026);
            // 获取文件大小
            $content  = preg_replace('#^data:\s*image/(\w+);base64,#U', '', $img);
            $content  = str_replace('=', '', $content);
            $len      = strlen($content);
            $fileSize = floor(($len - ($len / 8) * 2) / 1024);
            // 判断文件大小
            if ($fileSize > conf('UPLOAD_SIZE'))
            {
                fail(1027, [
                    'size' => conf('UPLOAD_SIZE')
                ]);
            }
            $doc = strtolower($data[1] == 'jpeg' ? 'jpg' : $data[1]);
            if ( ! is_dir(_UPLOAD . $path)) mkdir(_UPLOAD . $path);
            $localFile = $path . md5(microtime()) . '.' . $doc;
            // 保存文件
            file_put_contents(_UPLOAD . $localFile, base64_decode($content));
            return $localFile;
        }
        else
        {
            fail(1025);
        }
        return '';
    }

    /*****************************************************************************
     * _getDoc -- 获取文件后缀名
     *
     *
     * 输入 : Nothing
     *
     * 输出 : @return string
     *
     * 历史 :
     *     2021/10/7 : created
     *****************************************************************************/
    private function _getDoc(string $name): string
    {
        return substr($name, strrpos($name, '.') + 1, strlen($name));
    }

}