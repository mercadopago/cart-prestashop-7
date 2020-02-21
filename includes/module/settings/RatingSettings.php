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

require_once MP_ROOT_URL . '/includes/module/settings/AbstractSettings.php';

class RatingSettings extends AbstractSettings
{
    public function __construct()
    {
        parent::__construct();
        $this->submit = 'submitMercadopagoRating';
        $this->process = $this->verifyPostProcess();
    }

    /**
     * Save form data
     *
     * @return void
     */
    public function postFormProcess()
    {
        //retrieve data from form
        $rating = Tools::getValue('mercadopago-rating');
        $comments = Tools::getValue('mercadopago-comments');

        //update data
        $mp_module = new MPModule();
        $count = $mp_module->where('version', '=', MP_VERSION)->count();

        if ($count != 0) {
            $mp_module->update([
                "evaluation" => $rating,
                "comments" => $comments
            ]);
        }

        Mercadopago::$form_alert = 'alert-success';
        Mercadopago::$form_message = $this->module->l('Thanks for rating us!', 'RatingSettings');
        MPLog::generate('Evaluation saved successfully');
    }
}
