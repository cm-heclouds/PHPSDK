<?php

/**
 * ChinaMobile OneNet PHP API SDK
 * @author zhangzhan - goalzz85@gmail.com
 * @Editor LiMao - limao777@126.com
 * @license MIT - https://opensource.org/licenses/MIT
 */
class OneNetApi
{

    protected $_key = NULL;

    protected $_base_url = 'http://api.heclouds.com';

    protected $_raw_response = ''; // 服务端返回的原始数据

    protected $_http_code = 200;

    protected $_error_no = 0;

    protected $_error = '';

    protected static $_ALLOW_METHODS = array(
        'GET',
        'PUT',
        'POST',
        'DELETE'
    );

    public function __construct($key = NULL, $base_url = NULL)
    {
        $this->_key = $key;
        
        if (! empty($base_url)) {
            $this->_base_url = $base_url;
        }
    }

    public function raw_response()
    {
        return $this->_raw_response;
    }

    public function error()
    {
        return $this->_error;
    }

    public function error_no()
    {
        return $this->_error_no;
    }

    public function http_code()
    {
        return $this->_http_code;
    }

    public function curApiKey()
    {
        return $this->_key;
    }
    
    // 设备相关API
    public function device($id)
    {
        if (empty($id)) {
            return FALSE;
        }
        
        $api = "/devices/{$id}";
        
        return $this->_call($api);
    }

    public function device_list($page = 1, $page_size = 30, $key_word = NULL, $tag = NULL, $is_online = NULL, $is_private = NULL, $device_ids = NULL)
    {
        $params = array(
            'page' => is_numeric($page) ? $page : 1,
            'per_page' => is_numeric($page_size) ? $page_size : 30
        );
        
        if (! is_null($key_word)) {
            $params['key_words'] = $key_word;
        }
        
        if (! is_null($tag)) {
            $params['tag'] = $tag;
        }
        
        if (! is_null($is_private)) {
            $params['private'] = $is_private;
        }
        
        if (! is_null($device_ids)) {
            $params['device_id'] = $device_ids;
        }
        
        $params_str = http_build_query($params);
        
        // 2015-10-29 http build query会将true转义为1，API只接受true/false的布尔串
        if (! is_null($is_online)) {
            $params_str = $params_str . '&online=true';
        }
        
        $api = '/devices?' . $params_str;
        
        return $this->_call($api);
    }

    public function device_add($device)
    {
        $api = '/devices';
        return $this->_call($api, 'POST', $device);
    }

    public function device_edit($id, $device)
    {
        if (empty($id)) {
            return FALSE;
        }
        
        $api = "/devices/{$id}";
        return $this->_call($api, 'PUT', $device);
    }

    public function device_delete($id)
    {
        if (empty($id)) {
            return FALSE;
        }
        
        $api = "/devices/{$id}";
        return $this->_call($api, 'DELETE');
    }
    
    // 数据流相关API
    public function datastream($device_id, $datastream_id)
    {
        if (empty($device_id) || empty($datastream_id)) {
            return FALSE;
        }
        
        // 对空格进行转义
        // $datastream_id = str_replace(" ","+", $datastream_id);
        $datastream_id = rawurlencode($datastream_id); // 空格处理的修改 2015-08-25
        
        $api = "/devices/{$device_id}/datastreams/{$datastream_id}";
        return $this->_call($api);
    }

    /**
     * 获取某个设备下面的数据流
     */
    public function datastream_of_dev($device_id)
    {
        if (empty($device_id)) {
            return FALSE;
        }
        
        $api = "/devices/{$device_id}/datastreams";
        return $this->_call($api);
    }

    public function datastream_add($device_id, $datastream)
    {
        if (empty($device_id) || empty($datastream)) {
            return FALSE;
        }
        
        $api = "/devices/{$device_id}/datastreams";
        return $this->_call($api, 'POST', $datastream);
    }

    public function datastream_edit($device_id, $datastream_id, $datastream)
    {
        if (empty($device_id) || empty($datastream_id) || empty($datastream)) {
            return FALSE;
        }
        
        $api = "/devices/{$device_id}/datastreams/{$datastream_id}";
        return $this->_call($api, 'PUT', $datastream);
    }

    public function datastream_delete($device_id, $datastream_id)
    {
        if (is_null($device_id) || is_null($datastream_id)) {
            return FALSE;
        }
        
        $api = "/devices/{$device_id}/datastreams/{$datastream_id}";
        return $this->_call($api, 'DELETE');
    }

    /**
     * $datas:
     * array(
     * timestamp => data
     * )
     */
    // 数据点操作
    public function datapoint_add($device_id, $datastream_id, $datas)
    {
        if (empty($datas)) {
            return TRUE;
        }
        
        if (empty($device_id) || empty($datastream_id)) {
            return FALSE;
        }
        $datastream_data = array();
        foreach ($datas as $t => $v) {
            $t = date('Y-m-d\TH:i:s', $t);
            if (empty($t)) {
                continue;
            }
            $datastream_data[] = array(
                'at' => $t,
                'value' => $v
            );
        }
        $api_data = array(
            'datastreams' => array(
                array(
                    'id' => $datastream_id,
                    'datapoints' => $datastream_data
                )
            )
        );
        $api = "/devices/{$device_id}/datapoints";
        return $this->_call($api, 'POST', $api_data);
    }

    /**
     * 多个datastream一次添加
     * $datas:
     * array(
     * 'datastream_id' => array(
     * 'timestamp' => data
     * )
     * )
     */
    public function datapoint_multi_add($device_id, $datas)
    {
        if (empty($datas)) {
            return TRUE;
        }
        
        if (empty($device_id)) {
            return FALSE;
        }
        
        $api_data = array(
            'datastreams' => array()
        );
        
        foreach ($datas as $datastream_id => $d) {
            $datastream_data = array();
            foreach ($d as $t => $v) {
                $t = date('Y-m-d\TH:i:s', $t);
                if (empty($t)) {
                    continue;
                }
                $datastream_data[] = array(
                    'at' => $t,
                    'value' => $v
                );
            }
            
            if (empty($datastream_data)) {
                continue;
            }
            
            $api_data['datastreams'][] = array(
                'id' => $datastream_id,
                'datapoints' => $datastream_data
            );
        }
        $api = "/devices/{$device_id}/datapoints";
        
        return $this->_call($api, 'POST', $api_data);
    }

    /**
     * 2015-04-13 OneNet更新后，将不再支持以下参数:
     * sort_time 可选，指定时按时间倒序排，最新时间在前面
     * page 指定页码, 可选
     * per_page 指定每页输出数据点个数,可选, 默认300，最多1000
     *
     * 当前datastream更像是数据流的操作。废弃之前 datapoint_list, datapoint_multi_list 方法
     */
    public function datapoint_get($device_id, $datastream_id, $start_time = NULL, $end_time = NULL, $limit = NULL, $cursor = NULL)
    {
        if (empty($device_id) || empty($datastream_id)) {
            return FALSE;
        }
        
        return $this->datapoint_multi_get($device_id, $start_time, $end_time, $limit, $cursor, $datastream_id);
    }

    public function datapoint_multi_get($device_id, $start_time = NULL, $end_time = NULL, $limit = NULL, $cursor = NULL, $datastream_ids = array())
    {
        if (empty($device_id)) {
            return FALSE;
        }
        
        $params = array();
        
        if (! empty($datastream_ids)) {
            if (is_array($datastream_ids)) {
                $datastream_ids = implode(',', $datastream_ids);
            }
            
            $params['datastream_id'] = $datastream_ids;
        }
        
        if (! empty($start_time)) {
            $start_time = date('Y-m-d\TH:i:s', strtotime($start_time));
            $params['start'] = $start_time;
        }
        if (! empty($end_time)) {
            $end_time = date('Y-m-d\TH:i:s', strtotime($end_time));
            $params['end'] = $end_time;
        }
        if (! empty($limit)) {
            $params['limit'] = $limit;
        }
        if (! empty($cursor)) {
            $params['cursor'] = $cursor;
        }
        
        $params_str = http_build_query($params);
        
        $api = "/devices/{$device_id}/datapoints?" . $params_str;
        
        return $this->_call($api);
    }

    public function datapoint_delete($device_id, $datastream_id, $start_time = NULL, $end_time = NULL)
    {
        if (empty($device_id) || empty($datastream_id)) {
            return FALSE;
        }
        $startduration = $this->_startendtime_to_startduration($start_time, $end_time);
        $params = array(
            'start' => $startduration[0],
            'duration' => $startduration[1],
            'datastream_id' => $datastream_id
        );
        
        $params_str = http_build_query($params);
        $api = "/devices/{$device_id}/datapoints?" . $params_str;
        return $this->_call($api, 'DELETE');
    }

    public function datapoint_multi_delete($device_id, $start_time = NULL, $end_time = NULL)
    {
        if (empty($device_id)) {
            return FALSE;
        }
        $startduration = $this->_startendtime_to_startduration($start_time, $end_time);
        $params = array(
            'start' => $startduration[0],
            'duration' => $startduration[1]
        );
        
        $params_str = http_build_query($params);
        $api = "/devices/{$device_id}/datapoints?" . $params_str;
        return $this->_call($api, 'DELETE');
    }
    
    // 触发器操作
    public function trigger($trigger_id)
    {
        if (empty($trigger_id)) {
            return FALSE;
        }
        
        $api = "/triggers/{$trigger_id}";
        return $this->_call($api);
    }

    public function trigger_list($page = NULL, $per_page = NULL, $title = NULL)
    {
        $params = array(
            'page' => is_numeric($page) ? $page : 1,
            'per_page' => is_numeric($per_page) ? $per_page : 30
        );
        
        if (! is_null($title)) {
            $params['title'] = $title;
        }
        
        $params_str = http_build_query($params);
        
        $api = "/triggers?" . $params_str;
        return $this->_call($api);
    }

    public function trigger_add($trigger)
    {
        if (empty($trigger)) {
            return FALSE;
        }
        $api = "/triggers";
        
        return $this->_call($api, 'POST', $trigger);
    }

    public function trigger_edit($trigger_id, $trigger)
    {
        if (empty($trigger_id) || empty($trigger)) {
            return FALSE;
        }
        $api = "/triggers/{$trigger_id}";
        return $this->_call($api, 'PUT', $trigger);
    }

    public function trigger_delete($trigger_id)
    {
        if (empty($trigger_id)) {
            return FALSE;
        }
        $api = "/triggers/{$trigger_id}";
        
        return $this->_call($api, 'DELETE');
    }

    /**
     * 获取APIKey
     *
     * @return array 当指定$dev_id 和|或 $key时，返回满足条件的key信息；当两个参数都不指定时，返回用户的所用key
     */
    public function api_key($dev_id = NULL, $key = NULL, $page = NULL, $per_page = NULL)
    {
        $params = array();
        if (! is_null($dev_id)) {
            $params['device_id'] = $dev_id;
        }
        if (! is_null($key)) {
            $params['key'] = $key;
        }
        if (! is_null($page)) {
            $params['page'] = $page;
        }
        if (! is_null($per_page)) {
            $params['per_page'] = $per_page;
        }
        
        $api = "/keys";
        $par_str = http_build_query($params);
        if (! empty($par_str)) {
            $api .= "?" . $par_str;
        }
        
        $ret = $this->_call($api, 'GET');
        if ($ret === TRUE) {
            // 数据是空的
            $ret = array();
        }
        
        return $ret;
    }
    
    // 暂时只提供到dev_id级别权限, 必须是master_key 才行
    public function api_key_add($dev_id, $title)
    {
        if (empty($title) || empty($dev_id)) {
            return FALSE;
        }
        
        $data = array(
            'title' => $title,
            'permissions' => array(
                array(
                    'resources' => array(
                        array(
                            'dev_id' => $dev_id
                        )
                    )
                )
            )
        );
        
        $api = "/keys";
        
        return $this->_call($api, 'POST', $data);
    }
    
    // 修改key
    public function api_key_edit($key, $title, $resource = null)
    {
        if (empty($key)) {
            return false;
        }
        
        $data = array(
            'title' => $title,
            'permissions' => array(
                array(
                    'resources' => $resource
                )
            )
        );
        
        $api = "/keys/{$key}";
        $res = $this->_call($api, 'PUT', $data);
        
        return $res;
    }
    
    // 删除指定的api_key
    public function api_key_delete($api_key)
    {
        if (empty($api_key)) {
            return FALSE;
        }
        $api_key = urlencode($api_key);
        $api = "/keys/{$api_key}";
        
        $res = $this->_call($api, 'DELETE');
        
        return $res;
    }

    public function request_log($device_id, $start_time)
    {
        if (empty($device_id)) {
            return FALSE;
        }
        if (empty($start_time)) {
            $start_time = date('Y-m-d\TH:i:s', time() - 3600); // 向后查询1个小时
        } else {
            $start_time = date('Y-m-d\TH:i:s', $start_time + 5); // 随后就有开始时间了
        }
        
        $api = "/logs/{$device_id}?t_start=" . $start_time;
        
        return $this->_call($api);
    }

    public function send_data_to_edp($device_id, $qos, $timeout, $sms)
    {
        if (empty($device_id)) {
            return FALSE;
        }
        $api = "/cmds?device_id={$device_id}&qos={$qos}&timeout={$timeout}";
        return $this->_call($api, 'POST', $sms);
    }
	
	public function send_data_to_mqtt($topic, $sms)
    {

        $api = "/mqtt?topic={$topic}";
        return $this->_call($api, 'POST', $sms);
    }

    public function get_dev_status($cmd_uuid)
    {
        if (empty($cmd_uuid)) {
            return FALSE;
        }
        
        $api = "/cmds/{$cmd_uuid}";
        $res = $this->_call($api, 'GET');
        return $res;
    }

    public function get_dev_status_resp($cmd_uuid)
    {
        if (empty($cmd_uuid)) {
            return FALSE;
        }
        
        $api = "/cmds/{$cmd_uuid}/resp";
        $res = $this->_call($api, 'GET');
        return $res;
    }
    
    // 开始时间和结束时间转换为接口形式
    protected function _startendtime_to_startduration($start_time, $end_time)
    {
        $duration = 3600;
        $start = 0;
        if (! empty($start_time)) {
            $start = strtotime($start_time) + 1; // 不返回这个时间的点
            $start_time = date('Y-m-d\TH:i:s', $start);
        }
        
        if (! empty($end_time)) {
            $duration = $end_time - $start_time;
        }
        return array(
            $start_time,
            $duration
        );
    }
    
    // 权限key操作，具体还需要修改
    protected function _paddingUrl($url)
    {
        if (empty($url)) {
            return $url;
        }
        
        if ($url[0] != '/') {
            $url = '/' . $url;
        }
        
        return $this->_base_url . $url;
    }
    
    // 返回直接的ret数据
    protected function _rawcall($url, $method = 'GET', $data = array(), $headers = array())
    {
        $url = $this->_paddingUrl($url);
        
        $this->_error_no = 0;
        $this->_error = NULL;
        $default_headers = array(
            "api-key: {$this->_key}"
        );
        
        if (empty($this->_key)) {
            $default_headers = array();
        }
        
        if (empty($url)) {
            $this->_http_code = 500;
            return FALSE;
        }
        
        if (! in_array($method, self::$_ALLOW_METHODS)) {
            $this->_http_code = 500;
            return FALSE;
        }
        
        //如果data不是想要的，直接设置为NULL
        if (is_null($data) || (is_array($data) && count($data) == 0) || $data === FALSE) {
            $data = NULL;
        } else {
            if (is_array($data)) {
                $data = json_encode($data);
            }
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 2);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        if ($method != 'GET') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        // header set
        if (! empty($headers)) {
            $headers = array_merge($default_headers, $headers);
        } else {
            $headers = $default_headers;
        }
        
        // 有可能default_header为空
        if (! empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        
        $this->_beforeCall($ch, $url, $method, $data);
        $ret = curl_exec($ch);
        $this->_afterCall($ch, $url, $method, $data, $ret);
        
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (! empty($http_code)) {
            $this->_http_code = $http_code;
        }
        
        curl_close($ch);
        $this->_raw_response = $ret;
        return $ret;
    }

    protected function _call($url, $method = 'GET', $data = array(), $headers = array())
    {
        $ret = $this->_rawcall($url, $method, $data, $headers);
        $ori_ret = $ret;
        
        $ret = @json_decode($ret, TRUE);
        
        if (empty($ret)) {
            $ret = FALSE;
        } else {
            if (empty($ret['errno'])) {
                if (isset($ret['data'])) {
                    $ret = $ret['data'];
                } else {
                    $ret = TRUE;
                }
            } else {
                // 产生了错误了
                /**
                 * {
                 * errno:0 //0表示成功
                 * error:''//错误信息
                 * data:{} //如果有数据放在这里边
                 * }
                 */
                $this->_error_no = $ret['errno'];
                if (! empty($ret['error'])) {
                    $this->_error = $ret['error'];
                }
                
                $ret = FALSE;
            }
        }
        $this->_afterDecode($url, $method, $data, $ori_ret, $ret);
        
        return $ret;
    }

    protected function _beforeCall($ch, $url, $method, $data)
    {}

    protected function _afterCall($ch, $url, $method, $data, $ret)
    {}

    protected function _afterDecode($url, $method, $data, $ori_ret, $ret)
    {}
}

class SmartDataApi extends OneNetApi
{
}