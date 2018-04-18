<?php
namespace JiaLeo\Payment\Wechatpay;

use JiaLeo\Payment\Common\PaymentException;

class BasePay extends BaseWechatpay
{
    protected $tradeType = 'JSAPI';
    protected $payUrl = '/pay/unifiedorder';
    protected $device;

    /**
     * 统一下单接口
     */
    protected function pay($params)
    {

        $this->checkParams($params);


        $nonce_str = $this->getNonceStr();

        if (!isset($params['create_ip'])) {
            $ip_address = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
            //兼容处理
            $ip_address_arr = explode(',', $ip_address);
            $ip_address = $ip_address_arr[0];

        } else {
            $ip_address = $params['create_ip'];
        }

        //必传参数
        $data = array(
            'appid' => $this->config['appid'],
            'mch_id' => $this->config['mchid'],
            'device_info' => $this->device,
            'trade_type' => $this->tradeType,
            'nonce_str' => $nonce_str,

            'spbill_create_ip' => $ip_address,
            'out_trade_no' => $params['out_trade_no'],
            'body' => $params['body'],
            'total_fee' => $params['total_fee'],
            'attach' => $params['attach'],
            'notify_url' => $params['notify_url']
        );

        //用户标识
        if (isset($params['openid'])) {
            $data['openid'] = $params['openid'];
        }

        //订单生成时间，格式为yyyyMMddHHmmss，如2009年12月25日9点10分10秒表示为20091225091010。
        if (isset($params['time_start'])) {
            $data['time_start'] = $params['time_start'];
        }

        //订单失效时间，格式为yyyyMMddHHmmss，如2009年12月27日9点10分10秒表示为20091227091010。
        //注意：最短失效时间间隔必须大于5分钟
        if (isset($params['time_expire'])) {
            $data['time_expire'] = $params['time_expire'];
        }

        //dump($data);

        //进行签名
        $data['sign'] = $this->makeSign($data);

        //转换成xml
        $xml = $this->toXml($data);
        $result = $this->postXmlCurl($this->gateway . $this->payUrl, $xml);
        $get_result = $this->fromXml($result);


        if (!isset($get_result['return_code']) || $get_result['return_code'] != 'SUCCESS') {
            throw new PaymentException('调起支付失败!错误信息:' . isset($get_result['return_msg']) ? $get_result['return_msg'] : $get_result);
        }

        //验证签名
        $check_sigin = $this->makeSign($get_result);
        if ($check_sigin != $get_result['sign']) {
            throw new PaymentException('调起支付失败!错误信息:返回数据验证数据失败!');
        }

        //验证交易是否成功
        if (empty($get_result['result_code']) || $get_result['result_code'] !== 'SUCCESS') {
            throw new PaymentException('生成微信单号失败,' . $get_result['return_msg']);
        };

        //验证返回参数
        if (!array_key_exists("appid", $get_result) || $get_result['appid'] != $this->config['appid'] || !array_key_exists("prepay_id", $get_result) || $get_result['prepay_id'] == "") {
            throw new PaymentException("返回数据参数错误");
        }

        return $get_result;
    }

    /**
     * 检查参数
     * @param $params
     * @throws PaymentException
     */
    public function checkParams($params)
    {
        //检测必填参数
        if (!isset($params['out_trade_no']) || mb_strlen($params['out_trade_no']) > 32) {
            throw new PaymentException("缺少统一支付接口必填参数out_trade_no,并且长度不能超过32位!");
        }

        if (!isset($params['body']) || mb_strlen($params['body']) > 128) {
            throw new PaymentException("缺少统一支付接口必填参数body,并且长度不能超过128位!");
        }

        if (!isset($params['total_fee']) || mb_strlen($params['total_fee']) > 88) {
            throw new PaymentException("缺少统一支付接口必填参数total_fee,并且长度不能超过88位！");
        }

        if (!in_array($this->tradeType, ['JSAPI', 'NATIVE', 'APP', 'MWEB'])) {
            throw new PaymentException("trade_type参数错误！");
        }

        //关联参数
        if ($this->tradeType == "JSAPI" && !isset($params['openid'])) {
            throw new PaymentException("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！");
        }
    }
}