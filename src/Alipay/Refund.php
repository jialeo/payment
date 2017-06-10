<?php
namespace JiaLeo\Payment\Alipay;

use JiaLeo\Payment\Common\PaymentException;

class Refund extends BaseAlipay
{
    /**
     * 统一收单交易退款接口
     * @return mixed
     * @throws PaymentException
     */
    public function handle($params)
    {
        if (empty($params['out_trade_no']) && empty($params['trade_no'])) {
            throw new PaymentException('订单号和支付宝交易号不能同时为空');
        }

        // 检查订单号是否合法
        if (!empty($params['out_trade_no']) && mb_strlen($params['out_trade_no']) > 64) {
            throw new PaymentException('订单号长度不能超过64位');
        }

        // 检查订单号是否合法
        if (!empty($params['trade_no']) && mb_strlen($params['trade_no']) > 64) {
            throw new PaymentException('支付宝交易号长度不能超过64位');
        }

        // 检查退款请求号是否合法
        if (!empty($params['out_request_no']) && mb_strlen($params['out_request_no']) > 64) {
            throw new PaymentException('退款请求号长度不能超过64位');
        }

        // 需要退款的金额不能低于0.01
        if (bccomp($params['refund_amount'] / 100, '0.01', 2) === -1) {
            throw new PaymentException('需要退款的金额不能低于 0.01 元');
        }

        // 检查退款的原因说明是否合法
        if (!empty($params['refund_reason']) && mb_strlen($params['refund_reason']) > 256) {
            throw new PaymentException('退款的原因说明长度不能超过256位');
        }
        $params['refund_amount'] = $params['refund_amount'] / 100;

        //定义公共参数
        $publicParams = array(
            'app_id' => $this->config['app_id'],
            'method' => 'alipay.trade.refund',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
        );

        //生成biz_content参数
        $biz_content = $params;
        $biz_content = $this->createbizContent($biz_content);
        $publicParams['biz_content'] = $biz_content;

        //需要签名的参数
        $sign_params = array_merge($params, $publicParams);

        //参数重新排序
        $sign_params = $this->arraySort($sign_params);

        //生成待签名字符串
        $str = $this->createLinkstring($sign_params);

        //提取私钥
        $rsa_private_key = $this->getRsaKeyValue($this->config['rsa_private_key']);

        //加密字符串
        $rsa = new Utils\Rsa2Encrypt($rsa_private_key);
        $sign = $rsa->encrypt($str);

        $sign_params['sign'] = $sign;
        $url = $this->gateway . '?' . http_build_query($sign_params);

        // 发起网络请求
        $curl = new \JiaLeo\Payment\Common\Curl();
        $responseTxt = $curl->get($url);

        if (!$responseTxt ) {
            throw new PaymentException('网络发生错误');
        }

        $this->refundReturnData = $responseTxt;

        $body = json_decode($responseTxt, true);

        if (!$body) {
            throw new PaymentException('返回数据有误!');
        }

        if (empty($body['alipay_trade_refund_response']['code'])) {
            throw new PaymentException('返回数据有误!');
        }

        if ($body['alipay_trade_refund_response']['code'] != 10000) {
            throw new PaymentException($body['alipay_trade_refund_response']['sub_msg']);
        }

        // 验证签名，检查支付宝返回的数据
        $preStr = json_encode($body['alipay_trade_refund_response']);

        $ali_public_key = $this->getRsaKeyValue($this->config['ali_public_key'], 'public');
        $rsa = new Utils\Rsa2Encrypt($ali_public_key);
        $flag = $rsa->rsaVerify($preStr, $body['sign']);
        if (!$flag) {
            throw new PaymentException('支付宝返回数据被篡改。请检查网络是否安全！');
        }

        return $body;
    }
}