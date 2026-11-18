<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 *
 * @author    PrestaShop SA and Contributors
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/** Register this patch version through the verified, idempotent upgrade routine. */
function upgrade_module_4_19_3($module)
{
    require_once __DIR__ . '/install-4.19.2.php';

    return upgrade_module_4_19_2($module);
}
