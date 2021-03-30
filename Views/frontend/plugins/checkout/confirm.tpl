{block name="frontend_checkout_confirm_left_payment_method" append}
    {if $novalnetcc_account_details.cc_no neq '' && $novalnetcc_account_details.curr_payment_name eq 'novalnetcc'}
        <div id="novalnetcc_ref_details" >
            <p class="none">
                <label  style="width:50%;">{s namespace='frontend/novalnet/payment' name='novalnetcc_card_holder'}Name des Karteninhabers{/s}: {$novalnetcc_account_details.cc_holder}</label>
            </p>
            <p class="none">
                <label  style="width:50%;">{s namespace='frontend/novalnet/payment' name='novalnetcc_card_number'}Kreditkartennummer{/s}: {$novalnetcc_account_details.cc_no}</label>
            </p>
        </div>
    {elseif $maskdetails.maskccno neq ''}
        <div id="novalnetcc_ref_details" >
            <p class="none">
                <label  style="width:50%;">{s namespace='frontend/novalnet/payment' name='novalnetcc_card_holder'}Name des Karteninhabers{/s}: {$maskdetails.maskccholder}</label>
            </p>
            <p class="none">
                <label  style="width:50%;">{s namespace='frontend/novalnet/payment' name='novalnetcc_card_number'}Kreditkartennummer{/s}: {$maskdetails.maskccno}</label>
            </p>
        </div>
    {/if}
    {if $novalnetsepa_account_details.iban neq '' && $novalnetsepa_account_details.curr_payment_name eq 'novalnetsepa'}
        <div id="novalnetsepa_ref_details" >
            <p class="none">
                <label style="width:50%;">{s namespace='frontend/novalnet/payment' name='frontend_novalnet_sepa_account_holder'}Kontoinhaber{/s}: {$novalnetsepa_account_details.bankaccount_holder}</label>
            </p>
            <p class="none">
                <label style="width:50%;">{s namespace='frontend/novalnet/payment' name='frontend_novalnet_sepa_iban'}IBAN oder Kontonummer{/s}: {$novalnetsepa_account_details.iban}</label>
            </p>
        </div>
    {elseif $maskdetails.masksepaiban neq ''}
        <div id="novalnetsepa_ref_details" >
            <p class="none">
                <label style="width:50%;">{s namespace='frontend/novalnet/payment' name='frontend_novalnet_sepa_account_holder'}Kontoinhaber{/s}: {$maskdetails.masksepaholder}</label>
            </p>
            <p class="none">
                <label style="width:50%;">{s namespace='frontend/novalnet/payment' name='frontend_novalnet_sepa_iban'}IBAN oder Kontonummer{/s}: {$maskdetails.masksepaiban}</label>
            </p>
        </div>
    {/if}
    {if $novalnetpaypal_account_details.tid neq '' && $novalnetpaypal_account_details.curr_payment_name eq 'novalnetpaypal'}
        <div id="novalnetpaypal_ref_details" >
            <p class="none">
            <label  style="width:50%;">{s namespace='frontend/novalnet/payment' name='frontend_novalnet_paypal_reference_tid'}Novalnet transaction ID{/s}: {$novalnetpaypal_account_details.tid}</label>
        </p>
            {if $novalnetpaypal_account_details.paypal_transaction_id neq ''}
                <p class="none">
                  <label  style="width:50%;">{s namespace='frontend/novalnet/payment' name='frontend_novalnet_paypal_transaction_id'}PayPal transaction ID{/s}: {$novalnetpaypal_account_details.paypal_transaction_id}</label>
                </p>
            {/if}
</div>
    {/if}
{/block}

{block name="frontend_index_content" prepend}
    {if $smarty.get.sNNError}
            {assign var="display_val" value="block"}
    {else}
            {assign var="display_val" value="none"}
    {/if}
    {if $smarty.get.sNNInfo}
        {assign var="display_info" value="block"}
        {assign var="display_val" value="block"}
    {else}
        {assign var="display_info" value="none"}
    {/if}
    {if $shopVersion gte '5.0.0'}
        <div class="alert is--error is--rounded" style="display:{$display_val}">
        <div class="alert--icon">
        <i class="icon--element icon--cross"></i>
        </div>
        <div class="alert--content">{$smarty.get.sNNError}</div>
        </div>
    {else}
        <div class="clear"></div>
        <div class="error agb_confirm" style="display:{$display_val}">
        <div class="center">
            <strong>{$smarty.get.sNNError}{$smarty.get.sNNInfo}</strong>
        </div>
        </div>
    {/if}
     
        {if $shopVersion gte '5.0.0'}
        <div class="alert is--info is--rounded" style="display:{$display_info}">
        <div class="alert--icon">
        <div class="icon--element icon--info"></div>
        </div>
        <div class="alert--content">{$smarty.get.sNNInfo}</div>
        </div>
        {/if}

<script type="text/javascript">
if(typeof(jQuery) == 'undefined') {
   ﻿   document.write('<scr'+'ipt src="{link file='frontend/_resources/js/jquery-2.1.4.min.js'}"></sc'+'ript>');
}
if(typeof(jQuery) != 'undefined') {
jQuery('document').ready(function(){
    jQuery("#basketButton").closest("form").submit(function(){
        jQuery(this).find("input[id=basketButton], input[type=submit]").attr("disabled", "disabled").css("opacity", "0.1");
    });
});
}
</script>
{/block}
