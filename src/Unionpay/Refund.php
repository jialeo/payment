<?php

namespace JiaLeo\Payment\Unionpay;

use JiaLeo\Payment\Common\PaymentException;

class Refund extends BaseUnionpay
{

    public $transUrl;

    /**
     * WebPay constructor.
     */
    public function __construct($config)
    {
        parent::__construct($config);

        //判断是否测试环境
        if ($config['is_test']) {
            $this->transUrl = 'https://gateway.test.95516.com/gateway/api/backTransReq.do';
        } else {
            $this->transUrl = 'https://gateway.95516.com/gateway/api/backTransReq.do';
        }
    }

    /**
     * 退款
     * @return mixed
     * @throws PaymentException
     */
    public function handle($get_params)
    {
        //参数配置
        $params = [
            'version' => '5.0.0',                 //版本号
            'encoding' => 'utf-8',                  //编码方式
            'txnType' => '04',                      //交易类型
            'txnSubType' => '00',                  //交易子类
            'bizType' => '000201',                  //业务类型
            'signMethod' => '01',                  //签名方法
            'channelType' => '08',                  //渠道类型，07-PC，08-手机
            'accessType' => '0',                  //接入类型
            'currencyCode' => '156',              //交易币种，境内商户固定156
        ];


        $params['merId'] = $this->config['mer_id'];
        $params['txnTime'] = date('YmdHis');

        $params = array_merge($params, $get_params);

        //签名
        $cert_path = $this->config['private_key_path'];
        $cert_pwd = $this->config['private_key_pwd'];

        //获取证书key
        $certId = Utils\Rsa::getCertId($cert_path, $cert_pwd);
        $params['certId'] = $certId;

        //签名
        $params['signature'] = Utils\Rsa::getParamsSignatureWithRSA($params, $cert_path, $cert_pwd);;

        //异步提交---后台通知地址
        $result = \JiaLeo\Payment\Common\Curl::post($this->transUrl, $params);

        //返回示例
        //accessType=0&bizType=000201&encoding=utf-8&merId=777290058143059
        //&orderId=149648615778501&origQryId=201706031828290366408&queryId=201706031835570406088
        //&respCode=00&respMsg=成功[0000000]&signMethod=01&txnAmt=1&txnSubType=00
        //&txnTime=20170603183557&txnType=04&version=5.0.0&certId=68759585097
        //&signature=C6m1JFv59rNmB/tiS4Q1YIAcg3Vv+U4odNUkzqlt3+D+AASmtWS4bg1jfJ3EidXxPTF+kMuk1Iy2gGzkgvfbn9BWEqEeYCV8sktusaTfOO6o2Obbz8HLRpu4zkLfWbVBA6aQ7nSrdpRl7RHO9ToFBcDdUcM07vWzGQ79cAELmrgxuvnp966/eNuQFu5jPHj2hFndduWAKM0+EIbIy/n1vcIk6kiGJGsXVcBeNoqsgjPMMMQWF2j3jTAmvQvnx7SWmwgtWE/LYSmNonehvJQv1CFa/fKkiiMIdd8XsMvJv6zbDQumzJz3lQ67mWMRq+YDBEyH+onzwSjO4IWXeJz9ig==

        if (!$result) {
            throw new PaymentException('请求银联接口错误!');
        }

        $res_array=$this->strToArray($result);

        if (!(is_array($res_array) && isset($res_array['respCode']))) {
            throw new PaymentException('请求银联接口错误!');
        }

        if ($res_array['respCode'] != '00') {
            throw new PaymentException('退款失败!原因:' . $res_array['respMsg']);
        }

        //验签
        $res =  \JiaLeo\Payment\Unionpay\Utils\Rsa::verify($res_array,$this->config['cert_dir']);
        if(!$res){
            throw new PaymentException('签名验证失败!');
        }

        return $res_array;
    }


    public function strToArray($data)
    {
        $temp = array();
        $arr = explode('&',$data);

        foreach($arr as $key => $v){
            $str = explode('=',$v);
            $temp[$str[0]] = $str[1];
        }

        return $temp;

    }
}