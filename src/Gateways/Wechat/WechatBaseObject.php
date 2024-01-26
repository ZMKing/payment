<?php

namespace Payment\Gateways\Wechat;

use Payment\Exceptions\GatewayException;
use Payment\Gateways\Wechat\Framework\Builder;
use Payment\Gateways\Wechat\Crypto\Rsa;
use Payment\Helpers\ArrayUtil;
use Payment\Helpers\PemUtil;
use Payment\Supports\BaseObject;
use Payment\Supports\HttpRequest;

abstract class WechatBaseObject extends BaseObject
{
    use HttpRequest;

    const REQ_SUC = 'SUCCESS';

    /**
     * @var bool
     */
    protected $returnRaw = false;

    /**
     * @var bool
     */
    protected $useBackup = false;

    /**
     * WechatBaseObject constructor.
     * @throws GatewayException
     */
    public function __construct()
    {

        $this->useBackup = self::$config->get('use_backup', false);
        $this->returnRaw = self::$config->get('return_raw', false);
    }

    /**
     * 生成请求参数
     * @param array $requestParams
     * @return string
     * @throws GatewayException
     */
    protected function buildParams(array $requestParams = [])
    {
        $params = $this->getSelfParams($requestParams);
        $params = ArrayUtil::paraFilter($params);
        $params = ArrayUtil::arraySort($params);

        return $params;
    }

    /**
     * @param array $requestParams
     * @return mixed
     */
    abstract protected function getSelfParams(array $requestParams);

    /*
     *
     */
    protected function buildFactory() :array
    {
        $keyPem = 'file://' . self::$config->get('app_key_pem', '');
        $certPem = 'file://' . self::$config->get('app_cert_pem', '');

        $privateKey = Rsa::from($keyPem);
        $publicKey = Rsa::from($certPem, Rsa::KEY_TYPE_PUBLIC);
        $certSerial = PemUtil::parseCertificateSerialNo($certPem);

        $serial = self::$config->get('serial', '');

        return [
            'mchid' => self::$config->get('mch_id', ''),
            'serial' => $serial,
            'privateKey' => $privateKey,
            'certs' => [
                $certSerial => $publicKey
            ]
        ];
    }

    /**
     * 请求微信支付的api
     * @param string $method
     * @param array $requestParams
     * @return array|false
     * @throws GatewayException
     */
    protected function requestWXApi(string $method, array $requestParams)
    {
        try {

            $resp = Builder::factory($this->buildFactory())
                        ->chain($method)
                        ->post(['json' => $this->buildParams($requestParams)]);

            $code = $resp->getStatusCode();
            $body = json_decode($resp->getBody(), true);

            if ($code == 200) {

                return $body;
            }else{

                throw new GatewayException('http response exception code: ' . $code, 10000);
            }

        } catch (GatewayException $e) {

            throw $e;
        }
    }

    /**
     * 请求微信支付的api
     * @param string $method
     * @param array $requestParams
     * @return array|false
     * @throws GatewayException
     */
    protected function requestGetWXApi(string $method)
    {
        try {

            $resp = Builder::factory($this->buildFactory())
                ->chain($method)
                ->get();

            $code = $resp->getStatusCode();
            $body = json_decode($resp->getBody(), true);

            if ($code == 200) {

                return $body;
            }else{

                throw new GatewayException('http response exception code: ' . $code, 10000);
            }

        } catch (GatewayException $e) {

            throw $e;
        }
    }
}
