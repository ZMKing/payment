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
use Payment\Gateways\Wechat\Crypto\Rsa;
use Payment\Gateways\Wechat\Framework\Formatter;

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
    const METHOD = 'v3/pay/transactions/jsapi';

    /**
     * 获取第三方返回结果
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function request(array $requestParams)
    {
        try {

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
            'appId'     => self::$config->get('app_id', ''),
            'timeStamp' => (string)Formatter::timestamp(),
            'nonceStr'  => Formatter::nonce(),
            'package'   => 'prepay_id='.$params['prepay_id'] ,
        ];

        $keyPem = 'file://' . self::$config->get('app_key_pem', '');

        $privateKey = Rsa::from($keyPem);
        $values += ['sign' => Rsa::sign(
            Formatter::joinedByLineFeed(...array_values($values)),
            $privateKey
        ), 'signType' => 'RSA'];

        return $values;
    }

    /**
     * @param array $requestParams
     * @return mixed
     *
     *  "attach" : "自定义数据说明",
        "goods_tag" : "WXG",
        "support_fapiao" : true,
        "detail" : {
            "cost_price" : 608800,
            "invoice_id" : "微信123",
            "goods_detail" : [
                {
                "merchant_goods_id" : "1246464644",
                "wechatpay_goods_id" : "1001",
                "goods_name" : "iPhoneX 256G",
                "quantity" : 1,
                "unit_price" : 528800
                }
            ]
        },
        "scene_info" : {
            "payer_client_ip" : "14.23.150.211",
            "device_id" : "013467007045764",
            "store_info" : {
                "id" : "0001",
                "name" : "腾讯大厦分店",
                "area_code" : "440305",
                "address" : "广东省深圳市南山区科技中一道10000号"
            }
        },
        "settle_info" : {
            "profit_sharing" : false
        }
     */
    protected function getSelfParams(array $requestParams)
    {
        $timeExpire = intval($requestParams['time_expire']);
        if (!empty($timeExpire)) {

            $timestamp = $requestParams['time_expire'];
            $date = new \DateTime("@$timestamp");
            $timeExpire = $date->format("Y-m-d\TH:i:sP");
        } else {

            $timestamp = time() + 1800;
            $date = new \DateTime("@$timestamp");
            $timeExpire = $date->format("Y-m-d\TH:i:sP");
        }

        $selfParams = [
            'out_trade_no'     => $requestParams['trade_no'] ?? '',
            'description'      => $requestParams['body'] ?? '',
            'appid'      => self::$config->get('app_id', ''),
            'mchid'     => self::$config->get('mch_id', ''),
            'notify_url' => self::$config->get('notify_url', ''),
            'time_expire'      => $timeExpire,
            'amount'           => [
                'total'    => intval(round(($requestParams['amount'] ? $requestParams['amount'] * 100 : 0))),
                'currency' => 'CNY'
            ],
            'payer' => [
              'openid' => $requestParams['openid'] ?? '',
            ]
        ];

        if (isset($requestParams['settle_info'])) {
            $selfParams['settle_info'] = $requestParams['settle_info'];
        }

        if (isset($requestParams['scene_info'])) {
            $selfParams['scene_info'] = $requestParams['scene_info'];
        }

        if (isset($requestParams['detail'])) {
            $selfParams['detail'] = $requestParams['detail'];
        }

        if (isset($requestParams['support_fapiao'])) {
            $selfParams['support_fapiao'] = $requestParams['support_fapiao'];
        }

        if (isset($requestParams['support_fapiao'])) {
            $selfParams['support_fapiao'] = $requestParams['support_fapiao'];
        }

        if (isset($requestParams['goods_tag'])) {
            $selfParams['goods_tag'] = $requestParams['goods_tag'];
        }

        if (isset($requestParams['attach'])) {
            $selfParams['attach'] = $requestParams['attach'];
        }

        return $selfParams;
    }
}
