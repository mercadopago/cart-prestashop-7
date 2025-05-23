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

class MPLog
{
    const LOG_SEVERITY_INFORMATIVE = 1;
    const LOG_SEVERITY_WARNING = 2;
    const LOG_SEVERITY_ERROR = 3;

    /**
     * Get url for adminto view the logs
     *
     * @return void
     */
    public static function getLogUrl()
    {
        $ps_link = new Link();
        return $ps_link->getAdminLink('AdminLogs', true);
    }

    /**
     * Generate plugin logs
     *
     * @param string $message
     * @param string $severity
     * @return void
     */
    public static function generate($message, $severity = 1)
    {
        switch ($severity) {
            case 'warning':
                $severity_log = self::LOG_SEVERITY_WARNING;
                break;

            case 'error':
                $severity_log = self::LOG_SEVERITY_ERROR;
                break;

            default:
                $severity_log = self::LOG_SEVERITY_INFORMATIVE;
        }

        $object_id = str_replace('.', '', MP_VERSION);
        $object_type = 'Mercadopago';

        PrestaShopLogger::addLog($message, $severity_log, null, $object_type, $object_id, true, null);
    }
}
