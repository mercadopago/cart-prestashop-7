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

use MercadoPago\PP\Sdk\Sdk;

class CoreSdkSettings
{
    private static $instance;

    public const PRODUCT_ID = 'BC32CCRU643001OI39AG';
    public const PLATFORM_ID = 'BP1EEMU0A3M001J8OJUG';

    private function __construct()
    {
        $accessToken = $this->getAccessToken();
        $publicKey = $this->getPublicKey();
        $integratorId = Configuration::get('MERCADOPAGO_INTEGRATOR_ID');
        $integratorId = !empty($integratorId) ? $integratorId : '';
        $instance = new Sdk($accessToken, CoreSdkSettings::PLATFORM_ID, CoreSdkSettings::PRODUCT_ID, $integratorId, $publicKey);
        CoreSdkSettings::$instance = $instance;
    }

    /**
     * Get getInstance
     *
     * @return Sdk
     */
    public static function getInstance()
    {
        try {
            if (CoreSdkSettings::$instance == null) {
                new CoreSdkSettings();
            }

            return CoreSdkSettings::$instance;
        } catch (Throwable $th) {
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
