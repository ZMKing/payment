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
 * @desc    : 该接口提供所有微信支付订单的查询
 **/
class TradeQuery extends WechatBaseObject implements IGatewayRequest
{
    const METHOD = '/v3/pay/transactions/id/';

    /**
     * 获取第三方返回结果
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function request(array $requestParams)
    {
        try {

            $queryUrl = self::METHOD . $requestParams['transaction_id'] . '?mchid= '. self::$config->get('mch_id', '');
            return $this->requestGetWXApi($queryUrl);
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
        $selfParams = [

        ];

        return $selfParams;
    }
}
