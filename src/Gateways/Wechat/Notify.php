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

use Payment\Exceptions\GatewayException;
use Payment\Gateways\Wechat\Crypto\Rsa;
use Payment\Gateways\Wechat\Crypto\AesGcm;
use Payment\Gateways\Wechat\Framework\Formatter;
use Payment\Payment;

/**
 * @version : 1.0.0
 * @desc    : 异步通知数据处理
 **/
class Notify extends WechatBaseObject
{
    /**
     * @throws GatewayException
     */
    public function request($requestParams = [])
    {
        $returnArr = [];
        if (count($requestParams) > 0) {
            $returnArr = $requestParams['inBody'];

            $inWechatpayNonce = $requestParams['headers']['wechatpay-nonce'];
            $inWechatpaySerial = $requestParams['headers']['wechatpay-serial'];
            $inWechatpaySignature = $requestParams['headers']['wechatpay-signature'];
            $inWechatpayTimestamp = $requestParams['headers']['wechatpay-timestamp'];
        }else{
            $request = \Hyperf\Utils\ApplicationContext::getContainer()->get(\Hyperf\HttpServer\Contract\RequestInterface::class);

            $inBody = $request->getBody()->getContents();

            if (empty($inBody)) {
                throw new GatewayException('the notify data is empty', Payment::NOTIFY_DATA_EMPTY);
            }

            $returnArr = (array)json_decode($inBody, true);

            $inWechatpayNonce = $request->getHeaderLine('wechatpay-nonce');
            $inWechatpaySerial = $request->getHeaderLine('wechatpay-serial');
            $inWechatpaySignature = $request->getHeaderLine('wechatpay-signature');
            $inWechatpayTimestamp = $request->getHeaderLine('wechatpay-timestamp');
        }



        $apiv3Key = self::$config->get('mch_api_v3_key', '');

        $platformPublicKeyInstance = Rsa::from('file://' . self::$config->get('app_cert_pem', ''), Rsa::KEY_TYPE_PUBLIC);

        $timeOffsetStatus = 300 >= abs(Formatter::timestamp() - (int)$inWechatpayTimestamp);

        if (!$timeOffsetStatus) {

            throw new GatewayException('check notify timestamp failed', Payment::GATEWAY_CHECK_FAILED);
        }


        $verifiedStatus = Rsa::verify(
            // 构造验签名串
            Formatter::joinedByLineFeed($inWechatpayTimestamp, $inWechatpayNonce, json_encode($returnArr)),
            $inWechatpaySignature,
            $platformPublicKeyInstance
        );

        if ($verifiedStatus) {

            ['resource' => [
                'ciphertext'      => $ciphertext,
                'nonce'           => $nonce,
                'associated_data' => $aad
            ]] = $returnArr;
            $inBodyResource = AesGcm::decrypt($ciphertext, $apiv3Key, $nonce, $aad);
            $inBodyResourceArray = (array)json_decode($inBodyResource, true);
            $returnArr['resource'] = $inBodyResourceArray;
        }else{

            throw new GatewayException('check notify data sign failed', Payment::SIGN_ERR, $returnArr);
        }

        if (self::$config->get('mch_id', '') !== $returnArr['resource']['mchid']) {
            throw new GatewayException('mch info is error', Payment::MCH_INFO_ERR, $returnArr);
        }

        if (strpos($returnArr['event_type'], 'REFUND')) {
            return [
                'notify_type' => 'pay',
                'notify_data' => $returnArr
            ];
        }else{
            return [
                'notify_type' => 'refund',
                'notify_data' => $returnArr
            ];
        }
    }

    /**
     * 向微信响应处理结果
     * @param bool $flag
     * @return bool|string
     */
    public function response(bool $flag)
    {
        // 默认为成功
        $result = [
            'code' => 'SUCCESS',
            'message'  => 'OK',
        ];
        if (!$flag) {
            // 失败
            $result = [
                'code' => 'FAIL',
                'message'  => 'mch have error',
            ];
        }
        return json_encode($result);
    }

    /**
     * 该方法在这里不做处理
     * @param array $requestParams
     * @return mixed
     */
    protected function getSelfParams(array $requestParams)
    {
        return [];
    }
}
