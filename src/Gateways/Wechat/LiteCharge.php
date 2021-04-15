<?php

/*
 * The file is part of the payment lib.
 *
 * (c) Leo <dayugog@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Payment\Gateways\Wechat;

use Payment\Contracts\IGatewayRequest;
use Payment\Exceptions\GatewayException;
use Payment\Helpers\ArrayUtil;
use Payment\Helpers\StrUtil;
use Payment\Payment;

/**
 * @package Payment\Gateways\Wechat
 * @author  : Leo
 * @email   : dayugog@gmail.com
 * @date    : 2019/4/1 8:25 PM
 * @version : 1.0.0
 * @desc    : 小程序支付
 **/
class LiteCharge extends WechatBaseObject implements IGatewayRequest
{
    const METHOD = 'pay/unifiedorder';

    /**
     * 获取第三方返回结果
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function request(array $requestParams)
    {
        try {
//            return $this->requestWXApi(self::METHOD, $requestParams);
            return $this->createData( $this->requestWXApi( self::METHOD , $requestParams ) );

        } catch (GatewayException $e) {
            throw $e;
        }
    }
    /**
     * 进行二次加签验证
     * https://pay.weixin.qq.com/wiki/doc/api/wxa/wxa_api.php?chapter=7_7&index=5
     * 注意大小写
     * @param array $params
     *
     *
     * @return array
     */
    public function createData( array $params )
    {
        $values = [
            'appId'     => $params['appid'] ,
            'package'   => 'prepay_id='.$params['prepay_id'] ,
            'nonceStr'  => StrUtil::getNonceStr() ,
            'timeStamp' => time() ,
            'signType' => 'MD5' ,
        ];

        $values = ArrayUtil::removeKeys( $values , [ 'sign' ] );

        $values = ArrayUtil::arraySort( $values );

        $signStr = ArrayUtil::createLinkstring( $values );

        $values['sign'] = $this->makeSign( $signStr );

        return $values;
    }

    /**
     * @param array $requestParams
     * @return mixed
     */
    protected function getSelfParams(array $requestParams)
    {
        $limitPay = self::$config->get('limit_pay', '');
        if ($limitPay) {
            $limitPay = $limitPay[0];
        } else {
            $limitPay = '';
        }
        $nowTime    = time();
        $timeExpire = intval($requestParams['time_expire']);
        if (!empty($timeExpire)) {
            $timeExpire = date('YmdHis', $timeExpire);
        } else {
            $timeExpire = date('YmdHis', $nowTime + 1800); // 默认半小时过期
        }

        $receipt   = $requestParams['receipt'] ?? false;
        $totalFee  = bcmul($requestParams['amount'], 100, 0);
        $sceneInfo = $requestParams['scene_info'] ?? '';
        if ($sceneInfo) {
            $sceneInfo = json_encode(['store_info' => $sceneInfo]);
        } else {
            $sceneInfo = '';
        }


        $selfParams = [
            'device_info'      => $requestParams['device_info'] ?? '',
            'body'             => $requestParams['subject'] ?? '',
            'detail'           => $requestParams['body'] ?? '',
            'attach'           => $requestParams['return_param'] ?? '',
            'out_trade_no'     => $requestParams['trade_no'] ?? '',
            'fee_type'         => self::$config->get('fee_type', 'CNY'),
            'total_fee'        => $totalFee,
            'spbill_create_ip' => $requestParams['client_ip'] ?? '',
            'time_start'       => date('YmdHis', $nowTime),
            'time_expire'      => $timeExpire,
            'goods_tag'        => $requestParams['goods_tag'] ?? '',
            'notify_url'       => self::$config->get('notify_url', ''),
            'trade_type'       => 'JSAPI',
            'product_id'       => $requestParams['product_id'] ?? '',
            'limit_pay'        => $limitPay,
            'openid'           => $requestParams['openid'] ?? '',
            'receipt'          => $receipt === true ? 'Y' : '',
            'scene_info'       => $sceneInfo,
        ];

        return $selfParams;
    }
}
