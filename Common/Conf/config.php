<?php
$config = array(
	//'配置项'=>'配置值'
	
    'DEFAULT_MODULE'     => 'Admin', // 默认模块
    'APP_USE_NAMESPACE' => false, // 应用类库不再使用命名空间
    'URL_CASE_INSENSITIVE' =>true, // URL忽略大小写
    
    /*
     * 0:普通模式 (采用传统癿URL参数模式 )
     * 1:PATHINFO模式(http://<serverName>/appName/module/action/id/1/)
     * 2:REWRITE模式(PATHINFO模式基础上隐藏index.php)
     * 3:兼容模式(普通模式和PATHINFO模式, 可以支持任何的运行环境, 如果你的环境不支持PATHINFO 请设置为3)
     */
    'URL_MODEL'         => 2, // 如果环境不支持PATHINFO 请设置为3
    
   
);

//数据库配置
$dbConfig = require_once './Common/Conf/dbconfig.php';

return array_merge($config, $dbConfig);