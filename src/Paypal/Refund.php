<?php
namespace JiaLeo\Payment\Paypal;

use JiaLeo\Payment\Common\PaymentException;

class Refund extends BasePaypalPay
{
    /**
     * PayPal退款
     * @param string $txn_id //交易流水ID
     * @return array                [description]
     */
    public function handle($txn_id)
    {
        $url = $this->end_point . "/v1/payments/sale/$txn_id/refund";
        $access_token = $this->getAccessToken();
        if (!$access_token) {
            return false;
        }

        $header = array(
            "Content-Type: application/json",
            "Authorization: Bearer $access_token"
        );
        $output = $this->httpPostPaypal($url, '{}', $header);

        if (false === $output || false === $access_token) {
            //网络异常，请您稍后重试
            throw new PaymentException("网络异常，请您稍后重试");
        }

        $output = json_decode($output, TRUE);
        var_dump($output);
        exit;
        if ("TRANSACTION_REFUSED" == $output['name']) {
            //退款已提交，请勿重复
            throw new PaymentException("退款已提交，请勿重复");
        }

        if ($txn_id == $output['sale_id'] && "completed" == $output['completed']) {
            //退款已提交,改变订单状态等
            return $output;
        }
        return false;
    }
}