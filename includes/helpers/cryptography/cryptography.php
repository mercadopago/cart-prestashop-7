<?php
/**
 * 2007-2021 PrestaShop
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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2021 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

/**
 * Class Cryptography
 */
class Cryptography 
{

    /**
     * Object to String
     * @param array $array
     * @return String
     */
    public static function obj_to_string( $array ) 
    {
        ksort($array);

        $data = '';

        foreach ($array as $key=>$value) {
            $data .= $key . '=' . $value . '&';
        }

        $data = substr( $data, 0, -1 );

        return $data;
    }

    /**
     * Verify token
     * @param String $key
     * @param String $hmac
     * @return Boolean
     */
    public static function verify( $key, $hmac ) 
    {
        if (hash_equals($key, $hmac)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Encrypt
     * @param array $data
     * @param String $secret
     * @return String
     */
    public static function encrypt( $data, $secret ) 
    {
        if (!empty($secret) && !empty($data)) {
            try {
                $string = self::obj_to_string($data);
                $hmac   = hash_hmac('sha256', $string, $secret, true);
                $key    = base64_encode($hmac);
                return $key;
            } catch (Exception $e) {
                throw new Exception($e);
            }
        } else {
            throw new Exception('Empty parameters');
        }
    }

    
}
