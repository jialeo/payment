# Wechatpay 微信支付

## 配置

```php
$config = array(
    'appid' => '',			//填写高级调用功能的app id
    'appsecret' => '', 		//填写高级调用功能的app secret
    'mchid' => '',			//商户id
    'key' => '', 			//填写你设定的key
    'sslcert_path' => '/your/path/cert/wechatpay/apiclient_cert.pem'
    'sslkey_path' => '/your/path/cert/wechatpay/apiclient_key.pem',
);
```

## 即时到账接口

示
例代码：

```php
if ($data['device'] == 'wap') {
	$wechatpay = new \JiaLeo\Payment\Wechatpay\MpPay($config);
} elseif ($data['device'] == 'app') {
	$wechatpay = new \JiaLeo\Payment\Wechatpay\AppPay($config);
}

$out_trade_no = date('YmdHis') . rand(10000, 99999);

$pay_data = [
	'body' => $data['body'], //内容
	'attach' => $wechatpay->setPassbackParams($return_params), //商家数据包
	'out_trade_no' => $out_trade_no, //商户订单号
	'total_fee' => $data['amount'], //支付价格(单位:分)
	'openid' => $openid,
	'notify_url' => 'http://domain/api/wechatpay/notifies/' . $data['device'] //后台回调地址
];

$url = $wechatpay->handle($pay_data);
```

返回的url为微信的签名

#
## 支付回调处理

示例代码：

```php
$wechatpay = new \JiaLeo\Payment\Wechatpay\Notify($config);

try {
	//验签
	$data = $wechatpay->handle();

	$wechatpay->returnSuccess();
	$result = true;
} catch (\Exception $e) {
	$error_msg = $e->getMessage();
	$wechatpay->returnFailure($error_msg);
}
```

#
## 退款

示例代码：

```php
$wechatpay_refund = new \JiaLeo\Payment\Wechatpay\Refund($config);
$out_refund_no = date('YmdHis') . rand(10000, 99999);

//原路退款
$refund_data = [
	'out_refund_no' => $out_refund_no,//退款商户单号
	'total_fee' => $amount, //原订单金额（单位分）
	'refund_fee' => $refund_amount, //退款金额（单位分）
	'out_trade_no' => $out_trade_no //原订单商户单号
];
$wechatpay_refund->handle($refund_data);
```

#
## 企业付款

示例代码：

```php
$payment = new \JiaLeo\Payment\Wechatpay\Transfer($config);

$params = array(
'partner_trade_no' => time() . rand(10000, 99999), //转账订单号
'openid' => 'oErxPsxn6XTQQyFzauQW9qZYtI_k', //openid
'amount' => 100, //转账金额(单位:分)
'desc' => '测试转账', //备注
//'check_name' => true //是否验证实名
);
$res = $payment->handle($params);

if (!$res) {
	dump($payment->errorCode, $payment->errorCodeDes);
}

dump($res);
```


## 红包

普通红包：

```php
$config = config('payment.wechatpay.mp');
$payment = new \JiaLeo\Payment\Wechatpay\RedPack($config);

$params = array(
	'mch_billno' => time() . rand(10000, 99999),
	'send_name' => '汉子科技',
	'openid' => 'oErxPsxn6XTQQyFzauQW9qZYtI_k',
	'wishing' => '汉子科技恭喜发财',
	'act_name' => '汉子科技哈哈',
	'remark' => '备注',
	'total_amount' => 300
);
$res = $payment->sendRedPack($params);

if (!$res) {
	dump($payment->errorCode, $payment->errorCodeDes);
}

dump($res);
```

裂变红包：

```php
$config = config('payment.wechatpay.mp');
$payment = new \JiaLeo\Payment\Wechatpay\RedPack($config);

$params = array(
	'mch_billno' => time() . rand(10000, 99999),
	'send_name' => '汉子科技',
	'openid' => 'oErxPsxn6XTQQyFzauQW9qZYtI_k',
	'wishing' => '汉子科技恭喜发财',
	'act_name' => '汉子科技哈哈',
	'remark' => '备注',
	'total_amount' => 300,
	'total_num' => 3
);
$res = $payment->sendGroupRedPack($params);

if (!$res) {
	dump($payment->errorCode, $payment->errorCodeDes);
}

dump($res);
```