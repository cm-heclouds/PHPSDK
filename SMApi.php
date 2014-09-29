<?php
/**
 * SmartData API
 * @author zhangzhan - goalzz85@gmail.com
 * @license BSD 3-Clause, http://opensource.org/licenses/BSD-3-Clause
 */
class SmartDataApi {
    protected $_key = NULL;
    protected $_base_url = 'http://api.heclouds.com/';

    protected $_http_code = 200;
    protected $_error_no = 0;
    protected $_error = '';

    protected static $_ALLOW_METHODS = array(
        'GET', 'PUT', 'POST', 'DELETE'
    );

    public function __construct($key = NULL, $base_url = NULL)
    {
        $this->_key = $key;
        
        if (!empty($base_url)) {
            $this->_base_url = $base_url;
        }
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

    //设备相关API
    public function device($id)
    {
        if (empty($id)) {
            return FALSE;
        }

        $api = "/devices/{$id}";

        return $this->_call($api);
    }

    public function device_list($page = 1, $page_size = 30, $key_word = NULL, $tag = NULL, $is_online = NULL, $is_private = NULL)
    {
        $params = array(
            'page' => is_numeric($page) ? $page : 1,
            'per_page' => is_numeric($page_size) ? $page_size : 30,
        );

        if (!is_null($key_word)) {
            $params['key_words'] = $key_word;
        }

        if (!is_null($tag)) {
            $params['tag'] = $tag;
        }

        if (!is_null($is_online)) {
            $params['online'] = $is_online;
        }

        if (!is_null($is_private)) {
            $params['private'] = $is_private;
        }
        
        $params_str = http_build_query($params);
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

    //数据流相关API
    public function datastream($device_id, $datastream_id)
    {
        if (empty($device_id) || empty($datastream_id)) {
            return FALSE;
        }

        $api = "/devices/{$device_id}/datastreams/{$datastream_id}";
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
        if (empty($device_id) || empty($datastream_id)) {
            return FALSE;
        }

        $api = "/devices/{$device_id}/datastreams/{$datastream_id}";
        return $this->_call($api, 'DELETE');
    }

    /**
     * 
     * $datas:
     * array(
     *     timestamp => data
     * )
     */
    //数据点操作
    public function datapoint_add($device_id, $datastream_id, $datas)
    {
        if (empty($datas)) {
            return TRUE;
        }

        if (empty($device_id) || empty($datastream_id)) {
            return FALSE;
        }
        $datastream_data = array();
        foreach ($datas as $t => $v)
        {
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
                ),
            ),
        );
        $api = "/devices/{$device_id}/datapoints";
        return $this->_call($api, 'POST', $api_data);
    }

    /**
     * 多个datastream一次添加
     * $datas:
     * array(
     *  'datastream_id' => array(
     *     'timestamp' => data
     *   )
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
        

        foreach ($datas as $datastream_id => $d)
        {
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

    public function datapoint_list($device_id, $datastream_id, $start_time = NULL, $end_time = NULL , $page = 1, $page_size = 5, $order_desc = TRUE)
    { 
        if (empty($device_id) || empty($datastream_id))
        {
            return FALSE;
        }

        $params = array(
            'datastream_id' => $datastream_id,
            'page' => $page,
            'per_page' => $page_size
        );

        if ($order_desc) {
            $params['sort_time'] = -1;
        }

        if (!empty($start_time)) {
            $startduration = $this->_startendtime_to_startduration($start_time, $end_time);
            $params['start'] = $startduration[0];
            $params['duration'] = $startduration[1];
        }

        $params_str = http_build_query($params);
        $api = "/devices/{$device_id}/datapoints?" . $params_str;
        return $this->_call($api);
    }

    public function datapoint_multi_list($device_id, $start_time = NULL, $end_time = NULL, $page = 1, $page_size = 5, $order_desc = TRUE)
    {
        if (empty($device_id))
        {
            return FALSE;
        }
        $params = array(
            'page' => $page,
            'per_page' => $page_size
        );

        if ($order_desc) {
            $params['sort_time'] = -1;
        }

        if (!empty($start_time)) {
            $startduration = $this->_startendtime_to_startduration($start_time, $end_time);
            $params['start'] = $startduration[0];
            $params['duration'] = $startduration[1];
        }

        $params_str = http_build_query($params);
        $api = "/devices/{$device_id}/datapoints?" . $params_str;
        return $this->_call($api);
    }

    public function datapoint_delete($device_id, $datastream_id, $start_time = NULL, $end_time = NULL)
    {
        if (empty($device_id) || empty($datastream_id))
        {
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
        if (empty($device_id))
        {
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

    //触发器操作
    public function trigger($device_id, $datastream_id, $trigger_id)
    {
        if (empty($device_id) || empty($datastream_id) || empty($trigger_id)) {
            return FALSE;
        }

        $api = "/devices/{$device_id}/datastreams/{$datastream_id}/triggers/{$trigger_id}";
        return $this->_call($api);
    }

    public function trigger_add($device_id, $datastream_id, $trigger)
    {
        if (empty($device_id) || empty($datastream_id)) {
            return FALSE;
        }

        $api = "/devices/{$device_id}/datastreams/{$datastream_id}/triggers";
        return $this->_call($api, 'POST', $trigger);
    }


    public function trigger_edit($device_id, $datastream_id, $trigger_id, $trigger)
    {
        if (empty($device_id) || empty($datastream_id) || empty($trigger_id)) {
            return FALSE;
        }

        $api = "/devices/{$device_id}/datastreams/{$datastream_id}/triggers/{$trigger_id}";
        return $this->_call($api, 'PUT', $trigger);
    }

    public function trigger_delete($device_id, $datastream_id, $trigger_id)
    {
        if (empty($device_id) || empty($datastream_id) || empty($trigger_id)) {
            return FALSE;
        }
        $api = "/devices/{$device_id}/datastreams/{$datastream_id}/triggers/{$trigger_id}";

        return $this->_call($api, 'DELETE');
    }

    //获取APIkey
    public function api_key($dev_id)
    {
        if (empty($dev_id)) {
            return FALSE;
        }

        $api = "/keys?dev_id=" . urlencode($dev_id);
        $ret = $this->_call($api, 'GET');
        if ($ret === TRUE) {
            //数据是空的
            $ret = array();
        }

        return $ret;
    }

    //暂时只提供到dev_id级别权限, 必须是master_key 才行
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
                        ),
                    ),
                )
            ),
        );

        $api = "/keys";

        return $this->_call($api, 'POST', $data);
    }

    //删除指定的api_key
    public function api_key_delete($api_key)
    {
        $api = "/keys";
        $ori_api_key = $this->_key;

        $this->_key = $api_key;

        $res = $this->_call($api, 'DELETE');

        $this->_key = $ori_api_key;

        return $res;
    }



    public function request_log($device_id, $start_time){
        if(empty($device_id)) {
            return FALSE;
        }
        if(empty($start_time)) {
            $start_time = date('Y-m-d\TH:i:s', time()-3600); //向后查询1个小时
        } else {
            $start_time = date('Y-m-d\TH:i:s', $start_time + 5); //随后就有开始时间了
        }

        $api = "/logs/{$device_id}?t_start=".$start_time;

        return $this->_call($api);
    }

    //开始时间和结束时间转换为接口形式
    protected function _startendtime_to_startduration($start_time, $end_time)
    {

        $duration = 3600;
        $start = 0;
        if (!empty($start_time)) {
            $start = strtotime($start_time) + 1; //不返回这个时间的点
            $start_time = date('Y-m-d\TH:i:s', $start);
        } 

        if (!empty($end_time)) {
            $duration = $end_time - $start_time;
        }
        return array($start_time, $duration);
    }

    //权限key操作，具体还需要修改

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

    protected function _call($url, $method = 'GET', $data = array(), $headers = array())
    {
        $url = $this->_paddingUrl($url);

        $this->_error_no = 0;
        $this->_error = NULL;
        $default_headers = array(
            "api-key: {$this->_key}",
        );

        if (empty($this->_key)) {
            $default_headers = array();
        }

        if (empty($url)) {
            $this->_http_code = 500;
            return FALSE;
        }

        if (!in_array($method, self::$_ALLOW_METHODS)) {
            $this->_http_code = 500;
            return FALSE;
        }

        if (is_array($data)) {
            $data = json_encode($data);
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        //header set
        if (!empty($headers)) {
            $headers = array_merge($default_headers, $headers);
        } else {
            $headers = $default_headers;
        }

        //有可能default_header为空
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $this->_beforeCall($ch, $url, $method, $data);
        $ret = curl_exec($ch);
        $this->_afterCall($ch, $url, $method, $data, $ret);

        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (!empty($http_code)) {
            $this->_http_code = $http_code;
        }

        curl_close($ch);
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
                //产生了错误了
                /**
                 * {
                 *     errno:0 //0表示成功
                 *     error:''//错误信息
                 *     data:{} //如果有数据放在这里边
                 * }
                 */
                $this->_error_no = $ret['errno'];
                if (!empty($ret['error'])) {
                    $this->_error = $ret['error'];
                }

                $ret = FALSE;
            }
        }
        $this->_afterDecode($ch, $url, $method, $data, $ori_ret, $ret);
        
        return $ret;
    }

    protected function _beforeCall($ch, $url, $method, $data)
    {

    }

    protected function _afterCall($ch, $url, $method, $data, $ret)
    {

    }

    protected function _afterDecode($ch, $url, $method, $data, $ori_ret, $ret)
    {

    }
}