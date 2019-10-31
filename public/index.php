<?php
// +----------------------------------------------------------------------
// | EfarHost [ Easy Far And Easy More ... ]
// +----------------------------------------------------------------------
// | Copyright (c) 2012~2018 http://efarhost.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 琴瑟琵琶 <efarhost@foxmail.com>
// +----------------------------------------------------------------------
// | [应用入口文件]
// +----------------------------------------------------------------------
namespace think;
if(version_compare(PHP_VERSION,'7.0','<'))  die('require PHP > 7.0 ! your php vision:'.PHP_VERSION);
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Methods:OPTIONS, GET, POST'); // 允许option，get，post请求
header('Access-Control-Allow-Headers:*'); // 允许x-requested-with请求头
header('Access-Control-Expose-Headers:*');

if (!defined('ROOT_PATH')) {
    $_root = rtrim(dirname(rtrim($_SERVER['SCRIPT_NAME'], '/')), '/');
    define('ROOT_PATH', (('/' == $_root || '\\' == $_root) ? '' : $_root));
}
// 加载基础文件
require __DIR__ . '/../core/base.php';
// 执行应用并响应
Container::get('app')->run()->send();
