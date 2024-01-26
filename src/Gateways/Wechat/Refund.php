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
use Payment\Payment;

/**
 * @package Payment\Gateways\Wechat
 * @author  : Leo
 * @email   : dayugog@gmail.com
 * @date    : 2019/4/1 8:27 PM
 * @version : 1.0.0
 * @desc    : 申请退款
 **/
class Refund extends WechatBaseObject implements IGatewayRequest
{
    const METHOD = '/v3/refund/domestic/refunds';

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
        $selfParams = [
            'out_trade_no'     => $requestParams['trade_no'] ?? '',
            'out_refund_no'    => $requestParams['out_trade_no'] ?? '',
            'amount'           => [
                'refund'    => $requestParams['amount'] ? $requestParams['amount'] * 100 : 0,
                'total'    => $requestParams['total'] ? $requestParams['total'] * 100 : 0,
                'currency' => 'CNY'
            ]
        ];

        if (isset($requestParams['notify_url'])) {
            $selfParams['notify_url'] = $requestParams['notify_url'];
        }

        if (isset($requestParams['reason'])) {
            $selfParams['reason'] = $requestParams['reason'];
        }

        if (isset($requestParams['funds_account'])) {
            $selfParams['funds_account'] = $requestParams['funds_account'];
        }

        return $selfParams;
    }
}
