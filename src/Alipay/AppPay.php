<?php

/**
 * PC场景下单并支付
 */

namespace JiaLeo\Payment\Alipay;

use JiaLeo\Payment\Common\PaymentException;


class AppPay extends BasePay
{
    public $method = 'alipay.trade.app.pay';

    /**
     * 重载handle方法
     * @param $params
     * @throws PaymentException
     */
    public function handle($params, $is_app = true)
    {
        $url = parent::handle($params, true);

        return $url;
    }

}