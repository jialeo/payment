# Applypay 苹果支付


## IAP 内购支付验证


1. 实例化


```
$apple_pay = new \JiaLeo\Payment\Applepay\IAP();
```

```
$receipt = $_POST['receipt'];		//支付凭证
$sanbox = true;		//是否沙盒模式

//验签
$data = $apple_pay->checkPay($receipt, sanbox);

if ($data['status'] != 0) {
    throw new Exception('购买状态错误!');
}

var_dump($data);
```