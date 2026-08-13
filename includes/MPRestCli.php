<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class MPRestCli
{
    public const PRODUCT_ID = 'BC32CCRU643001OI39AG';
    public const PLATFORM_ID = 'BP1EEMU0A3M001J8OJUG';
    public const API_BASE_URL = 'https://api.mercadopago.com';
    public const API_BASE_MELI_URL = 'https://api.mercadolibre.com';

    public function __construct()
    {
    }

    /**
     * @param $uri
     * @param $method
     * @param $headers
     * @param $uri_base
     *
     * @return CurlHandle|false
     */
    private static function getConnect($uri, $method, $headers, $uri_base)
    {
        $product_id = ($method == 'POST') ? 'x-product-id: ' . self::PRODUCT_ID : '';

        $headers_default = [
            $product_id,
            'Accept: application/json',
            'Content-Type: application/json',
            'x-platform-id: ' . self::PLATFORM_ID,
            'x-integrator-id:' . Configuration::get('MERCADOPAGO_INTEGRATOR_ID'),
        ];
        is_array($headers) ? $headers = array_merge($headers_default, $headers) : '';

        $connect = curl_init($uri_base . $uri);

        if ($connect === false) {
            return false;
        }

        curl_setopt($connect, CURLOPT_USERAGENT, 'MercadoPago Prestashop v' . MP_VERSION);
        curl_setopt($connect, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($connect, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($connect, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($connect, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($connect, CURLOPT_SSL_VERIFYHOST, 2);

        return $connect;
    }

    /**
     * setData
     *
     * @param $connect
     * @param $data
     * @param $content_type
     *
     * @return void
     *
     * @throws Exception
     */
    private static function setData($connect, $data, $content_type)
    {
        if ($content_type == 'application/json') {
            if (gettype($data) == 'string') {
                json_decode($data, true);
            } else {
                $data = json_encode($data);
            }

            if (function_exists('json_last_error')) {
                $json_error = json_last_error();
                if ($json_error != JSON_ERROR_NONE) {
                    throw new Exception("JSON Error [{$json_error}] - Data: {$data}");
                }
            }
        }

        curl_setopt($connect, CURLOPT_POSTFIELDS, $data);
    }

    /**
     * @param $method
     * @param $uri
     * @param $data
     * @param $headers
     * @param $uri_base
     *
     * @return array
     *
     * @throws Exception
     */
    private static function exec($method, $uri, $data, $headers, $uri_base)
    {
        $connect = self::getConnect($uri, $method, $headers, $uri_base);

        if ($connect === false) {
            return ['status' => 500, 'response' => null];
        }

        if ($data) {
            self::setData($connect, $data, 'application/json');
        }

        $api_result = curl_exec($connect);
        $api_http_code = curl_getinfo($connect, CURLINFO_HTTP_CODE);
        $response = [
            'status' => $api_http_code,
            'response' => json_decode($api_result, true),
        ];

        curl_close($connect);

        return $response;
    }

    /**
     * @param $uri
     * @param array|null $headers
     *
     * @return array
     *
     * @throws Exception
     */
    public static function getMercadoLibre($uri, $headers = null)
    {
        return self::exec('GET', $uri, null, $headers, self::API_BASE_MELI_URL);
    }

    /**
     * @param $uri
     * @param array|null $headers
     *
     * @return array
     *
     * @throws Exception
     */
    public static function get($uri, $headers = null)
    {
        return self::exec('GET', $uri, null, $headers, self::API_BASE_URL);
    }

    /**
     * @param $uri
     * @param $data
     * @param array|null $headers
     *
     * @return array
     *
     * @throws Exception
     */
    public static function post($uri, $data, $headers = null)
    {
        return self::exec('POST', $uri, $data, $headers, self::API_BASE_URL);
    }

    /**
     * @param $uri
     * @param $data
     * @param array|null $headers
     *
     * @return array
     *
     * @throws Exception
     */
    public static function put($uri, $data, $headers = null)
    {
        return self::exec('PUT', $uri, $data, $headers, self::API_BASE_URL);
    }
}
