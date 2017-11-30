<?php

/**
 * 公众号场景下单并支付
 */

namespace JiaLeo\Payment\Wechatpay;

use JiaLeo\Payment\Common\PaymentException;


class H5Pay extends BasePay
{

    public $tradeType = 'MWEB';
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
        return $pay_info['mweb_url'];
    }


}