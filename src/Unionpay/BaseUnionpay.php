<?php

namespace JiaLeo\Payment\Unionpay;
use JiaLeo\Payment\Common\PaymentException;

class BaseUnionpay
{

    public $config = array();

    protected $gateway ;    //支付网关
    public $refundReturnData;  //退款返回的原始数据

    public function __construct($config)
    {
        if (empty($config['mer_id'])) {
            throw new PaymentException('缺少配置mer_id');
        }

        if (empty($config['private_key_path'])) {
            throw new PaymentException('缺少配置private_key_path');
        }

        if (empty($config['private_key_pwd'])) {
            throw new PaymentException('缺少配置private_key_pwd');
        }

        if (empty($config['cert_dir'])) {
            throw new PaymentException('缺少配置cert_dir');
        }

        $this->config = $config;
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