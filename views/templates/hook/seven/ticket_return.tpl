{*
* 2007-2019 PrestaShop
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
* @author PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2020 PrestaShop SA
* @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}

{if $ticket_url != null}
    <div class="row">
        <div class="col-md-12">
            <div class="mp-ticket-return">

                <h2 class="ticket-return-title">{l s='Thank you for your purchase! We are awaiting the payment.' mod='mercadopago'}</h2>

                <div class="row mp-ticket-frame">
                    <div class="col-md-12 mp-hg-100">
                        <iframe src="{$ticket_url|escape:'htmlall':'UTF-8'}" id="ticket-frame" name="ticket-frame">
                            <div class="lightbox" id="text">
                                <div class="box">
                                    <div class="content">
                                        <div class="processing">
                                            <span>{l s='Processing...' mod='mercadopago'}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </iframe>
                    </div>

                    <div class="col-md-12">
                        <a href="{$ticket_url|escape:'htmlall':'UTF-8'}" target="_blank" class="btn btn-primary">
                            {l s='Print ticket' mod='mercadopago'}
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
{/if}