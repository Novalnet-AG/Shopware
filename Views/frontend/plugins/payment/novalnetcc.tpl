{namespace name='frontend/novalnet/payment'}
{include file='frontend/plugins/payment/novalnetlogo.tpl'}
<div class="space"></div>
<div class="debit">
<input type="hidden" name="novalnetccShopVersion" id = "novalnetccShopVersion" value="{$shopVersion}"/>
<noscript>
<span style="color:red">{s namespace='frontend/novalnet/payment' name='novalnet_no_script_enabled'}Aktivieren Sie bitte JavaScript in Ihrem Browser, um die Zahlung fortzusetzen.{/s} </span>
</noscript>
{assign var="is_firstcall" value="0"}
{assign var="addGtTel" value="0"}
{assign var="pinenable" value="0"}

{assign var="nn_cc_new_acc_details" value="1"}
{assign var="novalnetcc_ref_details_display" value="none"}
    {if $novalnetcc_account_details.cc_no neq ''}
        <p class="none" id="novalnetcc_new_acc" style="color: blue; cursor:pointer;">
            <u>
                <b>
                    {s namespace='frontend/novalnet/payment' name='novalnetcc_new_account'}Neue Kartendaten eingeben{/s}
                </b>
            </u>
        </p>
        {assign var="nn_cc_new_acc_details" value="0"}
        {assign var="novalnetcc_ref_details_display" value="block"}
        {assign var="novalnetcc_iframe_display" value="none"}
    {/if}
    <iframe id = "nnIframe" onload="loadCreditcardIframe()" src ="{$iframeurl}" frameborder="0" style="display:{$novalnetcc_iframe_display}" width="60%"></iframe><br>
    {if $nnConfigArray.novalnetcc_shopping_type eq 'one' && !$nnConfigArray.novalnetcc_cc3d && !$nnConfigArray.novalnetcc_force_cc3d && $controller != 'AboCommerce'}
		<label style="display:{$novalnetcc_iframe_display}" id="nn_cc_confirm_save_check"><input type="checkbox" name="confirm_save_check" value="1"> {s namespace='frontend/novalnet/payment' name='frontend_novalnetcc_save_card'} Meine Kartendaten für zukünftige Bestellungen speichern {/s} </label>
    {/if}
    <div id="novalnetcc_ref_details" style="display:{$novalnetcc_ref_details_display}">
    <p class="none">
        <label  style="width:50%;">{s namespace='frontend/novalnet/payment' name='novalnetcc_card_holder'}Name des Karteninhabers{/s}</label>
        {if $shopVersion gte '5.0.0'}<br />{/if}<input type="text" style="width:{if $shopVersion gte '5.0.0'}70%;{else}45%{/if}" value="{$novalnetcc_account_details.cc_holder}" readonly/>
    </p>
    <p class="none">
        <label  style="width:50%;">{s namespace='frontend/novalnet/payment' name='novalnetcc_card_number'}Kreditkartennummer{/s}</label>
        {if $shopVersion gte '5.0.0'}<br />{/if}<input type="text" style="width:{if $shopVersion gte '5.0.0'}70%;{else}45%{/if}" value="{$novalnetcc_account_details.cc_no}" readonly/>
    </p>
    <p class="none">
        <label  style="width:50%;">{s namespace='frontend/novalnet/payment' name='novalnetcc_card_date'}Ablaufdatum{/s}</label>
        {if $shopVersion gte '5.0.0'}<br />{/if}<input type="text" style="width:{if $shopVersion gte '5.0.0'}70%;{else}45%{/if}" value="{$novalnetcc_account_details.cc_exp_month} / {$novalnetcc_account_details.cc_exp_year}" readonly/>
    </p>
</div>
    {if $nnConfigArray.novalnetcc_cc3d || $nnConfigArray.novalnetcc_force_cc3d}
    <input type="hidden" id="cc3d"  value="1"/>
    <input type="hidden" id="cc3d_lang"  value="{s namespace='frontend/novalnet/payment' name='frontend_description_novalnet_redirect'}Der Betrag wird von Ihrer Kreditkarte abgebucht, sobald die Bestellung abgeschickt wird.<br>Bitte schließen Sie den Browser nach der erfolgreichen Zahlung nicht, bis Sie zum Shop zurückgeleitet wurden.{/s}"/>
    {/if}
    <input type="hidden" id="novalnetcc_given_account"  name="novalnetcc_given_account" value="{s namespace='frontend/novalnet/payment' name='novalnetcc_given_account'}Eingegebene Kartendaten{/s}"/>
    <input type="hidden" id="novalnetcc_new_account"  name="novalnetcc_new_account" value="{s namespace='frontend/novalnet/payment' name='novalnetcc_new_account'}Neue Kartendaten eingeben{/s}"/>
    <input type="hidden" id="nn_cc_new_acc_details"  name="nn_cc_new_acc_details" value="{$nn_cc_new_acc_details}"/>
    <input type="hidden" id="nn_cc_paymentid"  name="nn_cc_paymentid" value="{$payment_mean.id}"/>
    
    <input type="hidden" id="CreditcardHolderLabel"  value="{$nnConfigArray.novalnetcc_holder_label}"/>
    <input type="hidden" id="CreditcardHolderInput"  value="{$nnConfigArray.novalnetcc_holder_field}"/>
    <input type="hidden" id="CreditcardNumberLabel"  value="{$nnConfigArray.novalnetcc_card_number_label}"/>
    <input type="hidden" id="CreditcardNumberInput"  value="{$nnConfigArray.novalnetcc_card_number_field}"/>
    <input type="hidden" id="CreditcardExpLabel"    value="{$nnConfigArray.novalnetcc_expiry_date_label}"/>
    <input type="hidden" id="CreditcardExpInput"     value="{$nnConfigArray.novalnetcc_expiry_date_field}"/>
    <input type="hidden" id="CreditcardCVCLabel"     value="{$nnConfigArray.novalnetcc_cvc_label}"/>
    <input type="hidden" id="CreditcardCVCInput"     value="{$nnConfigArray.novalnetcc_cvc_field}"/>
    <input type="hidden" id="CreditcardDefaultLabel" value="{$nnConfigArray.novalnetcc_standard_label}"/>
    <input type="hidden" id="CreditcardDefaultInput" value="{$nnConfigArray.novalnetcc_standard_field}"/>
    <input type="hidden" id="CreditcardDefaultCss"  value="{$nnConfigArray.novalnetcc_standard_text}"/>
    <input type="hidden" id="CreditcardHolderLabelLang" value="{s namespace='frontend/novalnet/payment' name='novalnetcc_card_holder'}Card holder name{/s}"/>
    <input type="hidden" id="CreditcardHolderInputLang"  value="{s namespace='frontend/novalnet/payment' name='novalnetcc_card_holder_input'}Name on card{/s}"/>
    <input type="hidden" id="CreditcardNumberLabelLang" value="{s namespace='frontend/novalnet/payment' name='novalnetcc_card_number'}Card number{/s}"/>
    <input type="hidden" id="CreditcardNumberInputLang" value="{s namespace='frontend/novalnet/payment' name='novalnetcc_card_number_input'}XXXX XXXX XXXX XXXX{/s}"/>
    <input type="hidden" id="CreditcardExpLabelLang" value="{s namespace='frontend/novalnet/payment' name='novalnetcc_card_date'}Expiry date{/s}"/>
    <input type="hidden" id="CreditcardExpInputLang" value="{s namespace='frontend/novalnet/payment' name='novalnetcc_card_date_input'}MM / YYYY{/s}"/>
    <input type="hidden" id="CreditcardCVCLabelLang"    value="{s namespace='frontend/novalnet/payment' name='novalnetcc_cvc'}CVC/CVV/CID{/s}"/>
    <input type="hidden" id="CreditcardCVCInputLang"  value="{s namespace='frontend/novalnet/payment' name='novalnetcc_cvc_input'}XXX{/s}"/>
    <input type="hidden" id="CreditcardCVCHintLang" value="{s namespace='frontend/novalnet/payment' name='novalnetcc_cvc_hint'}what is this?{/s}"/>
    <input type="hidden" id="CreditcardCCErrorLang" value="{s namespace='frontend/novalnet/payment' name='novalnetcc_error'}Your credit card details are invalid{/s}"/>
    <input type="hidden" id="novalnet_cc_hash"       name="novalnet_cc_hash"      value=""/>
    <input type="hidden" id="novalnet_cc_uniqueid"   name="novalnet_cc_uniqueid"  value=""/>
    <input type="hidden" id="novalnet_cc_mask_no"    name="novalnet_cc_mask_no"  value=""/>
    <input type="hidden" id="novalnet_cc_mask_type"  name="novalnet_cc_mask_type"  value=""/>
    <input type="hidden" id="novalnet_cc_mask_holder" name="novalnet_cc_mask_holder"  value=""/>
    <input type="hidden" id="novalnet_cc_mask_month" name="novalnet_cc_mask_month"  value=""/>
    <input type="hidden" id="novalnet_cc_mask_year" name="novalnet_cc_mask_year"  value=""/>
    
<style>
@media only screen and (max-width: 600px) {
  iframe {
    width: 100%;
  }
}
</style>
<script type="text/javascript">

if(typeof(jQuery) == 'undefined') {
    ﻿document.write('<scr'+'ipt src="{link file='frontend/_resources/js/jquery-2.1.4.min.js'}"></sc'+'ript>');
}
</script>
<script src="{link file='frontend/_resources/js/novalnetcc.js'}"></script>
</div>
