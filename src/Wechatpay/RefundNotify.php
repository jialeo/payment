<?php

namespace JiaLeo\Payment\Wechatpay;

use App\Exceptions\ApiException;
use JiaLeo\Payment\Common\PaymentException;


/**
 * 退款回调
 * Class RefundNotify
 * @package JiaLeo\Payment\Wechatpay
 */
class RefundNotify extends BaseWechatpay
{
    public $rawData = array();

    /**
     * 回调
     * @return array
     * @throws PaymentException
     */
    public function handle()
    {
        $postdata = file_get_contents("php://input");
        $get_notify = $this->fromXml($postdata);
        if (!$get_notify) {
            return false;
        }

        try {

            if (!isset($get_notify['return_code']) || $get_notify['return_code'] != 'SUCCESS') {
                throw new PaymentException('退款返回失败');
            }

            if (!isset($get_notify['req_info'])) {
                throw new PaymentException('缺少req_info字段');
            }

            //解密数据
            $req_info = base64_decode($get_notify['req_info']);
            $md5_key = md5($this->config['key']);
            $decrypted = openssl_decrypt($req_info, 'AES-256-ECB', $md5_key, OPENSSL_RAW_DATA);
            if (!$decrypted) {
                throw new PaymentException('解密数据失败!');
            }

            $decrypted_arr = $this->fromXml($decrypted);

            $get_notify = array_merge($get_notify, $decrypted_arr);

        } catch (PaymentException $e) {
            \Log::info($e->getMessage());
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