<?php

/**
 * APP场景下单并支付
 */

namespace JiaLeo\Payment\Wechatpay;

use JiaLeo\Payment\Common\PaymentException;


class AppPay extends BasePay
{

    public $tradeType = 'APP';
    public $device = 'WEB';


    /**
     * 下单处理
     * @param $params
     * @return string
     * @throws PaymentException
     */
    public function handle($params)
    {
        $pay_info = $this->pay($params);
        $time = time();

        $pay_sigin_data = array(
            'appid' => $this->config['appid'],
            'timestamp' => "$time",
            'noncestr' => $this->getNonceStr(),
            'package' => 'Sign=WXPay',
            'partnerid' => $this->config['mchid'],
            'prepayid' => $pay_info['prepay_id']
        );

        //签名
        $pay_sigin_data['sign'] = $this->makeSign($pay_sigin_data);

        return $pay_sigin_data;
    }

}