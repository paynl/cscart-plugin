{assign var="paymentMethods" value=''|fn_getpaymentMethods }
{assign var="multiCore" value=''|fn_paynl_getMultiCore }
{assign var="credentials" value=''|fn_paynl_getCredentials }
<h3>Pay.</h3>
<p/>

<script type="text/javascript">
function getPaymentProfiles(){
    var tokenCode = jQuery('#payNL_tokenCode').val();
    var serviceId = jQuery('#payNL_serviceId').val();
    var apiToken = jQuery('#payNL_apiToken').val();
    var selectedOption = '{$processor_params.optionId}';

    if (tokenCode != '' && serviceId != '' && apiToken != '') {
        jQuery('#payNL_option').html('<option>Loading...</option>');
        jQuery.ajax({
            url: '{"paynl.get_payment_methods"|fn_url}',
            dataType: 'json',
            data: {
                tokenCode: tokenCode,
                serviceId: serviceId,
                apiToken: apiToken
            },
            success: function(data){
                if (data.paymentMethods && data.paymentMethods.length) {
                    var options = '<option value="">Select payment method...</option>';
                    jQuery.each(data.paymentMethods, function(key, method){
                        options += "<option value='"+method.id+"'>"+method.name+"</option>";
                    });
                    jQuery('#payNL_option').html(options);
                    jQuery('#payNL_option').val(selectedOption);
                } else {
                    jQuery('#payNL_option').html('<option>Please check credentials</option>');
                }
            },
            error: function(){
                jQuery('#payNL_option').html('<option>Please check credentials</option>');
            }
        });
    }
}
jQuery(document).ready(function(){
    getPaymentProfiles();
});
</script>

{* Token code *}
<div class="form-field">
    <label  for="payNL_tokenCode">Token code:</label>
    <div class="control-group">
        <input onchange="getPaymentProfiles();" type="text" name="payment_data[processor_params][tokenCode]" id="payNL_tokenCode" value="{if (isset($processor_params.tokenCode))}{$processor_params.tokenCode}{else}{$credentials['tokenCode']}{/if}"  size="12">
</div>

{* Service Id *}
<div class="form-field">
    <label  for="payNL_serviceId">Service Id:</label>
    <div class="control-group">
        <input onchange="getPaymentProfiles();" type="text" name="payment_data[processor_params][serviceId]" id="payNL_serviceId" value="{if (isset($processor_params.serviceId))}{$processor_params.serviceId}{else}{$credentials['serviceId']}{/if}"  size="12">
</div>

{* API Token *}
<div class="form-field">
    <label  for="payNL_apiToken">API Token:</label>
    <div class="control-group">
        <input onchange="getPaymentProfiles();" type="text" name="payment_data[processor_params][apiToken]" id="payNL_apiToken" value="{if (isset($processor_params.apiToken))}{$processor_params.apiToken}{else}{$credentials['apiToken']}{/if}"  size="40">
</div>

{* Multicore *}
<div class="form-field">
    <label for="payNL_multicore">Multicore:</label>
    <div class="control-group">
        <select name="payment_data[processor_params][multicore]" id="payNL_multicore">
            <option value="">Select multicore...</option>
            {if is_array($multiCore) && !empty($multiCore)}
                {foreach from=$multiCore item="core"}
                    <option value="{$core.domain}" {if isset($processor_params.multicore) && $processor_params.multicore == $core.domain}selected="selected"{/if}>{$core.name}</option>
                {/foreach}
            {/if}
        </select>
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

      
        
        