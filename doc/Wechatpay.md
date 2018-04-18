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
    'transfer_rsa_public_path' => '/your/path/cert/wechatpay/rsa_public.pem',	//企业转账到银行卡rsa公钥证书文件路径
);
```

## 即时到账接口


1. 判断设备选择实例化的

公众号

```php
$wechatpay = new \JiaLeo\Payment\Wechatpay\MpPay($config);  
```

App

```php
$wechatpay = new \JiaLeo\Payment\ Wechatpay\AppPay($config);
```

H5

```php
$wechatpay = new \JiaLeo\Payment\ Wechatpay\H5Pay($config);
```

Native

```
$wechatpay = new \JiaLeo\Payment\ Wechatpay\NativePay($config);
```



示例代码：

```php
if ($data['device'] == 'mp') {
	$wechatpay = new \JiaLeo\Payment\Wechatpay\MpPay($config);
} elseif ($data['device'] == 'app') {
	$wechatpay = new \JiaLeo\Payment\Wechatpay\AppPay($config);
}
elseif($data['device'] == 'h5'){
	$wechatpay = new \JiaLeo\Payment\Wechatpay\H5Pay($config);
}
elseif ($data['device'] == 'native') {
    $wechatpay = new \JiaLeo\Payment\Wechatpay\NativePay($config);
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

MpPay和AppPay返回的url为微信的签名，只要直接给到客户端给可以了。


H5Pay为微信返回的url，需要额外重定向和指定支付完成后的跳转地址：

NativePay返回的是一个微信支付url,使用第三方库直接转换成二维码即可

### 支付回调处理

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

### 退款

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

### 企业付款

* 企业付款到零钱

示例代码：

```php
$config = config('payment.wechatpay.mp');
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
	var_dump($payment->errorCode, $payment->errorCodeDes);
}
var_dump($res);
```

* 企业付款到银行卡

	1. 调用获取RSA公钥API获取RSA公钥，落地成本地文件，假设为rsa_public.pem
	
		```php
		$config = config('payment.wechatpay.che');
		$wechatpay = new \JiaLeo\Payment\Wechatpay\Tools($config);
		$url = $wechatpay->getPublicKey();
		var_dump($url['pub_key']);
		```
		
	2. PKCS#1 转 PKCS#8 (微信获取的是PKCS#1的格式，php必须读取PKCS#8)

		```
		openssl rsa -RSAPublicKey_in -in <filename> -pubout
		```
		
		替换原证书内容
		
	3. 执行
	
		```php
		$config = config('payment.wechatpay.che');
		$wechatpay = new \JiaLeo\Payment\Wechatpay\Transfer($config);
		
		$data = array(
			'partner_trade_no' => time(),
			'amount' => 100,
			'bank_no' => '62148312XXXXXX',
			'true_name' => 'XXX',
			'bank_code' => '1001',
			'desc' => 'test',
		);
		
		
		var_dump($wechatpay->handleToBank($data));
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

