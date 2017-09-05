<?php
namespace JiaLeo\Payment\Paypal;

use JiaLeo\Payment\Common\PaymentException;

class Refund extends BasePaypalPay
{
    /**
     * PayPal退款
     * return 
     * array(8) {
     *  ["id"]=>
     *  string(17) "4M970811EP7222338"
     *  ["create_time"]=>
     *  string(20) "2017-08-14T06:35:14Z"
     *  ["update_time"]=>
     *  string(20) "2017-08-14T06:35:14Z"
     *  ["state"]=>
     *  string(9) "completed"
     *  ["amount"]=>
     *  array(2) {
     *    ["total"]=>
     *    string(6) "798.00"
     *    ["currency"]=>
     *    string(3) "USD"
     *  }
     *  ["sale_id"]=>
     *  string(17) "9HS19516C7516141J"
     *  ["parent_payment"]=>
     *  string(28) "PAY-2EE21012B68178407LGGHG6Y"
     *  ["links"]=>
     *  array(3) {
     *    [0]=>
     *    array(3) {
     *      ["href"]=>
     *      string(67) "https://api.sandbox.paypal.com/v1/payments/refund/4M970811EP7222338"
     *      ["rel"]=>
     *      string(4) "self"
     *      ["method"]=>
     *      string(3) "GET"
     *    }
     *    [1]=>
     *    array(3) {
     *      ["href"]=>
     *      string(79) "https://api.sandbox.paypal.com/v1/payments/payment/PAY-2EE21012B68178407LGGHG6Y"
     *      ["rel"]=>
     *      string(14) "parent_payment"
     *      ["method"]=>
     *      string(3) "GET"
     *    }
     *    [2]=>
     *    array(3) {
     *      ["href"]=>
     *      string(65) "https://api.sandbox.paypal.com/v1/payments/sale/9HS19516C7516141J"
     *      ["rel"]=>
     *      string(4) "sale"
     *      ["method"]=>
     *      string(3) "GET"
     *    }
     *  }
     *}
     *
     * @param string $txn_id //交易流水ID
     * @return array                [description]
     */
    public function handle($txn_id)
    {
        $url = $this->end_point."/v1/payments/sale/$txn_id/refund";
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
        var_dump($output);exit;
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