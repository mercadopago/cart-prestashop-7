<?php
/**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2025 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class MPRestCli
{
    const PRODUCT_ID = 'BC32CCRU643001OI39AG';
    const PLATFORM_ID = 'BP1EEMU0A3M001J8OJUG';
    const API_BASE_URL = 'https://api.mercadopago.com';
    const API_BASE_MELI_URL = 'https://api.mercadolibre.com';

    public function __construct()
    {
    }

    /**
     * @param $uri
     * @param $method
     * @param $headers
     * @param $uri_base
     * @return false|resource
     */
    private static function getConnect($uri, $method, $headers, $uri_base)
    {
        $product_id = ($method == 'POST') ? "x-product-id: " . self::PRODUCT_ID : "";

        $headers_default = [
            $product_id,
            'Accept: application/json',
            'Content-Type: application/json',
            'x-platform-id: ' . self::PLATFORM_ID,
            'x-integrator-id:' . Configuration::get('MERCADOPAGO_INTEGRATOR_ID')
        ];
        is_array($headers) ? $headers = array_merge($headers_default, $headers): '';

        $connect = curl_init($uri_base . $uri);

        curl_setopt($connect, CURLOPT_USERAGENT, 'MercadoPago Prestashop v'.MP_VERSION);
        curl_setopt($connect, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($connect, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($connect, CURLOPT_HTTPHEADER, $headers);

        return $connect;
    }

    /**
     * setData
     *
     * @param $connect
     * @param $data
     * @param $content_type
     * @return void
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
     * @return array
     * @throws Exception
     */
    private static function exec($method, $uri, $data, $headers, $uri_base)
    {
        $connect = self::getConnect($uri, $method, $headers, $uri_base);

        if ($data) {
            self::setData($connect, $data, 'application/json');
        }

        $api_result = curl_exec($connect);
        $api_http_code = curl_getinfo($connect, CURLINFO_HTTP_CODE);
        $response = array(
            'status' => $api_http_code,
            'response' => json_decode($api_result, true),
        );

        curl_close($connect);

        return $response;
    }

    /**
     * @param $uri
     * @param null $headers
     * @return array
     * @throws Exception
     */
    public static function getMercadoLibre($uri, $headers = null)
    {
        return self::exec('GET', $uri, null, $headers, self::API_BASE_MELI_URL);
    }

    /**
     * @param $uri
     * @param null $headers
     * @return array
     * @throws Exception
     */
    public static function get($uri, $headers = null)
    {
        return self::exec('GET', $uri, null, $headers, self::API_BASE_URL);
    }

    /**
     * @param $uri
     * @param $data
     * @param null $headers
     * @return array
     * @throws Exception
     */
    public static function post($uri, $data, $headers = null)
    {
        return self::exec('POST', $uri, $data, $headers, self::API_BASE_URL);
    }

    /**
     * @param $uri
     * @param $data
     * @param null $headers
     * @return array
     * @throws Exception
     */
    public static function put($uri, $data, $headers = null)
    {
        return self::exec('PUT', $uri, $data, $headers, self::API_BASE_URL);
    }
}
