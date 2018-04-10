<?php
namespace JiaLeo\Payment\Paypal;

use JiaLeo\Payment\Common\PaymentException;

class BasePay extends BasePaypalPay
{
    /**
     * 统一下单接口
     */
    public function handle($params, $is_app = false)
    {
        //检查参数
        $this->checkParams($params);

        $params['business'] = $this->config['account'];// 告诉paypal，我的（商城的商户）Paypal账号，就是这钱是付给谁的
        $params['cmd'] = '_xclick'; // 告诉Paypal，我的网站是用的我自己的购物车系统
        $params['currency_code'] = isset($params['currency_code']) ? $params['currency_code'] : 'USD'; // //告诉Paypal，我要用什么货币。这里需要注意的是，由于汇率问题，如果网站提供了更改货币的功能，那么上面的amount也要做适当更改，paypal是不会智能的根据汇率更改总额的
        $params['charset'] = 'utf-8';
        $params['no_note'] = '1';
        $params['rm'] = '2';
        $paypal_payment_url = $this->gateway . '?' . http_build_query($params);

        if ($is_app) {
            return $params;
        } else {
            return $paypal_payment_url;
        }
    }

    /**
     * 检查参数
     * @param $params
     * @throws PaymentException
     */
    public function checkParams($params)
    {
        //检测必填参数
        if (!isset($params['item_name'])) {
            throw new PaymentException("缺少统一支付接口必填参数item_name");
        }

        if (!isset($params['invoice']) && is_numeric($params['invoice'])) {
            throw new PaymentException("缺少统一支付接口必填参数invoice,并且一定为数字!");
        }

        if (!isset($params['amount']) || mb_strlen($params['amount']) > 88) {
            throw new PaymentException("缺少统一支付接口必填参数amount,并且长度不能超过88位！");
        }

        if (!isset($params['return'])) {
            throw new PaymentException("缺少统一支付接口必填参数return");
        }

        if (!isset($params['cancel_return'])) {
            throw new PaymentException("缺少统一支付接口必填参数cancel_return");
        }

        if (!isset($params['notify_url'])) {
            throw new PaymentException("缺少统一支付接口必填参数notify_url");
        }

        if (!isset($params['custom'])) {
            throw new PaymentException("缺少统一支付接口必填参数custom");
        }
    }


}