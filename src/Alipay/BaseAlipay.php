<?php

namespace JiaLeo\Payment\Alipay;

use JiaLeo\Payment\Common\PaymentException;

class BaseAlipay
{

    public $config = array();

    protected $gateway = 'https://openapi.alipay.com/gateway.do';    //支付网关
    public $refundReturnData;  //退款返回的原始数据

    public function __construct($config)
    {
        if (empty($config['app_id'])) {
            throw new PaymentException('缺少配置app_id');
        }

        if (empty($config['ali_public_key'])) {
            throw new PaymentException('缺少配置ali_public_key');
        }

        if (empty($config['rsa_private_key'])) {
            throw new PaymentException('缺少配置rsa_private_key');
        }

        $this->config = $config;
    }

    /**
     * 检查支付宝数据 签名是否被篡改
     * @param array $data
     * @return boolean
     * @author helei
     */
    public function verifySign(array $data)
    {
        $sign = $data['sign'];

        // 1. 剔除sign与sign_type参数
        unset($data['sign'], $data['sign_type']);
        //  2. 移除数组中的空值
        $data = $this->paraFilter($data);
        // 3. 对待签名参数数组排序
        $data = $this->arraySort($data);
        // 4. 将排序后的参数与其对应值，组合成“参数=参数值”的格式,用&字符连接起来
        $preStr = $this->createLinkstring($data);

        //提取私钥,公钥
        $ali_public_key = $this->getRsaKeyValue($this->config['ali_public_key'], 'public');

        $rsa = new Utils\Rsa2Encrypt($ali_public_key);
        return $rsa->rsaVerify($preStr, $sign);
    }

    /**
     * 生成biz_content内容
     * @param array $data
     * @return string
     */
    public function createbizContent(array $data)
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取rsa密钥内容
     * @param string $key 传入的密钥信息， 可能是文件或者字符串
     * @param string $type
     *
     * @return string
     */
    public function getRsaKeyValue($key, $type = 'private')
    {
        if (is_file($key)) {// 是文件
            $rsaKey = @file_get_contents($key);
            //$res = openssl_get_publickey($keyStr);
        } else {
            $keyStr = $key;

            $keyStr = str_replace(PHP_EOL, '', $keyStr);

            // 为了解决用户传入的密钥格式，这里进行统一处理
            if ($type === 'private') {
                $beginStr = ['-----BEGIN RSA PRIVATE KEY-----', '-----BEGIN PRIVATE KEY-----'];
                $endStr = ['-----END RSA PRIVATE KEY-----', '-----END PRIVATE KEY-----'];
            } else {
                $beginStr = ['-----BEGIN PUBLIC KEY-----', ''];
                $endStr = ['-----END PUBLIC KEY-----', ''];
            }
            $keyStr = str_replace($beginStr, ['', ''], $keyStr);
            $keyStr = str_replace($endStr, ['', ''], $keyStr);

            $rsaKey = $beginStr[0] . PHP_EOL . wordwrap($keyStr, 64, PHP_EOL, true) . PHP_EOL . $endStr[0];
        }

        return $rsaKey;
    }

    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param array $para 需要拼接的数组
     * @return string
     * @throws \Exception
     */
    public function createLinkstring($para)
    {
        if (!is_array($para)) {
            throw new \Exception('必须传入数组参数');
        }

        reset($para);
        $arg = '';
        foreach ($para as $key => $val) {
            if (is_array($val)) {
                continue;
            }

            $arg .= $key . '=' . $val . '&';
        }

        //去掉最后一个&字符
        $arg && $arg = substr($arg, 0, -1);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

    /**
     * 对输入的数组进行字典排序
     * @param array $param 需要排序的数组
     * @return array
     */
    public function arraySort(array $data)
    {
        ksort($data);
        reset($data);

        return $data;
    }

    /**
     * 移除空值的key
     * @param $para
     * @return array
     */
    public function paraFilter($para)
    {
        $paraFilter = [];
        foreach ($para as $key => $val) {
            if ($val === '' || $val === null) {
                continue;
            } else {
                if (!is_array($para[$key])) {
                    $para[$key] = is_bool($para[$key]) ? $para[$key] : trim($para[$key]);
                }

                $paraFilter[$key] = $para[$key];
            }
        }

        return $paraFilter;
    }

    /**
     * 设置自定义字段
     * @param array $data
     * @return string
     */
    public function setPassbackParams(array $data)
    {
        $str = urlencode(serialize($data));
        return $str;
    }

    /**
     * 获取自定义字段
     * @param $str
     * @return array
     */
    public function getPassbackParams($str)
    {
        $data = unserialize(urldecode($str));
        return $data;
    }

    /**
     * 从证书中提取序列号
     * @param $cert
     * @return string
     */
    public function getCertSN($certPath)
    {
        $cert = file_get_contents($certPath);
        $ssl = openssl_x509_parse($cert);
        $SN = md5($this->array2string(array_reverse($ssl['issuer'])) . $ssl['serialNumber']);
        return $SN;
    }

    /**
     * 提取根证书序列号
     * @param $cert  根证书
     * @return string|null
     */
    public function getRootCertSN($certPath)
    {
        $cert = file_get_contents($certPath);
        $this->alipayRootCertContent = $cert;
        $array = explode("-----END CERTIFICATE-----", $cert);
        $SN = null;
        for ($i = 0; $i < count($array) - 1; $i++) {
            $ssl[$i] = openssl_x509_parse($array[$i] . "-----END CERTIFICATE-----");
            if (strpos($ssl[$i]['serialNumber'], '0x') === 0) {
                $ssl[$i]['serialNumber'] = $this->hex2dec($ssl[$i]['serialNumber']);
            }
            if ($ssl[$i]['signatureTypeLN'] == "sha1WithRSAEncryption" || $ssl[$i]['signatureTypeLN'] == "sha256WithRSAEncryption") {
                if ($SN == null) {
                    $SN = md5($this->array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                } else {

                    $SN = $SN . "_" . md5($this->array2string(array_reverse($ssl[$i]['issuer'])) . $ssl[$i]['serialNumber']);
                }
            }
        }
        return $SN;
    }

    public function array2string($array)
    {
        $string = [];
        if ($array && is_array($array)) {
            foreach ($array as $key => $value) {
                $string[] = $key . '=' . $value;
            }
        }
        return implode(',', $string);
    }
}