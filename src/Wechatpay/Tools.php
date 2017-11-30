<?php
namespace JiaLeo\Payment\Wechatpay;

use JiaLeo\Payment\Common\PaymentException;

class Tools extends BaseWechatpay
{


    public $errorCode;  //错误代码
    public $errorCodeDes;   //错误描述

    /**
     * 获取RSA公钥
     * @return bool|mixed
     * @throws PaymentException
     */
    public function getPublicKey()
    {
        $url = 'https://fraud.mch.weixin.qq.com/risk/getpublickey';

        $data = array(
            'mch_id' => $this->config['mchid'],
            'nonce_str' => $this->getNonceStr(),
            'sign_type' => 'MD5'
        );

        //签名
        $data['sign'] = $this->makeSign($data);

        //转换成xml
        $xml = $this->toXml($data);
        $result = $this->postXmlCurl($url, $xml, $this->config['sslcert_path'], $this->config['sslkey_path']);
        $get_result = $this->fromXml($result);

        try {
            if (!isset($get_result['return_code']) || $get_result['return_code'] != 'SUCCESS') {
                throw new PaymentException('调起获取RSA公钥接口失败!错误信息:' . isset($get_result['return_msg']) ? $get_result['return_msg'] : $get_result);
            }

            if ($get_result['result_code'] != 'SUCCESS') {
                throw new PaymentException('调起获取RSA公钥接口失败!错误信息:' . isset($get_result['err_code_des']) ? $get_result['err_code_des'] : $get_result);
            }

            if (empty($get_result['pub_key'])) {
                throw new PaymentException('获取pub_key字段错误!');
            }
        } catch (\Exception $e) {
            $this->errorCode = $get_result['err_code'];
            $this->errorCodeDes = $get_result['err_code_des'];

            return false;
        }

        return $get_result;
    }


}