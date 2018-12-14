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
<form id="module_form" class="defaultForm form-horizontal" action="{$currentIndex|escape:'htmlall':'UTF-8'}" method="post" enctype="multipart/form-data">
    <div class="panel">
        <div class="panel-heading">{l s='Help' d='Modules.MercadoPago.Admin'}</div>
        <div class="panel-body">
            <div class="form-group">
                <label class="control-label col-lg-3"> {l s='View the log:' mod='mercadopago'} </label>
                <div class="col-lg-5">
                    <p><a href="{$log|escape:'htmlall':'UTF-8'}" class="btn btn-link" target="_blank" >{l s="Click here to see the error log" mod='mercadopago'}</a></p>
                </div>
            </div>
            <div class="form-group">
                <label class="control-label col-lg-3"> {l s='Check if your version is:' mod='mercadopago'} {$tagName|escape:'htmlall':'UTF-8'}</label>
                <div class="col-lg-5">
                    <p><a href="{$url|escape:'htmlall':'UTF-8'}" class="btn btn-link" target="_blank" >{l s="You can do the download here" mod='mercadopago'}</a></p>
                </div>
            </div>            
            <div class="form-group">
                <label class="control-label col-lg-3"> {l s='If you have problems:' mod='mercadopago'} </label>
                <div class="col-lg-5">
                    <p><a href="https://www.mercadopago.com.br/developers/pt/support" class="btn btn-link" target="_blank" >{l s="Click here to open a ticket" mod='mercadopago'}</a></p>
                </div>
            </div>           
        </div>
    </div>
</form>