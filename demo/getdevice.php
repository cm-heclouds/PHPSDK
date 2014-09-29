<?php
require 'SMApi.php';

$apikey = '';
$apiurl = 'http://api.heclouds.com';

//创建api对象
$sm = new SmartDataApi($apikey, $apiurl);

$device_id = '';
$device = $sm->device($device_id);
$error_code = 0;
$error = '';
if (empty($device)) {
    //处理错误信息
    $error_code = $sm->error_no();
    $error = $sm->error();
}

//展现设备
var_dump($device);