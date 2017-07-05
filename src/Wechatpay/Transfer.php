<?php
namespace JiaLeo\Payment\Wechatpay;

use JiaLeo\Payment\Common\PaymentException;

class Transfer extends BaseWechatpay
{
    public $payUrl = '/mmpaymkttransfers/promotion/transfers';

    /**
     * 企业付款到零钱
     * @return mixed
     * @throws PaymentException
     */
    public function handle($params)
    {
        //检查订单号是否合法
        if (empty($params['partner_trade_no'])) {
            throw new PaymentException('商户订单号订单号不能为空');
        }

        // 需要转账金额不能低于1
        if (empty($params['amount']) || $params['amount'] < 100) {
            throw new PaymentException('转账金额不能为空,且不能低于 1 元');
        }

        if (empty($params['openid'])) {
            throw new PaymentException('openid不能为空');
        }

        $is_check_name = !empty($params['check_name']) ? true : false;
        if (empty($params['re_user_name'])) {
            throw new PaymentException('校验用户姓名选项为验证时,收款用户姓名(re_user_name)不能为空');
        }

        if (!isset($params['create_ip'])) {
            $ip_address = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
            //兼容处理
            $ip_address_arr = explode(',', $ip_address);
            $ip_address = $ip_address_arr[0];

        } else {
            $ip_address = $params['create_ip'];
        }

        $data = array(
            'appid' => $this->config['appid'],
            'mch_id' => $this->config['mchid'],
            'nonce_str' => $this->getNonceStr(),
            'partner_trade_no' => $params['partner_trade_no'],
            'amount' => $params['amount'],
            'openid' => $params['openid'],
            'check_name' => !$is_check_name ? 'NO_CHECK' : 'FORCE_CHECK',
            'spbill_create_ip' => $ip_address,
        );

        if ($is_check_name) {
            $data['re_user_name'] = $params['re_user_name'];
        }

        if (!empty($params['desc'])) {
            $data['desc'] = $params['desc'];
        }

        //签名
        $data['sign'] = $this->makeSign($data);

        //转换成xml
        $xml = $this->toXml($data);
        $result = $this->postXmlCurl($this->gateway . $this->payUrl, $xml, $this->config['sslcert_path'], $this->config['sslkey_path']);
        $get_result = $this->fromXml($result);

        if (!isset($get_result['return_code']) || $get_result['return_code'] != 'SUCCESS') {
            throw new PaymentException('调起支付失败!错误信息:' . isset($get_result['return_msg']) ? $get_result['return_msg'] : $get_result);
        }

        //验证签名
        $check_sigin = $this->makeSign($get_result);
        if ($check_sigin != $get_result['sign']) {
            throw new PaymentException('调起支付失败!错误信息:返回数据验证数据失败!');
        }

        return $get_result;
    }
}