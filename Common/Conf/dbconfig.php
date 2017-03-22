<?php
//数据库配置
$arr1 = array(
    'DB_TYPE'           => 'mysql',     // 数据库类型
    'DB_HOST'           => '121.42.53.24', // 服务器地址
    'DB_NAME'           => 'db_app',  // 数据库名
    'DB_USER'           => 'app',      // 用户名
    'DB_PWD'            => 'app',     // 密码
    'DB_PORT'           => '3306',      // 端口
    'DB_PREFIX'         => '',        // 表前缀
    'DB_CHARSET'        => 'utf8',      // 字符集
);

$arr2 = array(
	//File
	'DATA_CACHE_TYPE'                   => 'File', // 数据缓存类型
	'DATA_CACHE_TIME'                   => 5,      // 数据缓存有效期 0表示永久缓存
	'DATA_CACHE_PREFIX'                   => '',   // 缓存前缀
);

return array_merge($arr1,$arr2);//数据库与应用同在本机