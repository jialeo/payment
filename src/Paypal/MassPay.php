<?php

namespace JiaLeo\Payment\Paypal;

use JiaLeo\Payment\Common\PaymentException;

class MassPay extends BasePaypalPay
{
    /**
     * MassPay 多人转账
     * @param array $receivers //接收者二维数组
     * @throws PaymentException
     */
    public function handle($receivers, $params)
    {
        //检查参数
        $this->checkParams($receivers, $params);

        //确定使用Paypal email账号为目标转账
        $email_subject = urlencode($params['email_subject']);
        $receiver_type = 'EmailAddress';
        $currency = urlencode($params['currency_code']); // 其他币种 ('GBP', 'EUR', 'JPY', 'CAD', 'AUD')

        // 拼装请求字段
        $nvp_str = "&EMAILSUBJECT=$email_subject&RECEIVERTYPE=$receiver_type&CURRENCYCODE=$currency";

        $receivers_array = array();

        //循环重构数组
        for ($i = 0; $i < count($receivers); $i++) {
            $receivers_array[$i] = $receivers[$i];
            $receivers_array[$i]['unique_id'] = 'id_' . $i;
        }

        //设置Nvp请求字段
        $nvp_str = $this->setNvpParams($receivers_array);

        // 执行Paypal Nvp API操作
        $result = $this->nvpHttpPost('MassPay', $nvp_str);

        if ("SUCCESS" == strtoupper($result["ACK"]) || "SUCCESSWITHWARNING" == strtoupper($result["ACK"])) {
            return true;
        } else {
            throw new PaymentException(urldecode($result['L_LONGMESSAGE0']));
        }
    }

    /**
     * 检查参数
     * @param $params
     * @throws PaymentException
     */
    public function checkParams($receivers, $params)
    {
        //检测必填参数
        if (!isset($params['email_subject'])) {
            throw new PaymentException("缺少MassPay接口必填参数email_subject");
        }

        if (!isset($params['currency_code'])) {
            throw new PaymentException("缺少MassPay接口必填参数currency_code");
        }

        //判断数据
        if (count($receivers) < 1) {
            throw new PaymentException("参数错误", 'PARAMS_ERROR');
        }
    }
}