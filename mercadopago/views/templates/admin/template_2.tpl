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

<div class="panel panel-landing">	
	<div class="mercadopago-content">
		
        <div class="mercadopago-banner" style="background-image: url({$module_dir|escape:'html':'UTF-8'}views/img/banner.jpg)">
            <div class="row mg-0">
                <div class="col-md-7 banner-gradient">
                    <div class="label-banner">
                        <h2 class="title-banner">{l s='Procesa pagos y despega tus ventas' mod='mercadopago'}</h2>
                        <p class="text-banner">{l s='Oferece a tus clientes la mejor' mod='mercadopago'} <br> {l s='experiencia de pago.' mod='mercadopago'}</p>
                        <a onclick="document.querySelectorAll('.nav-tabs li a')[0].click()" class="btn btn-default btn-banner">
                            {l s='Configura Mercado Pago' mod='mercadopago'}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mercadopago-installments">
            <div class="row row-installments">
                <div class="col-md-6">
                    <p class="text-installments">
                        {l s='Muestra tus promociones' mod='mercadopago'} <br> 
                        {l s='y vende en cuotas con la' mod='mercadopago'} <br> 
                        {l s='mejor financiación posible' mod='mercadopago'}
                    </p>
                </div>
                
                <div class="col-md-6 mr--10">
                    <img class="img-fluid img-tarjetitas" src="{$module_dir|escape:'html':'UTF-8'}views/img/tarjetitas.png" />
                </div>
            </div>
            
            <div class="row text-center">
                <div class="col-md-12">
                    <p class="info-installments">{l s='Te cobraremos una comisión de cada pago que recibas.' mod='mercadopago'}</p>
                </div>
            </div>
        </div>
        
        <div class="mercadopago-beneficios">
            <div class="row">
                <div class="col-md-6">
                    <h2 class="title-beneficios">
                        {l s='¿Cuáles son los beneficios de' mod='mercadopago'} <br> 
                        {l s='cobrar con Mercado Pago?' mod='mercadopago'}
                    </h2>
                    
                    <div class="panel-text-icon">
                        <img class="img-fluid icon-mercadopago" src="{$module_dir|escape:'html':'UTF-8'}views/img/icons/card.png" />
                        <span class="text-beneficios">{l s='Cobra como quieras y vende sin límites.' mod='mercadopago'}</span>
                    </div>
                    
                    <div class="panel-text-icon">
                        <img class="img-fluid icon-mercadopago" src="{$module_dir|escape:'html':'UTF-8'}views/img/icons/thick.png" />
                        <span class="text-beneficios">
                            {l s='Maximiza tu conversión con la mejor' mod='mercadopago'} <br> 
                            {l s='experiencia de pago.' mod='mercadopago'}
                        </span>
                    </div>
                    
                    <div class="panel-text-icon">
                        <img class="img-fluid icon-mercadopago" src="{$module_dir|escape:'html':'UTF-8'}views/img/icons/i-custom.png" />
                        <span class="text-beneficios">
                            {l s='Tienes herramientas listas para usar y' mod='mercadopago'} <br> 
                            {l s='especialistas dispuestos a ayudarte.' mod='mercadopago'}
                        </span>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <img class="img-fluid img-beneficios" src="{$module_dir|escape:'html':'UTF-8'}views/img/checkout2.png" />
                </div>
            </div>
        </div>
        
        <div class="mercadopago-recibopagos" style="background-image: url({$module_dir|escape:'html':'UTF-8'}views/img/bg-recibepagos.png)">            
            <div class="row">           
                <h2 class="title-recibopagos">{l s='¿Cómo recibo los pagos?' mod='mercadopago'}</h2>
                
                <div class="col-md-4 text-center px-10">
                    <p class="number-recibopagos number-recibopagos-after">1</p>
                    <p class="text-checkout-body">
                        {l s='Tus clientes pagan con el' mod='mercadopago'} <br> 
                        {l s='medio de pago que prefieran.' mod='mercadopago'}
                    </p>
                </div>
                
                <div class="col-md-4 text-center px-10">
                    <p class="number-recibopagos number-recibopagos-after number-recibopagos-before-green">2</p>
                    <p class="text-checkout-body">
                        {l s='El dinero se acredita en' mod='mercadopago'} <br> 
                        {l s='tu cuenta de Mercado Pago.' mod='mercadopago'}
                    </p>
                </div>
                
                <div class="col-md-4 text-center px-10">
                    <p class="number-recibopagos number-recibopagos-before">3</p>
                    <p class="text-checkout-body">
                        {l s='Una vez disponible,' mod='mercadopago'} <br> 
                        {l s='lo transfieres sin costo adicional a tu cuenta bancaria.' mod='mercadopago'}
                   </p>
                </div>
            </div>
        </div>
        
        <div class="mercadopago-beneficios">
            <div class="row">
                <div class="col-md-6">
                    <img class="img-fluid img-puedohacer" src="{$module_dir|escape:'html':'UTF-8'}views/img/checkout1.png" />
                </div>
                
                <div class="col-md-6">
                    <h2 class="title-beneficios">
                        {l s='¿Qué puedo hacer con' mod='mercadopago'} <br> 
                        {l s='Mercado Pago en mi tienda?' mod='mercadopago'}
                    </h2>
                    
                    <div class="panel-text-icon">
                        <img class="img-fluid icon-mercadopago" src="{$module_dir|escape:'html':'UTF-8'}views/img/icons/un-click.png" />
                        <span class="text-beneficios">
                            {l s='Compra con un click: recordamos' mod='mercadopago'} <br> 
                            {l s='los datos tus usuarios logueados.' mod='mercadopago'}
                        </span>
                    </div>
                    
                    <div class="panel-text-icon">
                        <img class="img-fluid icon-mercadopago pl-2 pr-20" src="{$module_dir|escape:'html':'UTF-8'}views/img/icons/perfil.png" />
                        <span class="text-beneficios">
                            {l s='Pago como invitado: no hace falta que tus' mod='mercadopago'} <br> 
                            {l s='clientes abran una cuenta en Mercado Pago.' mod='mercadopago'}
                        </span>
                    </div>
                    
                    <div class="panel-text-icon">
                        <img class="img-fluid icon-mercadopago" src="{$module_dir|escape:'html':'UTF-8'}views/img/icons/devolucion.png" />
                        <span class="text-beneficios">
                            {l s='Devolución de pagos y cancelación' mod='mercadopago'} <br> 
                            {l s='de pagos pendientes.' mod='mercadopago'}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="mercadopago-footer" style="background-image: url({$module_dir|escape:'html':'UTF-8'}views/img/bg-footer.png)">
            <div class="row">                
                <div class="col-md-12">
                    <h2 class="title-footer">
                        {l s='Crecer está en tus manos. ' mod='mercadopago'}
                        {l s='Oferece a tus clientes' mod='mercadopago'} <br> 
                        {l s='una experiencia de pago única.' mod='mercadopago'}
                    </h2>
                    <a href="" class="btn btn-default btn-footer">{l s='Configura Mercado Pago' mod='mercadopago'}</a>
                </div>
            </div>
        </div>
        
        <div class="mercadopago-partner">
            <div class="row">                
                <div class="col-md-6 left-partner">
                    <img class="img-fluid img-partner" src="{$module_dir|escape:'html':'UTF-8'}views/img/partner.png" />
                    <span>{l s='Somos partners oficiales de Prestashop.' mod='mercadopago'}</span>
                </div>
                
                <div class="col-md-6 right-partner">
                    <a href="{$seller_protect_link|escape:'html':'UTF-8'}" target="_blank" class="fl-right">{l s='Conoce nuestro Programa de Protección de vendedores.' mod='mercadopago'}</a>
                </div>
            </div>
        </div>
        
	</div>
</div>