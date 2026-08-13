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

function upgrade_module_4_10_0()
{
    Configuration::updateValue('MERCADOPAGO_CUSTOM_WALLET_BUTTON', true);

    // Insert necessary data on DB
    $mp_module = new MPModule();
    $count = $mp_module->where('version', '=', MP_VERSION)->count();

    if ($count == 0) {
        $old_mp = $mp_module->orderBy('id_mp_module', 'desc')->get();
        $old_mp = $mp_module->where('id_mp_module', '=', $old_mp['id_mp_module'])->update(['updated' => true]);
        $mp_module->create(['version' => MP_VERSION]);
    }

    return true;
}
