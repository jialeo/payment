<?php

/**
 * 公众号场景下单并支付
 */

namespace JiaLeo\Payment\Wechatpay;

use JiaLeo\Payment\Common\PaymentException;


class MpPay extends BasePay
{

    public $tradeType = 'JSAPI';
    public $device = 'MP';

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
            'appId' => $this->config['appid'],
            'timeStamp' => "$time",
            'nonceStr' => $this->getNonceStr(),
            'signType' => 'MD5',
            'package' => 'prepay_id=' . $pay_info['prepay_id'],
        );

        //签名
        $pay_sigin_data['paySign'] = $this->makeSign($pay_sigin_data);

        return $pay_sigin_data;
    }


}