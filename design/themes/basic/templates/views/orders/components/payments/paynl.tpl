{assign var="paynl_banks" value=$cart.payment_id|fn_paynl_get_checkout_banks}
{if $paynl_banks}
    <select name="paymentOptionbSubId">
        <option value="">Kies uw bank...</option>
        {foreach from=$paynl_banks item="bank"}
            <option value="{$bank.id}">{$bank.name}</option>
        {/foreach}
    </select>
    <br /><br /><br /><br />
{/if}
