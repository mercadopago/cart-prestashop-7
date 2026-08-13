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

require_once _PS_MODULE_DIR_ . 'mercadopago/includes/module/settings/AbstractSettings.php';

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
        // retrieve data from form
        $rating = Tools::getValue('mercadopago-rating');
        $comments = Tools::getValue('mercadopago-comments');

        // update data
        $mp_module = new MPModule();
        $count = $mp_module->where('version', '=', MP_VERSION)->count();

        if ($count != 0) {
            $mp_module->update([
                'evaluation' => pSQL($rating),
                'comments' => pSQL($comments),
            ]);
        }

        Mercadopago::$form_alert = 'alert-success';
        Mercadopago::$form_message = $this->module->l('Thanks for rating us!', 'RatingSettings');
        MPLog::generate('Evaluation saved successfully');
    }
}
