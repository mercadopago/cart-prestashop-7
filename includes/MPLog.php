<?php
/**
* 2007-2020 PrestaShop
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
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*
* Don't forget to prefix your containers with your own identifier
* to avoid any conflicts with others containers.
*/

class MPLog
{
    public function __construct()
    {
    }

    /**
     * Generate logs on mercadopago.log
     *
     * @param string $message
     * @param string $status
     * @return void
     */
    public static function generate($message, $status = 'INFO')
    {
        switch ($status) {
            case 'warning':
                $status_log = 'WARNING';
                break;

            case 'error':
                $status_log = 'ERROR';
                break;

            default:
                $status_log = 'INFO';
        }

        $date = date('Y-m-d H:i:s');
        $file = MP_ROOT_URL . '/logs/mercadopago.log';
        $message = sprintf("[%s] [%s]: %s%s", $date, $status_log, $message, PHP_EOL);
        error_log($message, 3, $file);
    }
}
