{*
* Prestashop Paymentsense Re-Directed Payment Module
* Copyright (C) 2020 Paymentsense.
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
*  @copyright  2020 Paymentsense
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}

{if $notice_text != ""}
    <div class="alert alert-{$notice_type|escape:'htmlall':'UTF-8'}">
        <p>{l s="{$notice_text|escape:'htmlall':'UTF-8'}" mod='paymentsense'}</p>
    </div>
{/if}
<div class="alert alert-info">
    <img src="../modules/paymentsense/img/logo.png" style="float:left; margin-right:15px;" height="60">
    <p><strong>{l s="This module allows you to accept payments via Paymentsense." mod='paymentsense'}</strong></p>
    <p>{l s="In order to process payments through your Paymentsense account please enter your gateway details below and click save." mod='paymentsense'}</p>
    <p>{l s="Please check your gateway details " mod='paymentsense'}
        <strong><a href="https://www.psdevsupport.co.uk/CheckDetails" target="_blank" class="alert-link" >{l s="here" mod='paymentsense'}</a></strong>
        {l s=" first." mod='paymentsense'}
    </p>
</div>
<form id="configuration_form" class="defaultForm form-horizontal" action="{$form_action|escape:'htmlall':'UTF-8'}" method="post">
    <input type="hidden" name="btnSubmit" value="1" />
    <div class="panel" id="fieldset_0">
        <div class="panel-heading">
            <i class="icon-edit"></i> {l s="Configuration settings" mod='paymentsense'}
        </div>
        <div class="form-wrapper">
            <div class="form-group">
                <label for="PAYMENTSENSE_GATEWAYID" class="control-label col-lg-3 required">
                    {l s="Gateway MerchantID: " mod='paymentsense'}
                </label>
                <div class="col-lg-9">
                    <input name="PAYMENTSENSE_GATEWAYID" id="PAYMENTSENSE_GATEWAYID" type="text" required="required"
                           value="{$form_var_gateway_id|escape:'htmlall':'UTF-8'}" />
                    <p class="help-block">
                        {l s="Contains Letters and Numbers separated by a hyphen, e.g. ABCDEF-1234567." mod='paymentsense'}
                    </p>
                </div>
            </div>
            <div class="form-group">
                <label for="PAYMENTSENSE_GATEWAYPASS" class="control-label col-lg-3 required">
                    {l s="Gateway Password: " mod='paymentsense'}
                </label>
                <div class="col-lg-9">
                    <input name="PAYMENTSENSE_GATEWAYPASS" id="PAYMENTSENSE_GATEWAYPASS" type="text" required="required"
                           value="{$form_var_gateway_pass|escape:'htmlall':'UTF-8'}" />
                    <p class="help-block">
                        {l s="Contains only Letters and Numbers. NO Special Characters." mod='paymentsense'}
                    </p>
                </div>
            </div>
            <div class="form-group">
                <label for="PAYMENTSENSE_PSK" class="control-label col-lg-3 required">
                    {l s="Pre-Shared Key: " mod='paymentsense'}
                </label>
                <div class="col-lg-9">
                    <input name="PAYMENTSENSE_PSK" id="PAYMENTSENSE_PSK" type="text" required="required"
                           value="{$form_var_gateway_psk|escape:'htmlall':'UTF-8'}" />
                    <p class="help-block">
                        Found in the Merchant Management System under "Account Admin" > "Account Settings".
                    </p>
                </div>
            </div>
            <div class="form-group">
                <label for="PAYMENTSENSE_TRANSACTION_TYPE" class="control-label col-lg-3 required">
                    {l s="Transaction Type: " mod='paymentsense'}
                </label>
                <div class="col-lg-9">
                    <select name="PAYMENTSENSE_TRANSACTION_TYPE" id="PAYMENTSENSE_TRANSACTION_TYPE">
                        <option value="SALE"{if $form_var_trx_type == "SALE"} selected="selected"{/if}>{l s="SALE" mod='paymentsense'}</option>
                        <option value="PREAUTH"{if $form_var_trx_type == "PREAUTH"} selected="selected"{/if}>{l s="PREAUTH" mod='paymentsense'}</option>
                    </select>
                    <p class="help-block">
                        {l s="SALE - Authorises and Collects the transaction. PREAUTH - Only Authorises the transaction." mod='paymentsense'}
                    </p>
                </div>
            </div>
            <div class="form-group">
                <label for="PAYMENTSENSE_DEBUG" class="control-label col-lg-3">
                    {l s="Debug Mode: " mod='paymentsense'}
                </label>
                <div class="col-lg-9">
                    <select name="PAYMENTSENSE_DEBUG" id="PAYMENTSENSE_DEBUG">
                        <option value="False"{if $form_var_debug == "False"} selected="selected"{/if}>{l s="Off" mod='paymentsense'}</option>
                        <option value="True"{if $form_var_debug == "True"} selected="selected"{/if}>{l s="On" mod='paymentsense'}</option>
                    </select>
                    <p class="help-block">
                        {l s="Outputs debugging information. Only turn on for testing. Do NOT use for live accounts." mod='paymentsense'}
                    </p>
                </div>
            </div>
        </div>
        <div class="panel-footer">
            <button type="submit" value="1"	id="configuration_form_submit_btn" name="btnSubmit" class="btn btn-default pull-right">
                <i class="process-icon-save"></i>{l s="Save" mod='paymentsense'}
            </button>
        </div>
    </div>
</form>
