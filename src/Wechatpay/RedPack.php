<?php
/**
 * 红包
 */

namespace JiaLeo\Payment\Wechatpay;

use JiaLeo\Payment\Common\PaymentException;

class RedPack extends BaseWechatpay
{
    public $payUrl = '/mmpaymkttransfers/sendredpack';

    public $errorCode;  //错误代码
    public $errorCodeDes;   //错误描述


    const SCENEID = [
        'PRODUCT_1',    //商品促销
        'PRODUCT_2',    //抽奖
        'PRODUCT_3',    //虚拟物品兑奖
        'PRODUCT_4',    //企业内部福利
        'PRODUCT_5',    //渠道分润
        'PRODUCT_6',    //保险回馈
        'PRODUCT_7',    //彩票派奖
        'PRODUCT_8',    //税务刮奖
    ];

    /**
     * 发送裂变红包
     * @param $params
     * @return mixed
     * @throws PaymentException
     */
    public function sendGroupRedPack($params)
    {
        $this->payUrl = '/mmpaymkttransfers/sendgroupredpack';
        $params['amt_type'] = 'ALL_RAND';

        return $this->handle($params);
    }

    /**
     * 发送普通红包
     * @param $params
     * @return mixed
     * @throws PaymentException
     */
    public function sendRedPack($params)
    {
        $this->payUrl = '/mmpaymkttransfers/sendredpack';

        return $this->handle($params);
    }

    /**
     * 发放红包
     * @return mixed
     * @throws PaymentException
     */
    public function handle($params)
    {
        //检查订单号是否合法
        if (empty($params['mch_billno'])) {
            throw new PaymentException('mch_billno商户订单号不能为空');
        }

        //检查订单号是否合法
        if (empty($params['send_name'])) {
            throw new PaymentException('send_name商户名称不能为空');
        }

        //检查openid
        if (empty($params['openid'])) {
            throw new PaymentException('openid不能为空');
        }

        //检查祝福语
        if (empty($params['wishing'])) {
            throw new PaymentException('wishing祝福语不能为空');
        }

        //检查活动名称
        if (empty($params['act_name'])) {
            throw new PaymentException('act_name活动名称不能为空');
        }

        //检查备注
        if (empty($params['remark'])) {
            throw new PaymentException('remark备注不能为空');
        }

        // 需要红包金额不能低于1
        if (empty($params['total_amount']) || $params['total_amount'] < 100) {
            throw new PaymentException('total_amount红包金额不能为空,且不能低于 1 元');
        }

        //红包金额大于200,需要传scene_id
        if ($params['total_amount'] > 20000 && (empty($params['scene_id']) || !in_array($params['scene_id'], self::SCENEID))) {
            throw new PaymentException('红包金额大于200元,scene_id不能为空或scene_id值错误!');
        }

        if (!isset($params['create_ip'])) {
            $ip_address = !empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : $_SERVER['REMOTE_ADDR'];
            //兼容处理
            $ip_address_arr = explode(',', $ip_address);
            $ip_address = $ip_address_arr[0];

        } else {
            $ip_address = $params['create_ip'];
        }

        $data = array(
            'wxappid' => $this->config['appid'],
            'mch_id' => $this->config['mchid'],
            'nonce_str' => $this->getNonceStr(),
            'mch_billno' => $params['mch_billno'],
            'send_name' => $params['send_name'],
            're_openid' => $params['openid'],
            'total_amount' => $params['total_amount'],
            'total_num' => empty($params['total_num']) ? 1 : $params['total_num'],
            'wishing' => $params['wishing'],
            'act_name' => $params['act_name'],
            'remark' => $params['remark'],
            'client_ip' => $ip_address,
        );

        //场景id
        if (!empty($params['scene_id'])) {
            $data['scene_id'] = $params['scene_id'];
        }

        //活动信息
        if (!empty($params['risk_info'])) {
            $data['risk_info'] = $params['risk_info'];
        }

        //资金授权商户号
        if (!empty($params['consume_mch_id'])) {
            $data['consume_mch_id'] = $params['consume_mch_id'];
        }

        //发放类型
        if (!empty($params['amt_type'])) {
            $data['amt_type'] = $params['amt_type'];
        }

        //签名
        $data['sign'] = $this->makeSign($data);

        //转换成xml
        $xml = $this->toXml($data);
        $result = $this->postXmlCurl($this->gateway . $this->payUrl, $xml, $this->config['sslcert_path'], $this->config['sslkey_path']);
        $get_result = $this->fromXml($result);

        try {
            if (!isset($get_result['return_code']) || $get_result['return_code'] != 'SUCCESS') {
                throw new PaymentException('调起支付失败!错误信息:' . isset($get_result['return_msg']) ? $get_result['return_msg'] : $get_result);
            }

            if ($get_result['result_code'] != 'SUCCESS') {
                throw new PaymentException('调起支付失败!错误信息:' . isset($get_result['err_code_des']) ? $get_result['err_code_des'] : $get_result);
            }
        } catch (\Exception $e) {
            $this->errorCode = $get_result['err_code'];
            $this->errorCodeDes = $get_result['err_code_des'];

            return false;
        }

        return $get_result;
    }
}