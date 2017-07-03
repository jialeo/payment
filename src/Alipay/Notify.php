<?php

namespace JiaLeo\Payment\Alipay;

use JiaLeo\Payment\Common\PaymentException;

class Notify extends BaseAlipay
{
    public $rawData = array();

    /**
     * 回调
     * @return array
     * @throws PaymentException
     */
    public function handle()
    {

        //获取回调信息
        $data = empty($_POST) ? $_GET : $_POST;
        if (empty($data) || !is_array($data)) {
            throw new PaymentException('获取参数失败!');
        }

        $this->rawData = $data;

        //判断交易状态
        if (empty($data['trade_status']) || !in_array($data['trade_status'], ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            throw new PaymentException('交易失败!');
        }

        // 检查签名
        $flag = $this->verifySign($data);
        if (!$flag) {
            throw new PaymentException('验证签名失败!');
        }

        return $data;
    }

    /**
     *  回复成功
     */
    public function returnSuccess()
    {
        echo 'success';
    }

    /**
     *  回复失败
     */
    public function returnFailure()
    {
        echo 'failure';
    }

}