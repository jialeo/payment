<?php

namespace JiaLeo\Payment\Paypal;

use JiaLeo\Payment\Common\PaymentException;

class BasePaypalPay
{

    public $config = array();

    protected $gateway;    //支付网关
    protected $nvp_api;    //nvp MassPay接口
    protected $end_point;    //EndPoint接口
    public $refundReturnData;  //退款返回的原始数据

    public function __construct($config)
    {
        $this->config = $config;
        if ($config['mode'] == 'sandbox') {
            $this->gateway = 'https://www.sandbox.paypal.com/cgi-bin/webscr';
            $this->nvp_api = 'https://api-3t.sandbox.paypal.com/nvp';
            $this->end_point = 'https://api.sandbox.paypal.com';
        } else {
            $this->gateway = 'https://www.paypal.com/cgi-bin/webscr';
            $this->nvp_api = 'https://api-3t.paypal.com/nvp';
            $this->end_point = 'https://api.paypal.com';
        }
    }

    /**
     * Nvp API Http 请求
     *
     * @param string API 方法名
     * @param string nvp 字符串
     * @return array Parsed HTTP Response body
     */
    public function nvpHttpPost($method_name, $nvp_str)
    {
        $api_username = urlencode($this->config['username']);
        $api_password = urlencode($this->config['password']);
        $api_signature = urlencode($this->config['signature']);

        //api 版本
        $version = urlencode('90');

        // 设置crul参数.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->nvp_api);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        // 关闭服务器SSL验证。
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);

        // 设置API操作，版本，和请求的API签名。
        $nvpreq = "METHOD=$method_name&VERSION=$version&PWD=$api_password&USER=$api_username&SIGNATURE=$api_signature$nvp_str";

        // 设置POST请求参数
        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq . "&" . $nvp_str);

        // 获取请求响应体
        $http_response = curl_exec($ch);

        if (!$http_response) {
            throw new PaymentException($method_name . ' 失败: ' . curl_error($ch) . '(' . curl_errno($ch) . ')', 'HTTP_REQ_ERROR');
        }

        // 提取响应细节
        $http_response_ar = explode("&", $http_response);

        $http_parsed_response_ar = array();
        foreach ($http_response_ar as $i => $value) {
            $tmpAr = explode("=", $value);
            if (sizeof($tmpAr) > 1) {
                $http_parsed_response_ar[$tmpAr[0]] = $tmpAr[1];
            }
        }

        return $http_parsed_response_ar;
    }

    /**
     * 设置Nvp请求字段
     * @param array $data
     * @return string
     */
    public function setNvpParams(array $data)
    {
        $nvp_str = '';
        //遍历数组拼装nvp请求字符串
        foreach ($data as $i => $receiver_data) {
            $receiver_email = urlencode($receiver_data['receiver_email']);
            $amount = urlencode($receiver_data['amount']);
            $unique_id = urlencode($receiver_data['unique_id']);
            $note = urlencode($receiver_data['note']);
            $nvp_str .= "&L_EMAIL$i=$receiver_email&L_Amt$i=$amount&L_UNIQUEID$i=$unique_id&L_NOTE$i=$note";
        }
        return $nvp_str;
    }

    /**
     * 设置自定义字段
     * @param array $data
     * @return string
     */
    public function setPassbackParams(array $data)
    {
        $str_arr = array();
        foreach ($data as $key => $v) {
            $str_arr[] = $key . '--' . $v;
        }

        $str = implode('---', $str_arr);
        return $str;
    }

    /**
     * 获取自定义字段
     * @param $str
     * @return array
     */
    public function getPassbackParams($str)
    {
        $str_arr = explode('---', $str);
        $data = array();
        foreach ($str_arr as $v) {
            $temp = explode('--', $v);
            $data[$temp[0]] = $temp[1];
        }
        return $data;
    }

    /**
     * 获取发起退款的Access token
     * @return array                [description]
     */
    public function getAccessToken()
    {
        $client_id = $this->config['client_id'];
        $secret = $this->config['client_secret'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->end_point . "/v1/oauth2/token");
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $client_id . ":" . $secret);
        curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");

        $result = curl_exec($ch);
        curl_close($ch);
        if (empty($result)) return false;
        else {
            $json = json_decode($result, true);

            return $json['access_token'];
        }
    }

    public function httpPostPaypal($url, $post_data, $header = array())
    {

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // 获取数据返回
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header); // 在启用 CURLOPT_RETURNTRANSFER 时候将获取数据返回array('Expect:')
        $output = curl_exec($ch);

        if (curl_errno($ch)) {
            return false;
        }

        return $output;
    }

    /**
     * IPN验证
     * @param string $url
     * @param string $post_data
     * @return array
     */
    public function notifyValidate($data)
    {
        $receiver_email = $this->config['account'];

        // 拼凑 post 请求数据
        $req = 'cmd=_notify-validate'; // 验证请求
        foreach ($data as $k => $v) {
            $v = urlencode(stripslashes($v));
            $req .= "&{$k}={$v}";
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->gateway);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
        $res = curl_exec($ch);
        if (!$res) {
            $res = curl_exec($ch);
        }
        curl_close($ch);

        if ($res && strcmp($res, 'VERIFIED') == 0 && $data['receiver_email'] == $receiver_email) {
            return $data;
        } else {
            throw new PaymentException('Paypal回调验证错误!');
        }
    }
}