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
 * @desc    : 关闭交易
 **/
class CloseTrade extends WechatBaseObject implements IGatewayRequest
{
    const METHOD = '/v3/pay/transactions/out-trade-no/{out_trade_no}/close';

    /**
     * 获取第三方返回结果
     * @param array $requestParams
     * @return mixed
     * @throws GatewayException
     */
    public function request(array $requestParams)
    {
        try {

            $queryUrl = str_replace('{out_trade_no}', $requestParams['out_trade_no'], self::METHOD);

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

        return [];
    }
}
