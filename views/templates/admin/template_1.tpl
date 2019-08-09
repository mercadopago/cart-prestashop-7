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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2019 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="panel panel-mercadopago">
	<div class="row header-mercadopago">
        <div class="left-details">
            <h2 class="title-checkout-header">{l s='Diseña la mejor experiencia de pago para tus clientes' mod='mercadopago'}</h2>
        </div>
        <div class="right-details">
            <img src="{$module_dir|escape:'html':'UTF-8'}views/img/mpinfo_logo.png" class="img-fluid header-mp-logo" id="payment-logo" />
        </div>
	</div>

	<hr />
	
	<div class="mercadopago-content">
		<div class="row">
            <div class="col-md-12">
                <h4 class="title-checkout-body">{l s='Sigue estos pasos y maximiza tu conversión:' mod='mercadopago'}</h4>
            </div>
		</div>
        
        <div class="row pt-15">
            <div class="col-md-2 text-center w-25 px-10">
                {if $access_token != '' && $sandbox_access_token != ''}
                    <p class="number-checked"><i class="icon-check"></i></p>
                {else}
                    <p class="number-checkout-body">1</p>
                {/if}
                
                <p class="text-checkout-body">
                    {l s='Obtén tus' mod='mercadopago'} 
                    <b>{l s='credenciales' mod='mercadopago'}</b> 
                    {l s='en tu cuenta de Mercado Pago.' mod='mercadopago'}
                </p>
            </div>
            
            <div class="col-md-2 text-center w-25 px-10">
                {if $seller_homolog == true}
                    <p class="number-checked"><i class="icon-check"></i></p>
                {else}
                    <p class="number-checkout-body">2</p>
                {/if}
                
                <p class="text-checkout-body">
                    {l s='Homologa tu cuenta para poder cobrar.' mod='mercadopago'}
                </p>
            </div>
            
            <div class="col-md-2 text-center w-25 px-10">
                {if $standard_test == true}
                    <p class="number-checked"><i class="icon-check"></i></p>
                {else}
                    <p class="number-checkout-body">3</p>
                {/if}
                
                <p class="text-checkout-body">
                    {l s='Elige los' mod='mercadopago'} 
                    <b>{l s='medios de pago' mod='mercadopago'}</b> 
                    {l s='disponibles en tu tienda.' mod='mercadopago'}
                </p>
            </div>
            
            <div class="col-md-2 text-center w-25 px-10">
                {if $count_test != 0}
                    <p class="number-checked"><i class="icon-check"></i></p>
                {else}
                    <p class="number-checkout-body">4</p>
                {/if}
                
                <p class="text-checkout-body">
                    {l s='Deja activo' mod='mercadopago'} 
                    <b>{l s='Sandbox' mod='mercadopago'}</b> 
                    {l s='para testear compras en tu tienda.' mod='mercadopago'}
                </p>
            </div>
            
            <div class="col-md-2 text-center w-25 px-10">
                {if $sandbox_status != true}
                    <p class="number-checked"><i class="icon-check"></i></p>
                {else}
                    <p class="number-checkout-body">5</p>
                {/if}
                <p class="text-checkout-body">{l s='Desactívalo cuando veas que todo va bien y ¡empieza a recibir pagos!' mod='mercadopago'}</p>
            </div>
		</div>
		
		<div class="row pt-30">
            <div class="col-md-6">
                <p class="text-branded lists-how-configure">
                    {l s='Las credenciales son las claves que te proporcionamos para que integres de forma rápida y segura.' mod='mercadopago'}
                    {l s='Debes tener una cuenta homologada en Mercado Pago para cobrar en tu sitio web.' mod='mercadopago'}
                    {l s='No necesitas saber diseñar o programar para activar Mercado Pago en tu tienda. ' mod='mercadopago'}
                </p>
            </div>
		</div>        
	</div>
</div>

<!-- Panel for MP Connect
<div class="panel">
    <div class="panel-heading">
	    <i class="icon-cogs"></i> {l s='Credenciales' mod='mercadopago'}
	</div>        
    
    <div class="mercadopago-content">
		<div class="row">
        <div class="col-md-12">
            <h4 class="title-checkout-body">{l s='Activa tus credenciales según lo que quieras hacer.' mod='mercadopago'}</h4>
        </div>
		</div>
        
        <div class="row pt-15">
            <div class="col-md-12">
                <p class="text-credenciais">
                    {l s='Realiza pruebas antes de salir al mundo.' mod='mercadopago'} 
                    <b>{l s='Opera de dos formas:' mod='mercadopago'}</b>
                </p>
                <p class="text-credenciais">
                    {l s='Por defecto te dejamos' mod='mercadopago'} 
                    <b>{l s='el modo Sandbox activo' mod='mercadopago'}</b> 
                    {l s='para que hagas testeos antes de empezar a vender.' mod='mercadopago'}
                </p>
                <p class="text-credenciais">
                    {l s='¿Todo va bien?' mod='mercadopago'} 
                    <b>{l s='Desactiva Sandbox' mod='mercadopago'}</b> 
                    {l s='al final de la configuración y abre paso a tus ventas online.' mod='mercadopago'}
                </p>
            </div>
        </div>
        
        <div class="row pt-25">
            <div class="col-md-12">
                <a class="btn btn-default btn-credenciais">{l s='Quiero mis credenciales' mod='mercadopago'}</a>
            </div>
        </div>
        
        <div class="row pt-25">
            <div class="col-md-6">
                <p class="text-branded lists-how-configure">
                    <b>{l s='Atención:' mod='mercadopago'}</b> {l s='Crea una cuenta en Mercado Pago para obtener tus credenciales.' mod='mercadopago'}
                    <a href="#" target="_blank">{l s='Homologa tu cuenta' mod='mercadopago'}</a> 
                    {l s='en Mercado Pago para ir a Producción y cobrar en tu tienda.' mod='mercadopago'}
                </p>
            </div>
        </div>
    </div>
</div>
-->

<!-- forms rendered via class from mercadopago.php -->
{html_entity_decode($country_form|escape:'html':'UTF-8')}
{html_entity_decode($credentials|escape:'html':'UTF-8')}

{if $access_token != '' && $sandbox_access_token != ''}
  
    {if $sandbox_status == true || $seller_homolog == true}
        <div style="display: none">{html_entity_decode($homolog_form|escape:'html':'UTF-8')}</div>
    {else}
        <div style="display: block">{html_entity_decode($homolog_form|escape:'html':'UTF-8')}</div>
    {/if}
    
    {html_entity_decode($standard_form|escape:'html':'UTF-8')}
    {html_entity_decode($advanced_form|escape:'html':'UTF-8')}
    
    {if $sandbox_status == true}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='Prueba tu tienda' mod='mercadopago'}
        </div>        

        <div class="mercadopago-content">
            <div class="row">
                <div class="col-md-12">
                    <h4 class="title-checkout-body">{l s='¿Todo configurado? Ve a tu tienda en modo Sandbox' mod='mercadopago'}</h4>
                </div>
            </div>

            <div class="row pt-15">
                <div class="col-md-12">
                    <p class="text-credenciais">{l s='Visita tu tienda como si fueras uno de tus mejores clientes.' mod='mercadopago'}</p>
                    <p class="text-credenciais">{l s='Revisa que todo esté bien para impresionarlos y aumentar tus ventas.' mod='mercadopago'}</p>
                </div>
            </div>

            <div class="row pt-25">
                <div class="col-md-12">
                    <a href="{$url_base|escape:'html':'UTF-8'}" target="_blank" class="btn btn-default btn-credenciais">{l s='Quiero testear mis ventas' mod='mercadopago'}</a>
                </div>
            </div>
        </div>
    </div>
    {else}
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='Comienza a vender' mod='mercadopago'}
        </div>        

        <div class="mercadopago-content">
            <div class="row">
                <div class="col-md-12">
                    <h4 class="title-checkout-body">{l s='Todo listo para el despegue de tus ventas' mod='mercadopago'}</h4>
                </div>
            </div>

            <div class="row pt-15">
                <div class="col-md-12">
                    <p class="text-credenciais">{l s='Ya saliste a Producción. Solo falta que tus mejores clientes lleguen a tu tienda' mod='mercadopago'}</p>
                    <p class="text-credenciais">{l s='para vivir la mejor experiencia de compra online com Mercado Pago.' mod='mercadopago'}</p>
                </div>
            </div>

            <div class="row pt-25">
                <div class="col-md-12">
                    <a href="{$url_base|escape:'html':'UTF-8'}" target="_blank" class="btn btn-default btn-credenciais">{l s='Visitar mi tienda' mod='mercadopago'}</a>
                </div>
            </div>
        </div>
    </div>
    {/if}
{/if}

<!-- Evaluation modal -->
<hr class="hr-mp-modal">
<div class="row">
    <div class="col-md-8">
        {l s='¿Algo anda mal? Ponte en' mod='mercadopago'}
        
        {if $country_link == 'mlb'}
          <a href="https://www.mercadopago.com.br/developers/pt/support" target="_blank">{l s='contacto con nuestro soporte' mod='mercadopago'}</a>
        {else}
          <a href="https://www.mercadopago.com.br/developers/es/support" target="_blank">{l s='contacto con nuestro soporte' mod='mercadopago'}</a>
        {/if}
    </div>
    
    <div class="col-md-4 text-right">
        <a class="link-modal-trigger lists-how-configure" data-toggle="modal" data-target="#rating-modal">
            {l s='Tu opinión nos ayuda a mejorar' mod='mercadopago'}
        </a>

        <!-- Modal -->
        <div class="modal rating-modal fade" id="rating-modal" tabindex="-1" role="dialog" aria-labelledby="rating-modal">
            <div class="modal-dialog rating-modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header rating-modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <h3 class="modal-title" id="myModalLabel">{l s='Tu opinión nos ayuda a mejorar.' mod='mercadopago'}</h3>
                    </div>
                        
                    <form action="" method="post">
                        <div class="modal-body rating-modal-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <p class="label-rating-input">
                                        {l s='Del 1 al 10, ¿qué tan probable es que recomiendes nuestro módulo a un amigo?' mod='mercadopago'}
                                    </p>
                                    <div class="rating-box pb-10">
                                        {for $i=1 to 10 step 1}
                                            <div class="rating-input">
                                                <input type="radio" value="{$i|escape:'html':'UTF-8'}" name="mercadopago-rating" id="rating{$i|escape:'html':'UTF-8'}" class="pointer" /><br>
                                                <label for="rating{$i|escape:'html':'UTF-8'}" class="label-rating pointer">{$i|escape:'html':'UTF-8'}</label>
                                            </div>
                                        {/for}
                                    </div>

                                    <div class="col-md-6">
                                        <p>1 - {l s='Nada probable' mod='mercadopago'}</p>
                                    </div>

                                    <div class="col-md-6">
                                        <p class="fl-right">10 - {l s='Muy probable' mod='mercadopago'}</p>
                                    </div>
                                </div>

                                <div class="col-md-12 pt-30">
                                    <p class="label-rating-input"><b>{l s='¿Comentarios o sugerencias? Este es el espacio ideal:' mod='mercadopago'}</b></p>
                                    <textarea name="mercadopago-comments" class="textarea-rating-module" placeholder="{l s='Escribe tu comentario' mod='mercadopago'}"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer rating-modal-footer">
                            <button type="button" class="btn btn-default" data-dismiss="modal">{l s='Cerrar' mod='mercadopago'}</button>
                            <input type="submit" class="btn btn-primary btn-rating-submit" name="submitMercadopagoRating" value="{l s='Enviar' mod='mercadopago'}" />
                        </div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</div>