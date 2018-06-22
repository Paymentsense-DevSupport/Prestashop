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

<div class="payment-method-{$paymentsense['name']}">
    <div class="payment-method-container">
        <div class="row">
            <img src="{$paymentsense['path']}img/PaymentsenseCards.png"
                 alt="{l s="Paymentsense Logo" mod="paymentsense"}"/>
        </div>
    </div>
</div>
<script type="text/javascript">
  function htmlDecode(str) {
    return String(str).replace(/&amp;/g, '&');
  }
  function addElement(sender, pName, pValue) {
    $('<input>').attr(
      {
        type: 'hidden',
        name: pName,
        value: htmlDecode(pValue)
      }
    ).appendTo(sender);
  }
  function onSubmitPaymentsense(sender) {
      {foreach from=$paymentsense['formparams'] key=pName item=pValue}
    addElement(sender,"{$pName}","{$pValue}");
      {/foreach}
    return true;
  }
</script>
