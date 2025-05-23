{*
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
*}

<div class="panel mp-panel-landing">
	<div class="mercadopago-content">

        <div class="mercadopago-banner" style="background-image: url({$module_dir|escape:'html':'UTF-8'}views/img/banner.jpg)">
            <div class="row mp-mg-0">
                <div class="col-md-7 mp-banner-gradient">
                    <div class="mp-label-banner">
                        <h2 class="mp-title-banner">{l s='Take off your online sales.' mod='mercadopago'}</h2>
                        <p class="mp-text-banner">{l s='Offer your customers the best' mod='mercadopago'} <br> {l s='payment experience.' mod='mercadopago'}</p>
                        <a href="" class="btn btn-default mp-btn-banner">
                            {l s='Configure Mercado Pago in your store' mod='mercadopago'}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mercadopago-installments">
            <div class="row mp-row-installments">
                <div class="col-md-6">
                    <p class="text-installments">
                        {l s='Show your promotions' mod='mercadopago'} <br>
                        {l s='and sell in installments with' mod='mercadopago'} <br>
                        {l s='the best possible financing.' mod='mercadopago'}
                    </p>
                </div>

                <div class="col-md-6 mp-mr--10">
                    <img class="img-fluid mp-img-tarjetitas" src="{$module_dir|escape:'html':'UTF-8'}views/img/tarjetitas.png" />
                </div>
            </div>

            <div class="row text-center">
                <div class="col-md-12">
                    <p class="mp-info-installments">{l s='We will charge you a fee for each payment you receive.' mod='mercadopago'}</p>
                </div>
            </div>
        </div>

        <div class="mercadopago-beneficios">
            <div class="row">
                <div class="col-md-6">
                    <h2 class="mp-title-beneficios"
                        {l s='What are the benefits of' mod='mercadopago'} <br>
                        {l s='charging with Mercado Pago?' mod='mercadopago'}
                    </h2>

                    <div class="mp-panel-text-icon">
                        <img class="img-fluid icon-mercadopago" src="{$module_dir|escape:'html':'UTF-8'}views/img/icons/card.png" />
                        <span class="mp-text-beneficios">{l s='Charge as you want and sell without limits.' mod='mercadopago'}</span>
                    </div>

                    <div class="mp-panel-text-icon">
                        <img class="img-fluid icon-mercadopago" src="{$module_dir|escape:'html':'UTF-8'}views/img/icons/thick.png" />
                        <span class="mp-text-beneficios">
                            {l s='Maximize your conversion with' mod='mercadopago'} <br>
                            {l s='the best payment experience.' mod='mercadopago'}
                        </span>
                    </div>

                    <div class="mp-panel-text-icon">
                        <img class="img-fluid icon-mercadopago" src="{$module_dir|escape:'html':'UTF-8'}views/img/icons/i-custom.png" />
                        <span class="mp-text-beneficios">
                            {l s='You have ready-to-use tools and' mod='mercadopago'} <br>
                            {l s='specialists willing to help you.' mod='mercadopago'}
                        </span>
                    </div>
                </div>

                <div class="col-md-6">
                    <img class="img-fluid mp-img-beneficios" src="{$module_dir|escape:'html':'UTF-8'}views/img/checkout2.png" />
                </div>
            </div>
        </div>

        <div class="mercadopago-recibopagos" style="background-image: url({$module_dir|escape:'html':'UTF-8'}views/img/bg-recibepagos.png)">
            <div class="row">
                <h2 class="mp-title-recibopagos">{l s='How do I receive the payments?' mod='mercadopago'}</h2>

                <div class="col-md-4 text-center mp-px-10">
                    <p class="mp-number-recibopagos mp-number-recibopagos-after">1</p>
                    <p class="mp-text-checkout-body">
                        {l s='Your customers pay as they prefer.' mod='mercadopago'}
                    </p>
                </div>

                <div class="col-md-4 text-center mp-px-10">
                    <p class="mp-number-recibopagos mp-number-recibopagos-after mp-number-recibopagos-before-green">2</p>
                    <p class="mp-text-checkout-body">
                        {l s='The money is credited' mod='mercadopago'} <br>
                        {l s='to your Mercado Pago account.' mod='mercadopago'}
                    </p>
                </div>

                <div class="col-md-4 text-center mp-px-10">
                    <p class="mp-number-recibopagos mp-number-recibopagos-before">3</p>
                    <p class="mp-text-checkout-body">
                        {l s='Once available,' mod='mercadopago'} <br>
                        {l s='you transfer it at no additional cost to your bank account.' mod='mercadopago'}
                   </p>
                </div>
            </div>
        </div>

        <div class="mercadopago-beneficios">
            <div class="row">
                <div class="col-md-6">
                    <img class="img-fluid mp-img-puedohacer" src="{$module_dir|escape:'html':'UTF-8'}views/img/checkout1.png" />
                </div>

                <div class="col-md-6">
                    <h2 class="mp-title-beneficios">
                        {l s='What can I do with' mod='mercadopago'} <br>
                        {l s='Mercado Pago in my store?' mod='mercadopago'}
                    </h2>

                    <div class="mp-panel-text-icon">
                        <img class="img-fluid icon-mercadopago" src="{$module_dir|escape:'html':'UTF-8'}views/img/icons/un-click.png" />
                        <span class="mp-text-beneficios">
                            {l s='One click purchase:' mod='mercadopago'} <br>
                            {l s='we remember the data of your logged users.' mod='mercadopago'}
                        </span>
                    </div>

                    <div class="mp-panel-text-icon">
                        <img class="img-fluid icon-mercadopago mp-pl-2 mp-pr-20" src="{$module_dir|escape:'html':'UTF-8'}views/img/icons/perfil.png" />
                        <span class="mp-text-beneficios">
                            {l s='Payment as a guest: your' mod='mercadopago'} <br>
                            {l s='customers do not have to open a Mercado Pago account.' mod='mercadopago'}
                        </span>
                    </div>

                    <div class="mp-panel-text-icon">
                        <img class="img-fluid icon-mercadopago" src="{$module_dir|escape:'html':'UTF-8'}views/img/icons/devolucion.png" />
                        <span class="mp-text-beneficios">
                            {l s='Return of payments and cancellation' mod='mercadopago'} <br>
                            {l s='of pending payments.' mod='mercadopago'}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mercadopago-footer" style="background-image: url({$module_dir|escape:'html':'UTF-8'}views/img/bg-footer.png)">
            <div class="row">
                <div class="col-md-12">
                    <h2 class="mp-title-footer">
                        {l s='Going further is in your hands. ' mod='mercadopago'}
                        {l s='Offer your customers ' mod='mercadopago'} <br>
                        {l s='a unique payment experience.' mod='mercadopago'}
                    </h2>
                    <a href="" class="btn btn-default mp-btn-footer">{l s='Configure Mercado Pago in your store' mod='mercadopago'}</a>
                </div>
            </div>
        </div>

        <div class="mercadopago-partner">
            <div class="row">
                <div class="col-md-6 mp-left-partner">
                    <img class="img-fluid mp-img-partner" src="{$module_dir|escape:'html':'UTF-8'}views/img/partner.png" />
                    <span>{l s='We are official partners of Prestashop' mod='mercadopago'}</span>
                </div>

                <div class="col-md-6 mp-right-partner">
                    <a href="{$seller_protect_link|escape:'html':'UTF-8'}" target="_blank" class="mp-fl-right">{l s='Seller Protection Program.' mod='mercadopago'}</a>
                </div>
            </div>
        </div>
	</div>
</div>
