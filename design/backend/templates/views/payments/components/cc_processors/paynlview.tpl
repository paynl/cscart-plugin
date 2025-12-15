{assign var="credential" value=''|fn_getCredential }
{assign var="paymentMethods" value=''|fn_getpaymentMethods }
{assign var="multiCore" value=''|fn_paynl_getMultiCore }
<h3>PAY.</h3>
<p/>

{*Token Code*}
<div class="form-field">
    <label  for="token_code">Token Code:</label>
    <div class="control-group">
        <input onchange="getPaymentProfiles();" type="text" name="payment_data[processor_params][token_code]" id="token_code" value="{if (isset($processor_params['token_code']))}{$processor_params['token_code']}{else}{$credential['token_code']}{/if}"  size="12">
    </div>
</div>

{*Service Id*}
<div class="form-field">
    <label  for="service_id">Service ID:</label>
    <div class="control-group">
        <input onchange="getPaymentProfiles();" type="text" name="payment_data[processor_params][service_id]" id="service_id" value="{if (isset($processor_params['service_id']))}{$processor_params['service_id']}{else}{$credential['service_id']}{/if}"  size="12">
    </div>
</div>

{*Token api*}
<div class="form-field">
    <label  for="token_api">API Token:</label>
    <div class="control-group">
        <input onchange="getPaymentProfiles();" type="text" name="payment_data[processor_params][token_api]" id="token_api" value="{if  (isset($processor_params['token_api']))}{$processor_params['token_api']}{else}{$credential['token_api']}{/if}"  size="40">
    </div>
</div>

{* Options *}
<div class="form-field">
    <label for="payNL_option">Payment Method:</label>
    <div class="control-group">
        <select name="payment_data[processor_params][optionId]" id="payNL_option">
            <option value="">Select payment method...</option>
            {if is_array($paymentMethods) && !empty($paymentMethods)}
                {foreach from=$paymentMethods item="method"}
                    <option value="{$method.id}" {if isset($processor_params.optionId) && $processor_params.optionId == $method.id}selected="selected"{/if}>{$method.name}</option>
                {/foreach}
            {/if}
        </select>
    </div>
</div>

{* Multicore *}
<div class="form-field">
    <label for="payNL_multicore">Multicore:</label>
    <div class="control-group">
        <select name="payment_data[processor_params][multicore]" id="payNL_multicore">
            <option value="">Select multicore...</option>
            {if is_array($multiCore) && !empty($multiCore)}
                {foreach from=$multiCore item="core"}
                    <option value="{$core.domain}" {if $selected == $core.domain}selected="selected"{/if}>{$core.name}</option>
                {/foreach}
            {/if}
        </select>
    </div>
</div>

{assign var="statuses" value=$smarty.const.STATUSES_ORDER|fn_get_simple_statuses}

<div class="form-field">
        <label for="payNL_cancel">CANCEL:</label>
        <select name="payment_data[processor_params][statuses][cancel]" id="payNL_cancel">
          {foreach from=$statuses item="s" key="k"}
            <option value="{$k}" {if (isset($processor_params.statuses.cancel) && $processor_params.statuses.cancel == $k) || (!isset($processor_params.statuses.cancel) && $k == 'I')}selected="selected"{/if}>{$s}</option>
          {/foreach}
        </select>
</div>

<div class="form-field">
        <label for="payNL_paid">PAID:</label>
        <select name="payment_data[processor_params][statuses][paid]" id="payNL_paid">
                {foreach from=$statuses item="s" key="k"}
                <option value="{$k}" {if (isset($processor_params.statuses.paid) && $processor_params.statuses.paid == $k) || (!isset($processor_params.statuses.paid) && $k == 'P')}selected="selected"{/if}>{$s}</option>
                {/foreach}
        </select>

</div>

<div class="form-field">
        <label  for="payNL_pending">PENDING:</label>
        <select name="payment_data[processor_params][statuses][pending]" id="payNL_pending">
          {foreach from=$statuses item="s" key="k"}
            <option value="{$k}" {if (isset($processor_params.statuses.pending) && $processor_params.statuses.pending == $k) || (!isset($processor_params.statuses.pending) && $k == 'O')}selected="selected"{/if}>{$s}</option>
          {/foreach}
        </select>
</div>

<div class="form-field">
    <label for="payNL_authorize">AUTHORIZE:</label>
    <select name="payment_data[processor_params][statuses][authorize]" id="payNL_authorize">
        {foreach from=$statuses item="s" key="k"}
            <option value="{$k}"
                    {if (isset($processor_params.statuses.authorize) && $processor_params.statuses.authorize == $k) || (!isset($processor_params.statuses.authorize) && $k == 'P')}selected="selected"{/if}>{$s}</option>
        {/foreach}
    </select>
</div>

<div class="form-field">
        <label  for="payNL_checkamount">CHECKAMOUNT:</label>
        <select name="payment_data[processor_params][statuses][checkamount]" id="payNL_checkamount">
                {foreach from=$statuses item="s" key="k"}
                <option value="{$k}" {if (isset($processor_params.statuses.checkamount) && $processor_params.statuses.checkamount == $k) || (!isset($processor_params.statuses.checkamount) && $k == 'I')}selected="selected"{/if}>{$s}</option>
                {/foreach}
        </select>
</div>

      
        
        