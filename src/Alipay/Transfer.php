<?php

namespace JiaLeo\Payment\Alipay;

use JiaLeo\Payment\Common\PaymentException;

class Transfer extends BaseAlipay
{
    /**
     * 单笔转账到支付宝账户
     * @return mixed
     * @throws PaymentException
     */
    public function handle($params)
    {
        //检查订单号是否合法
        if (empty($params['out_biz_no']) || mb_strlen($params['out_biz_no']) > 64) {
            throw new PaymentException('商户转账唯一订单号不能为空,长度不能超过64位');
        }

        // 检查收款方账户是否合法
        if (empty($params['payee_account']) || mb_strlen($params['payee_account']) > 100) {
            throw new PaymentException('收款方账户不能为空,长度不能超过100位');
        }

        if (empty($params['payee_name']) || mb_strlen($params['payee_name']) > 100) {
            throw new PaymentException('收款方姓名不能为空,长度不能超过100位');
        }

        // 需要转账金额不能低于0.01
        if (empty($params['amount']) || bccomp($params['amount'] / 100, '0.1', 2) === -1) {
            throw new PaymentException('转账金额不能为空,且不能低于 0.1 元');
        }

        $biz_params['out_biz_no'] = $params['out_biz_no'];
        $biz_params['trans_amount'] = (string)($params['amount'] / 100);
        $biz_params['product_code'] = 'TRANS_ACCOUNT_NO_PWD';
        $biz_params['biz_scene'] = 'DIRECT_TRANSFER';
        $biz_params['order_title'] = $params['order_title'];
        $biz_params['payee_info']['identity'] = $params['payee_account'];
        $biz_params['payee_info']['identity_type'] = 'ALIPAY_LOGON_ID';
        $biz_params['payee_info']['name'] = $params['payee_name'];
        $biz_params['remark'] = $params['remark'] ?? '';

        //定义公共参数
        $publicParams = array(
            'app_id' => $this->config['app_id'],
            'method' => 'alipay.fund.trans.uni.transfer',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
        );

        if (!empty($this->config['app_cert_path']) && !empty($this->config['alipay_root_cert_path'])) {

            //
            if (!file_exists($this->config['app_cert_path'])) {
                throw new PaymentException('应用证书不存在!');
            }

            if (!file_exists($this->config['alipay_root_cert_path'])) {
                throw new PaymentException('支付宝根证书不存在!');
            }

            $publicParams['app_cert_sn'] = $this->getCertSN($this->config['app_cert_path']);
            $publicParams['alipay_root_cert_sn'] = $this->getRootCertSN($this->config['alipay_root_cert_path']);
            $publicParams['alipay_root_cert_sn'] = '687b59193f3f462dd5336e5abf83c5d8_02941eef3187dddf3d3b83462e1dfcf6';
        }
        
        //生成biz_content参数
        $biz_content = $biz_params;
        $biz_content = $this->createbizContent($biz_content);
        $publicParams['biz_content'] = $biz_content;

        //需要签名的参数
        $sign_params = array_merge($params, $publicParams);

        //参数重新排序
        $sign_params = $this->arraySort($sign_params);

        //生成待签名字符串
        $str = $this->createLinkstring($sign_params);

        //提取私钥
        $rsa_private_key = $this->getRsaKeyValue($this->config['rsa_private_key']);

        //加密字符串
        $rsa = new Utils\Rsa2Encrypt($rsa_private_key);
        $sign = $rsa->encrypt($str);

        $sign_params['sign'] = $sign;
        $url = $this->gateway . '?' . http_build_query($sign_params);

        // 发起网络请求
        $curl = new \JiaLeo\Payment\Common\Curl();
        $responseTxt = $curl->get($url);

        if (!$responseTxt) {
            throw new PaymentException('网络发生错误');
        }

        $this->refundReturnData = $responseTxt;

        $body = json_decode($responseTxt, true);

        if (!$body) {
            throw new PaymentException('返回数据有误!');
        }

        if (empty($body['alipay_fund_trans_uni_transfer_response']['code'])) {
            throw new PaymentException('返回数据有误!');
        }

        // 验证签名，检查支付宝返回的数据
        $preStr = json_encode($body['alipay_fund_trans_uni_transfer_response'], JSON_UNESCAPED_UNICODE);

        $ali_public_key = $this->getRsaKeyValue($this->config['ali_public_key'], 'public');
        $rsa = new Utils\Rsa2Encrypt($ali_public_key);
        $flag = $rsa->rsaVerify($preStr, $body['sign']);
        if (!$flag) {
            throw new PaymentException('支付宝返回数据被篡改。请检查网络是否安全！');
        }

        return $body['alipay_fund_trans_uni_transfer_response'];
    }
}
