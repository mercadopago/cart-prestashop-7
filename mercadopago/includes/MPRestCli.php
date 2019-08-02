<?php
/**
 * 2007-2018 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    MercadoPago
 *  @copyright Copyright (c) MercadoPago [http://www.mercadopago.com]
 *  @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of MercadoPago
 */

class MPRestCli
{
    const PRODUCT_ID = 'BC32CCRU643001OI39AG';
    const API_BASE_URL = 'https://api.mercadopago.com';
    const API_BASE_MELI_URL = 'https://api.mercadolibre.com';
    const API_CONFIG_BASE_URL = 'https://api.mercadopago.com/account';

    public function __construct()
    {
    }

    /**
     * Get connect with cURL
     *
     * @param [string] $uri
     * @param [string] $method
     * @param [string] $content_type
     * @param [string] $uri_base
     * @return void
     */
    private static function getConnect($uri, $method, $content_type, $uri_base)
    {
        $connect = curl_init($uri_base . $uri);
        $product_id = ($method == 'POST') ? "x-product-id: " . self::PRODUCT_ID : "";

        curl_setopt($connect, CURLOPT_USERAGENT, 'MercadoPago Prestashop v1.0.0');
        curl_setopt($connect, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($connect, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt(
            $connect,
            CURLOPT_HTTPHEADER,
            array(
                $product_id,
                'Accept: application/json',
                'Content-Type: ' . $content_type,
            )
        );

        return $connect;
    }

    /**
     * Get tracking connect with cURL
     *
     * @param [string] $uri
     * @param [string] $method
     * @param [string] $content_type
     * @param [string] $trackingID
     * @return void
     */
    private static function getConnectTracking($uri, $method, $content_type, $trackingID)
    {
        $connect = curl_init(self::API_BASE_URL . $uri);
        $product_id = ($method == 'POST') ? "x-product-id: " . self::PRODUCT_ID : "";

        curl_setopt($connect, CURLOPT_USERAGENT, 'MercadoPago Prestashop v1.0.0');
        curl_setopt($connect, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($connect, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt(
            $connect,
            CURLOPT_HTTPHEADER,
            array(
                $product_id,
                'Accept: application/json',
                'Content-Type: ' . $content_type,
                'X-Tracking-Id:' . $trackingID,
            )
        );

        return $connect;
    }

    /**
     * execTracking
     *
     * @param [string] $method
     * @param [string] $uri
     * @param [string] $data
     * @param [string] $content_type
     * @param [string] $trackingID
     * @return void
     */
    private static function execTracking($method, $uri, $data, $content_type, $trackingID)
    {
        $connect = self::getConnectTracking($uri, $method, $content_type, $trackingID);

        if ($data) {
            self::setData($connect, $data, $content_type);
        }

        $api_result = curl_exec($connect);
        $api_http_code = curl_getinfo($connect, CURLINFO_HTTP_CODE);
        $response = array(
            'status' => $api_http_code,
            'response' => Tools::jsonDecode($api_result, true),
        );

        curl_close($connect);

        return $response;
    }

    /**
     * setData
     *
     * @param [string] $connect
     * @param [string] $data
     * @param [string] $content_type
     * @return void
     */
    private static function setData($connect, $data, $content_type)
    {
        if ($content_type == 'application/json') {
            if (gettype($data) == 'string') {
                Tools::jsonDecode($data, true);
            } else {
                $data = Tools::jsonEncode($data);
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
     * exec
     *
     * @param [string] $method
     * @param [string] $uri
     * @param [string] $data
     * @param [string] $content_type
     * @param [string] $uri_base
     * @return void
     */
    private static function exec($method, $uri, $data, $content_type, $uri_base)
    {
        $connect = self::getConnect($uri, $method, $content_type, $uri_base);

        if ($data) {
            self::setData($connect, $data, $content_type);
        }

        $api_result = curl_exec($connect);
        $api_http_code = curl_getinfo($connect, CURLINFO_HTTP_CODE);
        $response = array(
            'status' => $api_http_code,
            'response' => Tools::jsonDecode($api_result, true),
        );

        curl_close($connect);

        return $response;
    }

    /**
     * getConfig
     *
     * @param string $uri
     * @param string $content_type
     * @return void
     */
    public static function getConfig($uri, $content_type = 'application/json')
    {
        return self::exec('GET', $uri, null, $content_type, self::API_CONFIG_BASE_URL);
    }

    /**
     * get
     *
     * @param string $uri
     * @param string $content_type
     * @return void
     */
    public static function get($uri, $content_type = 'application/json')
    {
        return self::exec('GET', $uri, null, $content_type, self::API_BASE_URL);
    }
  
    /**
     * get mercado libre api
     *
     * @param string $uri
     * @param string $content_type
     * @return void
     */
    public static function getMercadoLibre($uri, $content_type = 'application/json')
    {
        return self::exec('GET', $uri, null, $content_type, self::API_BASE_MELI_URL);
    }

    /**
     * post
     *
     * @param string $uri
     * @param string $data
     * @param string $content_type
     * @return void
     */
    public static function post($uri, $data, $content_type = 'application/json')
    {
        return self::exec('POST', $uri, $data, $content_type, self::API_BASE_URL);
    }

    /**
     * postTracking
     *
     * @param string $uri
     * @param string $data
     * @param string $trackingID
     * @param string $content_type
     * @return void
     */
    public static function postTracking($uri, $data, $trackingID, $content_type = 'application/json')
    {
        return self::execTracking('POST', $uri, $data, $content_type, $trackingID);
    }

    /**
     * put
     *
     * @param string $uri
     * @param string $data
     * @param string $content_type
     * @return void
     */
    public static function put($uri, $data, $content_type = 'application/json')
    {
        return self::exec('PUT', $uri, $data, $content_type, self::API_BASE_URL);
    }

    /**
     * putConfig
     *
     * @param string $uri
     * @param string $data
     * @param string $content_type
     * @return void
     */
    public static function putConfig($uri, $data, $content_type = 'application/json')
    {
        return self::exec('PUT', $uri, $data, $content_type, self::API_CONFIG_BASE_URL);
    }
}
