<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// $Id$

$uri = urldecode($_SERVER["REQUEST_URI"]);
$path = parse_url($uri, PHP_URL_PATH);
$file = $_SERVER["DOCUMENT_ROOT"] . $path;

if (is_file($file) && !preg_match('/\.php$/', $file)) {
    return false;
} else {
    $_SERVER["SCRIPT_FILENAME"] = __DIR__ . '/index.php';
    $_SERVER["SCRIPT_NAME"] = '/index.php';
    $_SERVER["PATH_INFO"] = $path;

    require __DIR__ . "/index.php";
}
