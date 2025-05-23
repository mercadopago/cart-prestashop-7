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

        <div class="mercadopago-banner" style="background-image: url('https://http2.mlstatic.com/storage/cpp/static-files/9b486a54-ac9f-4dd7-b2fb-7bc9eee2eb17.png')">
            <div class="row mp-mg-0">
                <div class="col-md-7">
                    <div class="mp-label-banner">
                        <img class="mp-logo-banner" src="https://http2.mlstatic.com/storage/cpp/static-files/cf216978-0e5d-4a2e-b67d-8232b098ae00.png">
                        <h2 class="mp-title-banner">{l s='Take off your' mod='mercadopago'} <br> {l s='online sales' mod='mercadopago'}</h2>
                        <p class="mp-text-banner">{l s='Offer your customers the best' mod='mercadopago'} <br> {l s='payment experience.' mod='mercadopago'}</p>
                        <a href="" class="btn btn-default mp-btn-banner">
                            {l s='Configure Mercado Pago' mod='mercadopago'}
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="mercadopago-installments">
            <div class="row mp-row-installments">
                <div class="col-md-6 row-text-installments">
                    <p class="text-installments">
                        {l s='Show your promotions' mod='mercadopago'} <br>
                        {l s='and sell in installments with' mod='mercadopago'} <br>
                        {l s='the best possible financing.' mod='mercadopago'}
                    </p>
                </div>

                <div class="col-md-6 row-img-installments">
                    <img class="img-fluid mp-img-tarjetitas" src="https://http2.mlstatic.com/storage/cpp/static-files/df9cbe35-cb7f-42cd-aeaf-4b62d239020e.png" alt="Installments"/>
                </div>
            </div>

            <div class="row text-center">
                <div class="col-md-12">
                    <p class="mp-info-installments">{l s='We will charge you a fee for each payment you receive' mod='mercadopago'}</p>
                </div>
            </div>
        </div>

        <div class="mercadopago-beneficios">
            <div class="row">
                <div class="col-md-6">
                    <h2 class="mp-title-beneficios">
                        {l s='What are the benefits of' mod='mercadopago'} <br>
                        {l s='charging with Mercado Pago?' mod='mercadopago'}
                    </h2>

                    <div class="mp-panel-text-icon">
                        <img class="img-fluid icon-mercadopago" src="https://http2.mlstatic.com/storage/cpp/static-files/52042af4-ea50-435f-a73f-e271983f9f9b.png"  alt="Credit card"/>
                        <span class="mp-text-beneficios">{l s='Charge as you want and sell without limits.' mod='mercadopago'}</span>
                    </div>

                    <div class="mp-panel-text-icon"> 
                        <img class="img-fluid icon-mercadopago" src="https://http2.mlstatic.com/storage/cpp/static-files/6ef8c800-364d-44e9-9df6-c7d7fb6977fb.png" alt="Ticket"/>
                        <span class="mp-text-beneficios">
                            {l s='Maximize your conversion with' mod='mercadopago'} <br>
                            {l s='the best payment experience.' mod='mercadopago'}
                        </span>
                    </div>

                    <div class="mp-panel-text-icon">
                        <img class="img-fluid icon-mercadopago" src="https://http2.mlstatic.com/storage/cpp/static-files/87e4f433-eaa8-42df-83f7-8278eba23022.png" alt="Custom"/>
                        <span class="mp-text-beneficios">
                            {l s='You have ready-to-use tools and' mod='mercadopago'} <br>
                            {l s='specialists willing to help you.' mod='mercadopago'}
                        </span>
                    </div>
                </div>

                <div class="col-md-6">
                    <img class="img-fluid mp-img-beneficios" src="https://http2.mlstatic.com/storage/cpp/static-files/8d3e158e-7e23-430e-bfed-ae2d52c225db.png" alt="Benefit"/>
                </div>
            </div>
        </div>

        <div class="mercadopago-recibopagos">
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
                    <img class="img-fluid mp-img-puedohacer" src="https://http2.mlstatic.com/storage/cpp/static-files/56428e0d-9b28-4445-b2a1-1909fe680021.png" />
                </div>
                <div class="col-md-6">
                    <div class="mp-panel-title">
                         <h2 class="mp-title-beneficios mp-beneficios-icon">
                            {l s='What can I do with' mod='mercadopago'} <br>
                            {l s='Mercado Pago in my store?' mod='mercadopago'}
                        </h2>
                    </div>
                    <div class="mp-panel-text-icon mp-beneficios-icon">
                        <img class="img-fluid icon-mercadopago" src="https://http2.mlstatic.com/storage/cpp/static-files/fcb4c10d-806b-47d5-9651-b7c317269ee4.png" />
                        <span class="mp-text-beneficios">
                            {l s='One click purchase: we remember the data of' mod='mercadopago'} <br>
                            {l s='your logged users.' mod='mercadopago'}
                        </span>
                    </div>

                    <div class="mp-panel-text-icon mp-beneficios-icon">
                        <img class="img-fluid icon-mercadopago mp-pl-2 mp-pr-20" src="https://http2.mlstatic.com/storage/cpp/static-files/ae45e403-b371-466b-899c-59ca9e300941.png" />
                        <span class="mp-text-beneficios">
                            {l s='Payment as a guest: your customers do not' mod='mercadopago'} <br>
                            {l s='have to open a Mercado Pago account.' mod='mercadopago'}
                        </span>
                    </div>

                    <div class="mp-panel-text-icon mp-beneficios-icon">
                        <img class="img-fluid icon-mercadopago" src="https://http2.mlstatic.com/storage/cpp/static-files/65fe3922-133e-4a2b-9ee9-25531b8e82ab.png" />
                        <span class="mp-text-beneficios">
                            {l s='Return of payments and cancellation' mod='mercadopago'} <br>
                            {l s='of pending payments.' mod='mercadopago'}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="mercadopago-footer" style="background-image: url(https://http2.mlstatic.com/storage/cpp/static-files/8351df1d-7a12-4803-a876-69fe7dc34969.jpg)">
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
