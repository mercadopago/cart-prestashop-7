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

class MercadoPagoCouponModuleFrontController extends ModuleFrontController
{
    protected $mercadopago;

    public function __construct()
    {
        parent::__construct();
        $this->mercadopago = MPApi::getInstance();
    }

    /**
     * Default function of Prestashop for init the controller
     *
     * @return void
     */
    public function initContent()
    {
        $cart = $this->context->cart;
        $coupon = Tools::getValue('coupon');

        if (Tools::getIsset('coupon') && $coupon != "") {
            $response = $this->couponValidation($cart, $coupon);
            $status = $response['status'];

            if ($status > 202) {
                $this->geCouponResponse($response['response']['message'], $status);
            }
            
            $this->geCouponResponse($response['response'], $status);
        } else {
            $this->geCouponResponse("Bad request", 400);
        }
    }


    public function couponValidation($cart, $coupon)
    {
        $amount = (float) $cart->getOrderTotal();
        $customer = $this->context->customer->getFields();
        $params = array(
            'coupon_code' => $coupon,
            'payer_email' => $customer['email'],
            'transaction_amount' => $amount,
        );

        $applyCoupon = $this->mercadopago->getCouponDiscount($params);

        return $applyCoupon;
    }

    /**
     * Get coupon responses
     *
     * @param string $message
     * @param integer $code
     * @return void
     */
    public function geCouponResponse($message, $code)
    {
        header('Content-type: application/json');
        $response = array(
            "code" => $code,
            "message" => $message
        );

        echo Tools::jsonEncode($response);
        exit;
    }
}
