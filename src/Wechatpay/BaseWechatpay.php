<?php

namespace JiaLeo\Payment\Wechatpay;

use JiaLeo\Payment\Common\PaymentException;

class BaseWechatpay
{

    public $config = array();

    protected $gateway = 'https://api.mch.weixin.qq.com';    //支付网关

    public function __construct($config)
    {
        /*if (empty($config['app_id'])) {
            throw new PaymentException('缺少配置app_id');
        }

        if (empty($config['ali_public_key'])) {
            throw new PaymentException('缺少配置ali_public_key');
        }

        if (empty($config['rsa_private_key'])) {
            throw new PaymentException('缺少配置rsa_private_key');
        }*/

        $this->config = $config;
    }

    /**
     * 生成签名
     * @return string 签名
     */
    public function makeSign($params)
    {
        //签名步骤一：按字典序排序参数
        ksort($params);
        $string = $this->toUrlParams($params);
        //签名步骤二：在string后加入KEY
        $string = $string . "&key=" . $this->config['key'];
        //签名步骤三：MD5加密
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }

    /**
     * 格式化参数格式化成url参数
     */
    public function toUrlParams($params)
    {
        $buff = "";
        foreach ($params as $k => $v) {
            if ($k != "sign" && $v != "" && !is_array($v)) {
                $buff .= $k . "=" . $v . "&";
            }
        }

        $buff = trim($buff, "&");
        return $buff;
    }

    /**
     * 产生随机字符串，不长于32位
     * @param int $length
     * @return string 产生的随机字符串
     */
    public static function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyz0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }

    /**
     * 输出xml字符
     * @throws PaymentException
     **/
    public function toXml($params)
    {
        if (!is_array($params)
            || count($params) <= 0
        ) {
            throw new PaymentException("数组数据异常！");
        }

        $xml = "<xml>";
        foreach ($params as $key => $val) {
            if (is_numeric($val)) {
                $xml .= "<" . $key . ">" . $val . "</" . $key . ">";
            } else {
                $xml .= "<" . $key . "><![CDATA[" . $val . "]]></" . $key . ">";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    /**
     * 将xml转为array
     * @param string $xml
     * @throws PaymentException
     */
    public function fromXml($xml)
    {
        if (!$xml) {
            throw new PaymentException("xml数据异常！");
        }
        //将XML转为array
        //禁止引用外部xml实体
        $bPreviousValue = libxml_disable_entity_loader(true);
        $params = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        libxml_disable_entity_loader($bPreviousValue);

        return $params;
    }

    /**
     * 以post方式提交xml到对应的接口url
     *
     * @param string $xml 需要post的xml数据
     * @param string $url url
     * @param string $sslcert_path cert路径，默认不需要
     * @param string $sslkey_path 是否需要证书，默认不需要
     * @param int $second url执行超时时间，默认30s
     * @throws PaymentException
     */
    protected function postXmlCurl($url, $xml, $sslcert_path = '', $sslkey_path = '', $second = 30)
    {
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, $second);

        //如果有配置代理这里就设置代理
        /*curl_setopt($ch,CURLOPT_PROXY, "0.0.0.0");
        curl_setopt($ch,CURLOPT_PROXYPORT, 8080);*/

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);//严格校验
        //设置header
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

        if (!empty($sslcert_path) && !empty($sslkey_path)) {
            //设置证书
            //使用证书：cert 与 key 分别属于两个.pem文件
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, $sslcert_path);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, $sslkey_path);
        }

        //post提交方式
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        //运行curl
        $data = curl_exec($ch);

        //返回结果
        if ($data) {
            curl_close($ch);
            return $data;
        } else {
            $error = curl_errno($ch);
            curl_close($ch);
            throw new PaymentException("curl出错，错误码:$error");
        }
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

}