<?php

namespace JiaLeo\Payment\Applepay;

use JiaLeo\Payment\Common\PaymentException;

/**
 * 苹果内购
 * Class IAP
 * @package JiaLeo\Payment\Applepay
 */
class IAP
{

    /**
     * @param $receipt
     * @param bool $sanbox
     * @return mixed
     * @throws PaymentException
     */
    public function checkPay($receipt, $sanbox = false)
    {

        if (strlen($receipt) < 20) {
            throw new PaymentException('非法参数');
        }

        $post_data = json_encode(
            array('receipt-data' => $receipt)
        );

        $response = $this->postData($post_data, $sanbox);

        return $response;
    }

    /**
     * 发送数据
     * @param $post_data
     * @param bool $sanbox
     * @return mixed
     * @throws PaymentException
     */
    public function postData($post_data, $sanbox = false)
    {
        //如果是沙盒模式，请求苹果测试服务器,反之，请求苹果正式的服务器
        if ($sanbox) {
            $endpoint = 'https://sandbox.itunes.apple.com/verifyReceipt';
        } else {
            $endpoint = 'https://buy.itunes.apple.com/verifyReceipt';
        }

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  //这两行一定要加，不加会报SSL 错误
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $errmsg = curl_error($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        //判断时候出错，抛出异常
        if ($errno != 0) {
            throw new PaymentException('出现异常');
        }

        //判断返回的数据是否是对象
        if (!is_array($data)) {
            throw new PaymentException('响应数据异常');
        }

        //沙盒模式的,重新请求一下
        if ($data['status'] == 21007) {
            return $this->postData($post_data, true);
        }

        return $data;
    }
}