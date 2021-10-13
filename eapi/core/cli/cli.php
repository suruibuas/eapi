<?php

/*********************************************************************************
 ***                     CONFIDENTIAL  ---  SURUI STUDIOS                      ***
 *********************************************************************************
 *
 *                       项目 : EApi
 *
 *                       文件 : model.php
 *
 *                       开发 : 苏睿 / 317953536@qq.com
 *
 *                       开始 : 2021/10/8
 *
 *                       更新 :
 *
 *                       说明 : 命令行工具 - 创建模型
 *
 *********************************************************************************
 * Functions:
 *
 *********************************************************************************/

namespace eapi;

use api\Mysql;

class Cli {

    /*****************************************************************************
     * model -- 创建模型文件
     *
     *
     * 输入 : Nothing
     *
     * 输出 : Nothing
     *
     * 历史 :
     *     2021/10/8 : created
     *****************************************************************************/
    public function createModel()
    {
        $conf = conf('MYSQL');
        $sql  = 'SELECT 
                    `table_name` 
                FROM 
                     information_schema.`tables` 
                WHERE
                      table_schema = "' . $conf['DATABASE'] . '"';
        $data = Mysql::query($sql)->all();
        if (empty($data)) fail(2006);
        // 模型存放路径
        $path = _WORKPATH . '/' . _APP.'/model';
        foreach ($data as $row)
        {
            $table = str_replace($conf['PREFIX'], '', $row['TABLE_NAME']);
            $file  = $path . '/' . ucfirst($table) . '.php';
            if (is_file($file)) continue;
            $content  = "<?php\r\n\r\n";
            $content .= "namespace model;\r\n";
            $content .= "use eapi\Model;\r\n\r\n";
            $content .= "class $table extends Model{\r\n\r\n";
            $content .= "    public function __construct()\r\n";
            $content .= "    {\r\n";
            $content .= "        parent::__construct();\r\n";
            $content .= "        // 数据表名，不包括表前缀\r\n";
            $content .= "        \$this->table      = '" . $table . "';\r\n";
            $content .= "        // 主键，默认为id\r\n";
            $content .= "        \$this->pk         = 'id';\r\n";
            $content .= "        // 是否自动打时间标记\r\n";
            $content .= "        \$this->timestamps = FALSE;\r\n";
            $content .= "        // 添加时间字段名\r\n";
            $content .= "        \$this->createAt   = 'create_at';\r\n";
            $content .= "        // 更新时间字段名\r\n";
            $content .= "        \$this->updateAt   = 'update_at';\r\n";
            $content .= "    }\r\n\r\n";
            $content .= "}";
            // 生成模型文件
            file_put_contents($file, $content);
        }
    }

}