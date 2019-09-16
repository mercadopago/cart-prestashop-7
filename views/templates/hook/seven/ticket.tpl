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
* @copyright 2007-2019 PrestaShop SA
* @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}

<form id="mp_ticket_checkout" class="mp-checkout-form" method="post" action="{$redirect}">
    <div class="row frame-checkout-custom-seven">

        {if $coupon == true}
        <div id="mercadopago-form-ticket-coupon" class="col-xs-12 col-md-12 col-12">
            <h3 class="title-custom-checkout">{l s='Ingresa tu cupón de descuento' mod='mercadopago'}</h3>

            <div class="form-group">
                <div class="col-md-9 col-xs-8 pb-10 pl-0 mp-m-col">
                    <input type="text" id="couponCode" class="form-control mp-form-control" autocomplete="off" maxlength="24" placeholder="{l s='Ingresá tu cupón' mod='mercadopago'}" />
                </div>

                <div class="col-md-3 col-xs-4 pb-10 pr-0 text-center mp-m-col">
                    <input type="button" class="btn btn-primary mp-btn" id="applyCoupon" value="{l s='Aplicar' mod='mercadopago'}">
                </div>
            </div>
        </div>
        {/if}

        <div id="mercadopago-form" class="col-xs-12 col-md-12 col-12">
            <div class="form-group">
                <div class="col-md-12 col-12 pb-20 px-0">
                    <div class="form-check mp-form-check">
                        <div class="col-md-4 col-4 col-xs-6 pl-0">
                            <input class="form-check-input mp-checkbox" type="radio" value="CPF" id="mp_cpf" name="mercadopago_ticket[docType]" checked onchange="selectDocumentType()">
                            <label class="form-check-label fl-left pl-10" for="mp_cpf">{l s='Persona Física' mod='mercadopago'}</label>
                        </div>
                    </div>
                    <div class="form-check mp-form-check">
                        <div class="col-md-4 col-4 col-xs-6 m-pr-0">
                            <input class="form-check-input mp-checkbox" type="radio" value="CNPJ" id="mp_cnpj" name="mercadopago_ticket[docType]" onchange="selectDocumentType()">
                            <label class="form-check-label fl-left pl-10" for="mp_cnpj">{l s='Persona Jurídica' mod='mercadopago'}</label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <div id="mp-firstname" class="col-md-4 col-4 col-xs-6 pb-10 pl-0">
                    <label for="" id="mp-name-label" class="pb-5">{l s='Nombre' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <label for="" id="mp-social-label" class="pb-5">{l s='Razón social' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_firstname" name="mercadopago_ticket[firstname]" class="form-control mp-form-control" autocomplete="off" />
                    <small class="mp-erro-febraban" id="mp_firstname_error">{l s='This field can not be null' mod='mercadopago'}</small>
                </div>

                <div id="mp-lastname" class="col-md-4 col-4 col-xs-6 pb-10 m-pr-0">
                    <label for="" class="pb-5">{l s='Apellido' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_lastname" name="mercadopago_ticket[lastname]" class="form-control mp-form-control" autocomplete="off" />
                    <small class="mp-erro-febraban" id="mp_lastname_error">{l s='This field can not be null' mod='mercadopago'}</small>
                </div>

                <div class="col-md-4 col-4 col-xs-12 pb-10 pr-0 mp-m-col">
                    <label for="docNumberError" id="mp-cpf-label" class="pb-5">{l s='CPF' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <label for="docNumberError" id="mp-cnpj-label" class="pb-5">{l s='CNPJ' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_docNumber" name="mercadopago_ticket[docNumber]" class="form-control mp-form-control" onblur="cpfValidate(this.value)" autocomplete="off" />
                    <small class="mp-erro-febraban" id="mp_docNumber_error">{l s='The document must be valid' mod='mercadopago'}</small>
                </div>
            </div>

            <div class="form-group">
                <div class="col-md-8 col-8 col-xs-8 pb-20 pl-0">
                    <label for="" class="pb-5">{l s='Dirección' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_address" name="mercadopago_ticket[address]" class="form-control mp-form-control" autocomplete="off" />
                    <small class="mp-erro-febraban" id="mp_address_error">{l s='This field can not be null' mod='mercadopago'}</small>
                </div>

                <div class="col-md-4 col-4 col-xs-4 pb-20 pr-0">
                    <label for="" class="pb-5">{l s='Número' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_number" name="mercadopago_ticket[number]" class="form-control mp-form-control" autocomplete="off" />
                    <small class="mp-erro-febraban" id="mp_number_error">{l s='This field can not be null' mod='mercadopago'}</small>
                </div>
            </div>

            <div class="form-group">
                <div class="col-md-4 col-4 col-xs-6 pb-20 pl-0">
                    <label for="" class="pb-5">{l s='Ciudad' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_city" name="mercadopago_ticket[city]" class="form-control mp-form-control" autocomplete="off" />
                    <small class="mp-erro-febraban" id="mp_city_error">{l s='This field can not be null' mod='mercadopago'}</small>
                </div>

                <div class="col-md-4 col-4 col-xs-6 pb-20 m-pr-0">
                    <label for="" class="pb-5">{l s='Estado' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <select id="mp_state" name="mercadopago_ticket[state]" class="form-control mp-form-control mp-select pointer">
                        <option value="SP" selected="selected">São Paulo</option>
                    </select>
                </div>

                <div class="col-md-4 col-4 col-xs-12 pb-20 pr-0 mp-m-col">
                    <label for="" class="pb-5">{l s='Código postal' mod='mercadopago'} <em class="mp-required">*</em></label>
                    <input type="text" id="mp_zipcode" name="mercadopago_ticket[zipcode]" class="form-control mp-form-control" autocomplete="off" />
                    <small class="mp-erro-febraban" id="mp_zipcode_error">{l s='This field can not be null' mod='mercadopago'}</small>
                </div>
            </div>

            <div class="col-md-12 col-xs-12 col-12 px-0 mp-m-col">
                <p class="all-required">{l s='Completa todos los campos, son obligatorios.' mod='mercadopago'}</p>
            </div>

            <div class="col-md-12 col-12 frame-title">
                <h3 class="title-custom-checkout">{l s='Por favor, selecciona el emisor de su elección' mod='mercadopago'}</h3>
            </div>

            <div class="form-group">
                {if count($ticket) != 0}
                    {foreach $ticket as $tarjeta}
                        <div class="col-md-6 col-6 col-xs-6 px-0 mp-m-col">
                            <div class="form-check mp-form-check">
                                <input name="mercadopago_ticket[paymentMethodId]" id="{$tarjeta['id']}" class="form-check-input mp-checkbox" value="{Tools::strtolower($tarjeta['id'])}"type="radio">
                                <label class="form-check-label pb-20" for="{$tarjeta['id']}">
                                    <img src="{$tarjeta['image']}" alt="{$tarjeta['name']}" />
                                    <span class="text-ticket-tarjeta">{$tarjeta['name']}</span>
                                </label>
                            </div>
                        </div>
                    {/foreach}
                {/if}
            </div>
        </div>

    </div>
</form>