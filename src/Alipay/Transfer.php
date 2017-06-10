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

        // 需要转账金额不能低于0.01
        if (empty($params['amount']) || bccomp($params['amount'] / 100, '0.1', 2) === -1) {
            throw new PaymentException('转账金额不能为空,且不能低于 0.1 元');
        }

        $params['amount'] = (string) ($params['amount'] / 100);
        $params['payee_type'] = 'ALIPAY_LOGONID';


        //定义公共参数
        $publicParams = array(
            'app_id' => $this->config['app_id'],
            'method' => 'alipay.fund.trans.toaccount.transfer',
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
        );

        //生成biz_content参数
        $biz_content = $params;
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

        if (!$responseTxt ) {
            throw new PaymentException('网络发生错误');
        }

        $this->refundReturnData = $responseTxt;

        $body = json_decode($responseTxt, true);

        if (!$body) {
            throw new PaymentException('返回数据有误!');
        }

        if (empty($body['alipay_fund_trans_toaccount_transfer_response']['code'])) {
            throw new PaymentException('返回数据有误!');
        }

        if ($body['alipay_fund_trans_toaccount_transfer_response']['code'] != 10000) {
            throw new PaymentException($body['alipay_fund_trans_toaccount_transfer_response']['sub_msg']);
        }

        // 验证签名，检查支付宝返回的数据
        $preStr = json_encode($body['alipay_fund_trans_toaccount_transfer_response']);

        $ali_public_key = $this->getRsaKeyValue($this->config['ali_public_key'], 'public');
        $rsa = new Utils\Rsa2Encrypt($ali_public_key);
        $flag = $rsa->rsaVerify($preStr, $body['sign']);
        if (!$flag) {
            throw new PaymentException('支付宝返回数据被篡改。请检查网络是否安全！');
        }

        return $body;
    }
}