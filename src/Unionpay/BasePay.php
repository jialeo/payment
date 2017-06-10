<?php
namespace JiaLeo\Payment\Unionpay;

use JiaLeo\Payment\Common\PaymentException;

class BasePay extends BaseUnionpay
{

    protected $transUrl;     //交易请求地址


    //支付基本参数
    public $baseConsumeParams = array(

        //以下信息非特殊情况不需要改动
        'version' => '5.0.0',                 //版本号
        'encoding' => 'utf-8',                  //编码方式
        'txnType' => '01',                      //交易类型
        'txnSubType' => '01',                  //交易子类
        'bizType' => '000201',                  //业务类型
        'signMethod' => '01',                  //签名方法
        'channelType' => '08',                  //渠道类型，07-PC，08-手机
        'accessType' => '0',                  //接入类型
        'currencyCode' => '156',              //交易币种，境内商户固定156
    );

    /**
     * 消费
     */
    public function consume(array $get_params, $is_app = false)
    {
        //验证商户订单号参数
        if (empty($get_params['orderId'])) {
            throw new PaymentException('缺少商户订单号');
        }

        //验证缺少交易金额参数
        if (empty($get_params['txnAmt']) || intval($get_params['txnAmt']) < 0) {
            throw new PaymentException('缺少交易金额');
        }

        //订单发送时间，格式为YYYYMMDDhhmmss，取北京时间
        $get_params['txnTime'] = date('YmdHis');

        //商户号ID
        $get_params['merId'] = $this->config['mer_id'];

        //合并参数
        $params = array_merge($this->baseConsumeParams, $get_params);

        $cert_path = $this->config['private_key_path'];
        $cert_pwd = $this->config['private_key_pwd'];

        //获取证书key
        $certId = Utils\Rsa::getCertId($cert_path, $cert_pwd);
        $params['certId'] = $certId;

        //签名
        $params['signature'] = Utils\Rsa::getParamsSignatureWithRSA($params, $cert_path, $cert_pwd);
        //dd($params);

        if ($is_app) {
            return [
                'params' => $params,
                'url' => $this->transUrl,
            ];
        } else {
            //抛出表单---前台回调
            $html_form = Utils\Rsa::createAutoFormHtml($params, $this->transUrl);
            return $html_form;
            //dump( $html_form);
            //exit;
        }
    }
}