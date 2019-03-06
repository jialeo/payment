## 环境要求

* PHP >= 5.6

## 当前支持的接口

### 支付宝接口

* 即时到账（wap,web,app）
* 交易退款接口
* 单笔转账到支付宝

### 微信接口

* 公众号支付
* APP支付
* H5支付
* 扫码支付
* 企业付款到零钱
* 企业付款到银行卡
* 交易退款接口
* 公众号现金红包

### 银联

* 即时到账(wap,web,app)
* 交易退款接口

### Paypal

* APP,网关支付
* 退款
* MassPay(多人转账)

### 苹果

* 内购支付


## 文档

*  [支付宝](doc/Alipay.md)
*  [微信支付](doc/Wechatpay.md)
*  [苹果支付](doc/Applepay.md)
*  银联支付  (拖延症中...等待够100star再写)
*  Paypal	  (拖延症中...等待够100star再写)

## 安装使用

* 通过composer(推荐)

	```
	composer require "jialeo/payment"
	```
    
* composer.json

	```
	"require": {
        "jialeo/payment": "0.*"
    }
	```
    
	然后运行

	```
	composer update jialeo/payment
	```

