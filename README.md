PHPSDK
======

设备云平台主要提供Restful方式的接口供开发者调用。
接入设备云请进入 [设备云主站](http://open.iot.10086.cn) 了解相关文档。

**传送门**:
[API开发文档](http://open.iot.10086.cn/apidoc)

API调用基础地址为:
`http://api.heclouds.com`


简单示例：
```php
$apikey = '';
$apiurl = 'http://api.heclouds.com';
$device_id = 12345;
//创建api对象
$sm = new OneNetApi($apikey, $apiurl);
$device = $sm->device($device_id);
var_dump($device);
```