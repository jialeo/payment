<?php

/**
 * PC场景下单并支付
 */

namespace JiaLeo\Payment\Alipay;
use JiaLeo\Payment\Common\PaymentException;


class WebPay extends BasePay
{
    public $method = 'alipay.trade.page.pay';
}