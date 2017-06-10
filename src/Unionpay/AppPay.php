<?php

/**
 * APP场景下单并支付
 */

namespace JiaLeo\Payment\Unionpay;

use JiaLeo\Payment\Common\PaymentException;


class AppPay extends BasePay
{
    /**
     * WebPay constructor.
     */
    public function __construct($config)
    {
        parent::__construct($config);

        //判断是否测试环境
        if($config['is_test']){
            $this->transUrl = 'https://gateway.test.95516.com/gateway/api/appTransReq.do';
        }
        else{
            $this->transUrl = 'https://gateway.95516.com/gateway/api/appTransReq.do';
        }
    }

    /**
     * 执行
     * @param $params
     * @return array
     * @throws PaymentException
     */
    public function handle($params)
    {
        $test = $this->consume($params,true);

        $res = \JiaLeo\Payment\Common\Curl::post($test['url'],$test['params']);
        if(!$res){
            throw new PaymentException('请求银联接口错误!');
        }

        parse_str($res,$res_array);

        if( ! (is_array($res_array) && isset($res_array['respCode']))){
            throw new PaymentException('请求银联接口错误!');
        }

        return $res_array;
    }




}