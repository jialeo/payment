<?php
namespace JiaLeo\Payment\Alipay;

use App\Exceptions\ApiException;
use JiaLeo\Payment\Common\PaymentException;
use JiaLeo\Payment\Common\Curl;

class BasePay extends BaseAlipay
{
    protected $method;
    protected $productCode;

    /**
     * 即时到账接口
     */
    public function handle($params, $is_app = false, $is_qrcode = false)
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

        if (!$is_qrcode) {
            if (empty($params['product_code'])) {
                $params['product_code'] = $this->productCode;
            }
        }

        // 返回参数进行urlencode编码
        if (!empty($params['passback_params']) && !is_string($params['passback_params'])) {
            throw new PaymentException('回传参数必须是字符串');
        }

        $params['total_amount'] = (string)($params['total_amount'] / 100);

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

        if (!empty($this->config['app_cert_path']) && !empty($this->config['alipay_root_cert_path'])) {

            //
            if (!file_exists($this->config['app_cert_path'])) {
                throw new PaymentException('应用证书不存在!');
            }

            if (!file_exists($this->config['alipay_root_cert_path'])) {
                throw new ApiException('支付宝根证书不存在!');
            }

            $publicParams['app_cert_sn'] = $this->getCertSN($this->config['app_cert_path']);
            $publicParams['alipay_root_cert_sn'] = $this->getRootCertSN($this->config['alipay_root_cert_path']);
        }

        if (!empty($params['notify_url'])) {
            $publicParams['notify_url'] = $params['notify_url'];
        }

        if (!empty($params['return_url'])) {
            $publicParams['return_url'] = $params['return_url'];
        }

        unset($params['notify_url']);
        unset($params['return_url']);

        //生成biz_content参数
        $biz_content = $params;
        $biz_content = $this->createbizContent($biz_content);
        $publicParams['biz_content'] = $biz_content;

        //需要签名的参数
        $sign_params = $publicParams;

        //参数重新排序
        $sign_params = $this->arraySort($sign_params);

        //生成待签名字符串
        $str = $this->createLinkstring($sign_params);

        //提取私钥
        $rsa_private_key = $this->getRsaKeyValue($this->config['rsa_private_key']);

        //加密字符串
        $rsa = new Utils\Rsa2Encrypt($rsa_private_key);
        $sign = $rsa->encrypt($str);

        $sign_params['sign'] = $sign;

        if ($is_app) {
            return http_build_query($sign_params);
        }

        if ($is_qrcode) {
            //请求接口获取二维码地址
            $res = Curl::get($this->gateway . '?' . http_build_query($sign_params));//用get方法商品名称不会乱码

            return $res;
        } else {
            return $this->gateway . '?' . http_build_query($sign_params);
        }
    }
}


