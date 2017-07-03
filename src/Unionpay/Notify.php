<?php

namespace JiaLeo\Payment\Unionpay;

use JiaLeo\Payment\Common\PaymentException;

class Notify extends BaseUnionpay
{
    public $rawData = array();

    /**
     * 回调
     * @return mixed
     * @throws PaymentException
     */
    public function handle()
    {

        if (empty($_POST) && empty($_GET)) {
            return false;
        }
        $data = $_POST ?  : $_GET;

        $this->rawData = $data;

        $res =  \JiaLeo\Payment\Unionpay\Utils\Rsa::verify($data,$this->config['cert_dir']);
        if(!$res){
            throw new PaymentException('验证失败!');
        }

        //判断交易状态   判断respCode=00或A6即可认为交易成功
        if (empty($data['respCode']) || !in_array($data['respCode'], ['00', 'A6'])) {
            throw new PaymentException('交易失败!');
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
        //echo 'failure';
        throw new PaymentException('failure');
    }
}