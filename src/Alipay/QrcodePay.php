<?php

/**
 * 二维码场景下单并支付
 */

namespace JiaLeo\Payment\Alipay;
use JiaLeo\Payment\Common\PaymentException;


class QrcodePay extends BasePay
{
    public $method = 'alipay.trade.precreate';

    /**
     * 重载handle方法
     * @param $params
     * @throws PaymentException
     */
    public function handle($params, $is_app = false, $is_qrcode = false)
    {
        $url = parent::handle($params, false, true);

        return $url;
    }
}