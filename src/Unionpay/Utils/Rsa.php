<?php

namespace JiaLeo\Payment\Unionpay\Utils;

class Rsa
{
    //获取证书ID
    public static function getCertId($certPath, $password)
    {
        $data = file_get_contents($certPath);
        openssl_pkcs12_read($data, $certs, $password);
        $x509data = $certs ['cert'];
        openssl_x509_read($x509data);
        $certData = openssl_x509_parse($x509data);

        return $certData['serialNumber'];
    }

    //RSA签名
    public static function getParamsSignatureWithRSA($params, $certPath, $password)
    {

        $privateKey = self::getPrivateKey($certPath, $password);
        $query = self::getStringToSign($params);

        $params_sha1x16 = sha1($query, false);

        openssl_sign($params_sha1x16, $signature, $privateKey, OPENSSL_ALGO_SHA1);

        return base64_encode($signature);
    }

    //MD5签名
    public static function getParamsSignatureWithMD5($params, $secret)
    {
        $query = self::getStringToSign($params);

        $signature = md5($query . '&' . md5($secret));

        return $signature;
    }

    //获取私钥
    protected static function getPrivateKey($certPath, $password)
    {
        $data = file_get_contents($certPath);
        openssl_pkcs12_read($data, $certs, $password);

        return $certs['pkey'];
    }


    //验签
    public static function verify($params, $certDir)
    {
        $publicKey = self::getPublicKeyByCertId($params['certId'], $certDir);
        $requestSignature = $params ['signature'];
        unset($params['signature']);

        ksort($params);
        $query = http_build_query($params);
        $query = urldecode($query);

        $signature = base64_decode($requestSignature);
        $paramsSha1x16 = sha1($query, false);
        $isSuccess = openssl_verify($paramsSha1x16, $signature, $publicKey, OPENSSL_ALGO_SHA1);

        return (bool)$isSuccess;
    }

    //通过证书ID获取公钥
    public static function getPublicKeyByCertId($certId, $certDir)
    {
        $handle = opendir($certDir);
        if ($handle) {
            while ($file = readdir($handle)) {
                //clearstatcache();
                $filePath = rtrim($certDir, '/\\') . '/' . $file;
                if (is_file($filePath) && self::endsWith($filePath, '.cer')) {
                    if (self::getCertIdByCerPath($filePath) == $certId) {
                        closedir($handle);

                        return file_get_contents($filePath);
                    }
                }
            }
            throw new \Exception(sprintf('Can not find certId in certDir %s', $certDir));
        } else {
            throw new \Exception('certDir is not exists');
        }

    }

    //文件判断
    public static function endsWith($haystack, $needles)
    {
        foreach ((array)$needles as $needle) {
            if ((string)$needle === substr($haystack, -strlen($needle))) {
                return true;
            }
        }

        return false;
    }

    //通过证书路径获取证书ID
    protected static function getCertIdByCerPath($certPath)
    {
        $x509data = file_get_contents($certPath);
        openssl_x509_read($x509data);
        $certData = openssl_x509_parse($x509data);

        return $certData ['serialNumber'];
    }

    //过滤无效的参数
    public static function filterData($data)
    {
        $data = array_filter(
            $data,
            function ($v) {
                return $v !== '';
            }
        );

        return $data;
    }


    /**
     * 参数排列
     */
    public static function getStringToSign($params)
    {
        ksort($params);
        $query = http_build_query($params);
        $query = urldecode($query);

        return $query;
    }

    /**
     * wap 跳转支付
     * @param $params
     * @param $reqUrl
     * @return string
     */
    public static function createAutoFormHtml($params, $reqUrl)
    {
        $encodeType = isset ($params ['encoding']) ? $params ['encoding'] : 'UTF-8';
        $html = <<<eot
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset={$encodeType}" />
</head>
<body onload="javascript:document.pay_form.submit();">
    <form id="pay_form" name="pay_form" action="{$reqUrl}" method="post">
	
eot;
        foreach ($params as $key => $value) {
            $html .= "<input type=\"hidden\" name=\"{$key}\" id=\"{$key}\" value=\"{$value}\" />\n";
        }
        $html .= <<<eot
    </form>
</body>
</html>
eot;

        return $html;
    }

}
