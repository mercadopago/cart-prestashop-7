<?php
/**
 * 2007-2025 PrestaShop.
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
 * @author    MercadoPago
 * @copyright Copyright (c) MercadoPago [http://www.mercadopago.com]
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of MercadoPago
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_4_4_2($module)
{
    // Execute module update MySQL commands
    $sql_file = dirname(__FILE__).'/sql/install-4.4.2.sql';
    if (!$module->loadSQLFile($sql_file)) {
        return false;
    }

    //Insert necessary data on DB
    $mp_module = new MPModule();
    $count = $mp_module->where('version', '=', MP_VERSION)->count();

    if ($count == 0) {
        $old_mp = $mp_module->orderBy('id_mp_module', 'desc')->get();
        $old_mp = $mp_module->where('id_mp_module', '=', $old_mp['id_mp_module'])->update(["updated" => true]);
        $mp_module->create(["version" => MP_VERSION]);
    }

    return true;
}
