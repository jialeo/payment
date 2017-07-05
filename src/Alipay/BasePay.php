<?php
namespace JiaLeo\Payment\Alipay;

use JiaLeo\Payment\Common\PaymentException;

class BasePay extends BaseAlipay
{
    protected $method;

    /**
     * 即时到账接口
     */
    public function handle($params,$is_app = false)
    {

        // 检查订单号是否合法
        if (empty($params['out_trade_no']) || mb_strlen($params['out_trade_no']) > 64) {
            throw new PaymentException('订单号不能为空，并且长度不能超过64位');
        }

        // 检查金额不能低于0.01
        if (bccomp($params['total_amount'] / 100, '0.01', 2) === -1) {
            throw new PaymentException('支付金额不能低于 0.01 元');
        }

        // 检查 商品名称 与 商品描述
        if (empty($params['subject'])) {
            throw new PaymentException('必须提供 商品的标题/交易标题/订单标题/订单关键字 等');
        }

        // 检查商品类型
        if (empty($params['goods_type'])) {// 默认为实物类商品
            $params['goods_type'] = 1;
        } elseif (!in_array($params['goods_type'], [0, 1])) {
            throw new PaymentException('商品类型可取值为：0-虚拟类商品  1-实物类商品');
        }

        // 返回参数进行urlencode编码
        if (!empty($params['passback_params']) && !is_string($params['passback_params'])) {
            throw new PaymentException('回传参数必须是字符串');
        } elseif (!empty($params['passback_params'])) {
            $params['passback_params'] = urlencode($params['passback_params']);
        }

        //dd($params['passback_params']);

        $params['total_amount'] = (string)($params['total_amount'] / 100);
        $params['product_code'] = 'FAST_INSTANT_TRADE_PAY';

        //定义公共参数
        $publicParams = array(
            'app_id' => $this->config['app_id'],
            'method' => $this->method,
            'format' => 'JSON',
            'charset' => 'utf-8',
            'sign_type' => 'RSA2',
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => '1.0',
        );

        //生成biz_content参数
        $biz_content = $params;
        unset($biz_content['notify_url']);
        unset($biz_content['return_url']);
        $biz_content = $this->createbizContent($biz_content);
        $publicParams['biz_content'] = $biz_content;

        //需要签名的参数
        $sign_params = array_merge($params, $publicParams);

        //参数重新排序
        $sign_params = $this->arraySort($sign_params);

        //生成待签名字符串
        $str = $this->createLinkstring($sign_params);
       // dd($str);

        //提取私钥
        $rsa_private_key = $this->getRsaKeyValue($this->config['rsa_private_key']);

        //加密字符串
        $rsa = new Utils\Rsa2Encrypt($rsa_private_key);
        $sign = $rsa->encrypt($str);

        $sign_params['sign'] = $sign;

        if($is_app) {
            return http_build_query($sign_params);
        }
        else {
            return $this->gateway . '?' . http_build_query($sign_params);
        }
    }
}