<?php

namespace JiaLeo\Payment\Paypal;

use JiaLeo\Payment\Common\PaymentException;

class Notify extends BasePaypalPay
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

        //IPN验证
        $flag = $this->notifyValidate($data);
        if (!$flag) {
            throw new PaymentException('IPN验证失败!');
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