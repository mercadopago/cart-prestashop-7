<?php
/**
* 2007-2023 PrestaShop
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
*  @copyright 2007-2023 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

use MercadoPago\PP\Sdk\Sdk;

class CoreSdkSettings
{
    static $instance = null;

    const PRODUCT_ID = 'BC32CCRU643001OI39AG';
    const PLATFORM_ID = 'BP1EEMU0A3M001J8OJUG';

    private function __construct()
    {
        $accessToken = $this->getAccessToken();
        $publicKey = $this->getPublicKey();
        $integratorId = Configuration::get('MERCADOPAGO_INTEGRATOR_ID') ?? '';
        $instance = new Sdk($accessToken, CoreSdkSettings::PLATFORM_ID, CoreSdkSettings::PRODUCT_ID, $integratorId, $publicKey); 
        CoreSdkSettings::$instance = $instance;
    }

    /**
     * Get getInstance
     *
     * @return Sdk
     */
    public static function getInstance() : Sdk {
        try {
            if (CoreSdkSettings::$instance == null) {
                new CoreSdkSettings();
            }
            return CoreSdkSettings::$instance;
        } catch (\Throwable $th) {
            throw new Exception($th->getMessage());
        }
    }

    /**
     * Get access token
     *
     * @return string
     */
    private function getAccessToken()
    {
        if (Configuration::get('MERCADOPAGO_PROD_STATUS') == true) {
            return Configuration::get('MERCADOPAGO_ACCESS_TOKEN');
        }

        return Configuration::get('MERCADOPAGO_SANDBOX_ACCESS_TOKEN');
    }

    /**
     * Get public key
     *
     * @return string
     */
    private function getPublicKey()
    {
        if (Configuration::get('MERCADOPAGO_PROD_STATUS') == true) {
            return Configuration::get('MERCADOPAGO_PUBLIC_KEY');
        }

        return Configuration::get('MERCADOPAGO_SANDBOX_PUBLIC_KEY');
    }
}
