<?php

/**
 * 手机网站支付场景下单并支付
 */

namespace JiaLeo\Payment\Alipay;
use JiaLeo\Payment\Common\PaymentException;


class WapPay extends BasePay
{
    public $method = 'alipay.trade.wap.pay';
}