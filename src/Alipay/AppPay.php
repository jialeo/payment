<?php

/**
 * PC场景下单并支付
 */

namespace JiaLeo\Payment\Alipay;

use JiaLeo\Payment\Common\PaymentException;


class AppPay extends BasePay
{
    public $method = 'alipay.trade.app.pay';
    public $productCode = 'QUICK_MSECURITY_PAY';

    /**
     * 重载handle方法
     * @param $params
     * @throws PaymentException
     */
    public function handle($params, $is_app = true, $is_qrcode = false)
    {
        $url = parent::handle($params, $is_app, $is_qrcode);

        return $url;
    }

}