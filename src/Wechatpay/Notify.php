<?php

namespace JiaLeo\Payment\Wechatpay;

use JiaLeo\Payment\Common\PaymentException;


class Notify extends BaseWechatpay
{
    public $rawData = array();
    public $errorMsg = '';

    /**
     * 回调
     * @return array
     * @throws PaymentException
     */
    public function handle()
    {
        $postdata = file_get_contents("php://input");
        $get_notify = $this->fromXml($postdata);
        try {

            if (!isset($get_notify['return_code']) || $get_notify['return_code'] != 'SUCCESS') {
                throw new PaymentException('支付返回失败');
            }

            if (!isset($get_notify['sign'])) {
                throw new PaymentException('缺少sign字段');
            }

            //验证签名
            if ($this->makeSign($get_notify) != $get_notify['sign']) {
                throw new PaymentException('验证签名失败!');
            }

        } catch (PaymentException $e) {
            $this->errorMsg = $e->getMessage();
            return false;
        }

        return $get_notify;
    }

    /**
     *  回复成功
     */
    public function returnSuccess()
    {
        $return = array(
            'return_code' => 'SUCCESS',
            'return_msg' => 'OK'
        );
        echo $this->toXml($return);
    }

    /**
     *  回复失败
     */
    public function returnFailure($error_msg)
    {
        $return = array(
            'return_code' => 'FAIL',
            'return_msg' => $error_msg
        );
        echo $this->toXml($return);
    }

}