# Alipay 支付宝

## 配置

```php

$config = array(
	 //支付宝分配给开发者的应用ID
	'app_id' => '',
	
	//签名方式,现在只支持RSA2
	'sign_type' => 'RSA2',
	
	//支付宝公钥(证书路径或key,请填写绝对路径)
	'ali_public_key' => '/your/path/cert/alipay/alipay_rsa_public_key.pem',
	
	//用户应用私钥(证书路径或key,请填写绝对路径)
	'rsa_private_key' => '/your/path/cert/alipay/alipay_rsa_private_key.pem',
	);
),
```


## 即时到账接口

1. 判断设备选择实例化

	Web网页
	
	```php
	$alipay = new \JiaLeo\Payment\Alipay\WebPay($config);
	```
	
	Wap(手机网页)
	
	```php
	$alipay = new \JiaLeo\Payment\Alipay\WapPay($config);
	```
	
	App
	
	```php
	$alipay = new \JiaLeo\Payment\Alipay\AppPay($config);
	```

2. 设置支付参数

	代码示例:
	
	```php
	$pay_data = array(
	    'body' => $data['body'], //内容
	    'subject' => $data['subject'], //标题
	    'out_trade_no' => $out_trade_no, //商户订单号
	    'timeout_express' => '30m', //取值范围：1m～15d。m-分钟，h-小时，d-天，1c-当天
	    'total_amount' => $data['amount'], //支付价格(单位:分)
	    'passback_params' => $alipay->setPassbackParams($return_params), //额外字段，回调时
	    'notify_url' => 'http://domain/api/alipay/notifies', //后台回调地址
	    'return_url' => 'http://domain/api/alipay/success' //支付成功后跳转的地址
	);
	
	```

3. 调用执行函数

	代码示例:
	
	```php
	$url = $alipay->handle($pay_data);
	
	if ($data['device'] == 'app') {
	    return $url;
	} else {
	    return redirect($url);
	}
	```

4. 处理支付宝回调

	```php
	$alipay = new \JiaLeo\Payment\Alipay\Notify($config);
	
	try {
	    //验签
	    $data = $alipay->handle();
	}
	catch (\Exception $e) {
	    $error_msg = $e->getMessage();
	    $alipay->returnFailure();
	}
	```

#### 退款

示例代码：

```php
try{
$refund_data = [
'out_request_no' => $out_refund_no, //退款订单号
'refund_amount' => $refund_amount, //退款金额，(单位：分)
'out_trade_no' => $out_trade_no, //订单号
];
$res = $alipay_refund->handle($refund_data);

} catch (\Exception $e) {
throw new ApiException($e->getMessage());
}
```

#### 转账到支付宝余额

示例代码：

```php
$alipay = new \JiaLeo\Payment\Alipay\Transfer($config);

$out_biz_no = date('YmdHis') . rand(10000, 99999);

$payData = [
	'out_biz_no' => $out_biz_no, //订单支付时传入的商户订单号,不能和 trade_no同时为空。
	//'out_trade_no' => '149629776262420', //支付宝交易号，和商户订单号不能同时为空
	'amount' => $res->amount, //退款金额,单位:分
	'payee_account' => $res->alipay_account, //标识一次退款请求，同一笔交易多次退款需要保证唯一，如需部分退款，则此参数必传。
	'remark' => '用户提现'
];

try {
	$body = $alipay->handle($payData);
} catch (\Exception $e) {
	$error_msg = $e->getMessage();
	throw new ApiException($error_msg);
}

