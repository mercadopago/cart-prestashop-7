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

namespace Helpers;

/**
 * Class Request
 */
class Request {
	/**
	 * Get header Authorization
	 */
	public static function getAuthorizationHeader() {
		$headers = null;
		if (isset($_SERVER['Authorization'])) {
			$headers = trim($_SERVER['Authorization']);
		} elseif ( isset($_SERVER['HTTP_AUTHORIZATION']) ) {
			$headers = trim($_SERVER['HTTP_AUTHORIZATION']);
		} elseif (function_exists('apache_request_headers')) {
			$requestHeaders = apache_request_headers();
			// Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
			$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
			if (isset($requestHeaders['Authorization'])) {
				$headers = trim($requestHeaders['Authorization']);
			}
		}
		return $headers;
	}

	/**
	 * Get access token from header
	 */
	public static function getBearerToken() {
		$headers = self::getAuthorizationHeader();

		// HEADER: Get the access token from the header
		if (!empty($headers)) {
			if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
				return $matches[1];
			}
		}

		return null;
	}

	/**
	 * Get json body
	 */
	public static function getJsonBody() {
		$post = file_get_contents('php://input');
		$post = (array) json_decode($post);

		if ( isset($post) && !empty($post) ) {
			return $post;
		}

		return null;
	}
}
