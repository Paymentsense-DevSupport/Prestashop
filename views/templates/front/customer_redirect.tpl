{*
 * Copyright (C) 2018 Paymentsense Ltd.
 *
 * This program is free software: you can redistribute it and/or modify it under the terms
 * of the AFL Academic Free License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the AFL Academic Free License for more details. You should have received a copy of the
 * AFL Academic Free License along with this program. If not, see <http://opensource.org/licenses/AFL-3.0/>.
 *
 *  @author Paymentsense <devsupport@paymentsense.com>
 *  @copyright  2018 Paymentsense Ltd.
 *  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *}

<style type="text/css">
    #paymentsense_title {
        padding-top: 1em;
        padding-bottom: 1.5em;
    }
    #paymentsense_debug_title {
        padding-top: 1em;
        padding-bottom: 0.5em;
    }
</style>
{extends file='page.tpl'}
{block name="page_content"}
    <h1 id="paymentsense_title">{$paymentsense['title']|escape:'htmlall':'UTF-8'}</h1>
    <div class="content">
        <p>{$paymentsense['message']|escape:'htmlall':'UTF-8'}</p>
        {if $paymentsense['gateway_message']}
        <p>Payment gateway message: {$paymentsense['gateway_message']|escape:'htmlall':'UTF-8'}</p>
        {/if}
        {if $paymentsense['debug_info']}
        <h4 id="paymentsense_debug_title">{l s='Debug information:' mod='paymentsense'}</h4>
        <pre>{$paymentsense['debug_info'] nofilter}</pre>
        {/if}
    </div>
{/block}
