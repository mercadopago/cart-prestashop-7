{**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, consult the license URL above.
 *
 * @author    PrestaShop SA and Contributors
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 *}
{extends file='page.tpl'}

{block name='page_title'}
  {l s='Payment confirmation required' mod='mercadopago'}
{/block}

{block name='page_content'}
  <div class="alert alert-warning" role="status">
    <p>{l s='An earlier payment may be pending or partially approved. A new payment cannot be started until its outcome is confirmed.' mod='mercadopago'}</p>
    <p>{l s='Do not pay again. Contact the store or check payment confirmation.' mod='mercadopago'}</p>
  </div>
  <a class="btn btn-primary" href="{$mp_confirmation_retry_url|escape:'htmlall':'UTF-8'}">
    {l s='Check payment confirmation' mod='mercadopago'}
  </a>
{/block}
