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
 * If a copy of the license was not supplied, consult LICENSE.md
 * or the license URL below.
 *
 * @author    PrestaShop SA and Contributors
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/** Read-only SDK resource; retain authenticated raw response identity. */
class AuthenticatedSellerResource extends MercadoPago\PP\Sdk\Entity\Payment\Payment
{
    public function getUris(): array
    {
        return ['get' => '/users/:id'];
    }
}
