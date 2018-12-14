{**
* 2007-2018 PrestaShop
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
*}
    <div class="row">
        <div class="col-xs-12">
            <img src="{$logo_mercadopago|escape:'htmlall':'UTF-8'}" class="logo-wrapper" alt="Mercado Pago">
        </div>
    </div>
    <br>

    {if $payment_status == "approved"}
        <div class="alert alert-success">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {l s='Thank you, your payment has been approved.' d='Modules.MercadoPago.Shop'}
        </div>
    {/if}
    {if $payment_status == "in_process"}
        <div class="alert alert-warning">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {l s='Thank you, your payment is being processed.' d='Modules.MercadoPago.Shop'}
        </div>
    {/if}
    {if $payment_status == "rejected"}
        <div class="alert alert-danger">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {l s='Sorry, your payment was declined.' d='Modules.MercadoPago.Shop'}
        </div>
    {/if}




