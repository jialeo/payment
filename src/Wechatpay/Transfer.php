<?php
namespace JiaLeo\Payment\Wechatpay;

use JiaLeo\Payment\Common\PaymentException;

class Transfer extends BaseWechatpay
{
    public $payUrl = '/mmpaymkttransfers/promotion/transfers';

    public $errorCode;  //错误代码
    public $errorCodeDes;   //错误描述

    /**
     * 银行编码
     * @var array
     */
    public $bankCode = array(
        '1002' => '工商银行',
        '1005' => '农业银行',
        '1026' => '中国银行',
        '1003' => '建设银行',
        '1001' => '招商银行',
        '1066' => '邮储银行',
        '1020' => '交通银行',
        '1004' => '浦发银行',
        '1006' => '民生银行',
        '1009' => '兴业银行',
        '1010' => '平安银行',
        '1021' => '中信银行',
        '1025' => '华夏银行',
        '1027' => '广发银行',
        '1022' => '光大银行',
        '1032' => '北京银行',
        '1056' => '宁波银行',
    );

    /**
     * 企业付款到零钱
     * @return mixed
     * @throws PaymentException
     */
    public function handle($params)
    {
        //检查订单号是否合法
        if (empty($params['partner_trade_no'])) {
            throw new PaymentException('商户订单号不能为空');
        }

        // 需要转账金额不能低于1
        if (empty($params['amount']) || $params['amount'] < 100) {
            throw new PaymentException('转账金额不能为空,且不能低于 1 元');
        }

        if (empty($params['openid'])) {
            throw new PaymentException('openid不能为空');
        }

        $is_check_name = !empty($params['check_name']) ? true : false;
        if (empty($params['re_user_name']) && $is_check_name) {
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
            'mch_appid' => $this->config['appid'],
            'mchid' => $this->config['mchid'],
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

        try {
            if (!isset($get_result['return_code']) || $get_result['return_code'] != 'SUCCESS') {
                throw new PaymentException('调起转账失败!错误信息:' . isset($get_result['return_msg']) ? $get_result['return_msg'] : $get_result);
            }

            if ($get_result['result_code'] != 'SUCCESS') {
                throw new PaymentException('调起转账失败!错误信息:' . isset($get_result['err_code_des']) ? $get_result['err_code_des'] : $get_result);
            }
        } catch (\Exception $e) {
            $this->errorCode = $get_result['err_code'];
            $this->errorCodeDes = $get_result['err_code_des'];

            return false;
        }


        return $get_result;
    }

    /**
     * 转账到银行卡
     * @param $params
     * @return bool
     * @throws PaymentException
     */
    public function handleToBank($params)
    {
        $this->payUrl = '/mmpaysptrans/pay_bank';

        //检查订单号是否合法
        if (empty($params['partner_trade_no'])) {
            throw new PaymentException('商户订单号不能为空');
        }

        // 需要转账金额不能低于1
        if (empty($params['amount']) || $params['amount'] < 100) {
            throw new PaymentException('转账金额不能为空,且不能低于 1 元');
        }

        if (empty($params['bank_no'])) {
            throw new PaymentException('bank_no不能为空');
        }

        if (empty($params['true_name'])) {
            throw new PaymentException('true_name不能为空');
        }

        if (empty($params['bank_code']) || !isset($this->bankCode[$params['bank_code']])) {
            throw new PaymentException('bank_code不能为空或银行编号错误!');
        }

        $data = array(
            'mch_id' => $this->config['mchid'],
            'nonce_str' => $this->getNonceStr(),
            'partner_trade_no' => $params['partner_trade_no'],
            'amount' => $params['amount'],
            'enc_bank_no' => $this->rsaPublicEncrypt($params['bank_no']),
            'enc_true_name' => $this->rsaPublicEncrypt($params['true_name']),
            'bank_code' => $params['bank_code'],

        );

        !isset($params['desc']) ?: $data['desc'] = $params['desc'];

        //签名
        $data['sign'] = $this->makeSign($data);

        //转换成xml
        $xml = $this->toXml($data);
        $result = $this->postXmlCurl($this->gateway . $this->payUrl, $xml, $this->config['sslcert_path'], $this->config['sslkey_path']);
        $get_result = $this->fromXml($result);

        try {
            if (!isset($get_result['return_code']) || $get_result['return_code'] != 'SUCCESS') {
                throw new PaymentException('调起转账失败!错误信息:' . isset($get_result['return_msg']) ? $get_result['return_msg'] : $get_result);
            }

            if ($get_result['result_code'] != 'SUCCESS') {
                throw new PaymentException('调起转账失败!错误信息:' . isset($get_result['err_code_des']) ? $get_result['err_code_des'] : $get_result);
            }
        } catch (\Exception $e) {
            $this->errorCode = $get_result['err_code'];
            $this->errorCodeDes = $get_result['err_code_des'];

            return false;
        }

        return $get_result;
    }

    /**
     * 查询企业打款信息
     * @param $params
     *        $params[partner_trade_no] int 商户订单号
     *        $params[nonce_str] string 随机字符串，长度小于32位
     * @return bool|mixed
     * @throws PaymentException
     */
    public function queryBankOrder($params)
    {
        $this->payUrl = '/mmpaysptrans/query_bank';

        $param = [
            'mch_id' => $this->config['mchid'],
            'partner_trade_no' => $params['partner_trade_no'],
            'nonce_str' => $params['nonce_str'],
        ];

        // 签名
        $sign = $this->makeSign($param);
        $param['sign'] = $sign;

        //转换成xml
        $xml = $this->toXml($param);
        $result = $this->postXmlCurl($this->gateway . $this->payUrl, $xml, $this->config['sslcert_path'], $this->config['sslkey_path']);
        $get_result = $this->fromXml($result);

        try {
            if (!isset($get_result['return_code']) || $get_result['return_code'] != 'SUCCESS') {
                throw new PaymentException('查询企业打款到银行卡信息!错误信息:' . isset($get_result['return_msg']) ? $get_result['return_msg'] : $get_result);
            }

            if ($get_result['result_code'] != 'SUCCESS') {
                throw new PaymentException('查询企业打款到银行卡信息!错误信息:' . isset($get_result['err_code_des']) ? $get_result['err_code_des'] : $get_result);
            }
        } catch (\Exception $e) {
            $this->errorCode = $get_result['err_code'];
            $this->errorCodeDes = $get_result['err_code_des'];

            return false;
        }

        return $get_result;
    }

    /**
     * RSA公钥加密
     * @param $str
     * @return string
     * @throws PaymentException
     */
    public function rsaPublicEncrypt($str)
    {
        $publicstr = file_get_contents($this->config['transfer_rsa_public_path']);
        $publickey = openssl_pkey_get_public($publicstr); // 读取公钥
        if (!$publickey) {
            throw new PaymentException('读取公钥错误!');
        }

        $r = openssl_public_encrypt($str, $encrypted, $publickey, OPENSSL_PKCS1_OAEP_PADDING);

        if (!$r) {
            throw new PaymentException('公钥加密失败错误!');
        }
        return base64_encode($encrypted);
    }
}