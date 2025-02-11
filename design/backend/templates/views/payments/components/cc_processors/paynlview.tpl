{assign var="credential" value=''|fn_getCredential }
<h3>PAY.</h3>
<p/>
<script type="text/javascript">
function getPaymentProfiles(){
    var serviceId = jQuery('#service_id').val();
    var apiToken = jQuery('#token_api').val();

    var selectedOption = '{$processor_params.optionId}';

    if(serviceId != '' && apiToken != ''){
        jQuery('#payNL_option').html('<option>Loading...</option>');
        jQuery.ajax({
            url: 'https://rest-api.pay.nl/v4/Transaction/getServicePaymentOptions/jsonp/?token='+apiToken+'&serviceId='+serviceId,
            dataType: 'jsonp',
            success: function(data){
                if(data.request.result == 1){
                    var options = "";
                    jQuery.each(data.paymentProfiles, function(key, profile){
                        options += "<option value='"+profile.id+"'>"+profile.name+"</option>";
                    });
                    jQuery('#payNL_option').html(options);
                    jQuery('#payNL_option').val(selectedOption);

                } else {
                    jQuery('#payNL_option').html('<option>Please check ApiToken and serviceId</option>');
                    alert('Error: '+data.request.errorMessage);
                }
            }
        });
    }
}
jQuery(document).ready(function(){
    getPaymentProfiles();

    jQuery('a:contains("Configure")').on('click', function () {
        if (jQuery('#payNL_option').val() == 10) {
            jQuery('#issuersDropdown').show();
        } else {
            jQuery('#issuersDropdown').hide();
        }
    });

    jQuery('#payNL_option').on('change', function () {
        if (jQuery(this).val() == 10) {
            jQuery('#issuersDropdown').show();
        } else {
            jQuery('#issuersDropdown').hide();
        }
    });
});
</script>

{*Service Id*}
<div class="form-field">
    <label  for="service_id">Service id:</label>
    <div class="control-group">
        <input onchange="getPaymentProfiles();" type="text" name="payment_data[processor_params][service_id]" id="service_id" value="{if (isset($processor_params['service_id']))}{$processor_params['service_id']}{else}{$credential['service_id']}{/if}"  size="12">
    </div>
</div>

{*Token api*}
<div class="form-field">
    <label  for="token_api">Token:</label>
    <div class="control-group">
        <input onchange="getPaymentProfiles();" type="text" name="payment_data[processor_params][token_api]" id="token_api" value="{if  (isset($processor_params['token_api']))}{$processor_params['token_api']}{else}{$credential['token_api']}{/if}"  size="40">
    </div>
</div>



{* Options *}
<div class="form-field">
        <label for="payNL_option">Option:</label>
        <select name="payment_data[processor_params][optionId]" id="payNL_option">

        </select>

</div>

<div class="form-field" id="issuersDropdown">
    <label for="issuersOption">Bank issuers:</label>
    <select name="payment_data[processor_params][issuersOption]" id="issuersOption">
        <option value="hide" {if (isset($processor_params.issuersOption) && $processor_params.issuersOption == 'hide')}selected="selected"{/if}>Hide</option>
        <option value="show" {if (isset($processor_params.issuersOption) && $processor_params.issuersOption == 'show')}selected="selected"{/if}>Show</option>
    </select>
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

      
        
        