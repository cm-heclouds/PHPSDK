PHPSDK
======

设备云平台主要提供Restful方式的接口供开发者调用。
接入设备云请进入 [设备云主站](https://open.iot.10086.cn) 了解相关文档。

**传送门**:
[API开发文档](https://open.iot.10086.cn/doc)

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

# 更新历史
## 2017-11
更新了API文档描述，描述更符合阅读需要

新增设备注册方法

新增设备模糊查询方法，老方法声明即将废弃，但该版本可继续使用

新增批量查看设备状态方法

**  *重制了数据点获取方法，老方法声明即将废弃，但该版本可继续使用 **

声明不建议使用删除数据点方法，但该方法予以保留满足特殊需求

更新了EDP命令发送方法，增强健壮性

新增按设备给MQTT下发命令方法

新增modbus命令下发方法

新增TCP透传命令下发方法
