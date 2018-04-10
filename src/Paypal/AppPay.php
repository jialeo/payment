<?php

/**
 * APP场景下单并支付
 */

namespace JiaLeo\Payment\Paypal;

use JiaLeo\Payment\Common\PaymentException;


class AppPay extends BasePay
{
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