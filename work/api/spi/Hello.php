<?PHP

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : Hello.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/7
 *
 *                       更新 :
 *
 *                       说明 : 演示控制器
 * 
 *********************************************************************************
 * Functions:
 *
 *********************************************************************************/

declare (strict_types = 1);

namespace spi;

use \api\Mcq;

class Hello extends Init{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * ***************************************************************************
     *  --
     *
     * 说明 :
     *
     *
     * 前置 ：
     *
     * 后置 :
     *
     * 历史 :
     *     2021/10/11 : created
     * ***************************************************************************
     */
    public function index()
    {
        json();
    }

}
