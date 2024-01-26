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

/**
 * @version : 1.0.0
 * @desc    : 扫码支付（Native支付）
 **/
class QrCharge extends WechatBaseObject implements IGatewayRequest
{
    const METHOD = '/v3/pay/transactions/native';

    /**
     * 获取第三方返回结果
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function request(array $requestParams)
    {
        try {
            return $this->requestWXApi(self::METHOD, $requestParams);
        } catch (GatewayException $e) {
            throw $e;
        }
    }

    /**
     * @param array $requestParams
     * @return mixed
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
            'appid'            => self::$config->get('app_id', ''),
            'mchid'            => self::$config->get('mch_id', ''),
            'notify_url'       => self::$config->get('notify_url', ''),
            'time_expire'      => $timeExpire,
            'amount'           => [
                'total'    => $requestParams['amount'] ? $requestParams['amount'] * 100 : 0,
                'currency' => 'CNY'
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
