{namespace name='frontend/novalnet/payment'}
{include file='frontend/plugins/payment/novalnetlogo.tpl'}
<div class="space"></div>
<div class="debit">
<input type="hidden" name="novalnetpaypalShopVersion" id = "novalnetpaypalShopVersion" value="{$shopVersion}"/>
<noscript>
<span style="color:red">Aktivieren Sie bitte JavaScript in Ihrem Browser, um die Zahlung fortzusetzen. </span>
</noscript>
{assign var="nn_paypal_new_acc_details" value="1"}
{assign var="novalnetpaypal_ref_details_display" value="none"}
    {if $novalnetpaypal_account_details.tid neq '' }
        <p class="none" id="novalnetpaypal_new_acc" style="color: blue; cursor: pointer;">
            <u>
                <b>
                    {s namespace='frontend/novalnet/payment' name='novalnetpaypal_new_account'}Neue Kontodaten eingeben{/s}
                </b>
            </u>
        </p>
        {assign var="nn_paypal_new_acc_details" value="0"}
        {assign var="novalnetpaypal_ref_details_display" value="block"}
        {assign var="novalnetpaypal_confirm_check_display" value="none"}
        <input type="hidden" id="paypalRef"  value="1"/>
        <input type="hidden" id="paypalref_lang"  value="{s namespace='frontend/novalnet/payment' name='frontend_description_novalnetpaypal_ref'}Sobald die Bestellung abgeschickt wurde, wird die Zahlung bei Novalnet als Referenztransaktion verarbeitet.{/s}"/>
    {/if}
{if $nnConfigArray.novalnetpaypal_shopping_type eq 'one'}
	<label id="nn_paypal_confirm_save_check" style="display:{$novalnetpaypal_confirm_check_display}"><input type="checkbox" name="confirm_save_check" value="1"> {s namespace='frontend/novalnet/payment' name='frontend_novalnetpaypal_save_card'} Meine PayPal-Daten für zukünftige Bestellungen speichern {/s} </label>
{/if}
<div id="novalnetpaypal_ref_details" style="display:{$novalnetpaypal_ref_details_display}">
    <p class="none">
        <label  style="width:50%;">{s namespace='frontend/novalnet/payment' name='frontend_novalnet_paypal_reference_tid'}Novalnet transaction ID{/s}</label>
        {if $shopVersion gte '5.0.0'}<br />{/if}<input type="text" style="width:{if $shopVersion gte '5.0.0'}70%;{else}45%{/if}" value="{$novalnetpaypal_account_details.tid}" readonly/>
    </p>
    {if $novalnetpaypal_account_details.paypal_transaction_id neq ''}
    <p class="none">
          <label  style="width:50%;">{s namespace='frontend/novalnet/payment' name='frontend_novalnet_paypal_transaction_id'}PayPal transaction ID{/s}</label>
          {if $shopVersion gte '5.0.0'}<br />{/if}<input type="text" style="width:{if $shopVersion gte '5.0.0'}70%;{else}45%{/if}" value="{$novalnetpaypal_account_details.paypal_transaction_id}" readonly/>
    </p>
    {/if}
</div>
    <input type="hidden" id="novalnetpaypal_given_account"  name="novalnetpaypal_given_account" value="{s namespace='frontend/novalnet/payment' name='novalnetpaypal_given_account'}Given PayPal account details{/s}"/>
    <input type="hidden" id="novalnetpaypal_new_account"  name="novalnetpaypal_new_account" value="{s namespace='frontend/novalnet/payment' name='novalnetpaypal_new_account'}Proceed with new PayPal account details{/s}"/>
    <input type="hidden" id="nn_paypal_new_acc_details"  name="nn_paypal_new_acc_details" value="{$nn_paypal_new_acc_details}"/>
    <input type="hidden" id="nn_paypal_new_acc_form" name="nn_paypal_new_acc_form" value="{$nn_paypal_new_acc_form}"/>
    <input type="hidden" id="nn_paypal_paymentid"  name="nn_paypal_paymentid" value="{$payment_mean.id}"/>
    <input type="hidden" id="paypalref_lang_before"  value="{s namespace='frontend/novalnet/payment' name='frontend_description_novalnet_redirect'}Nach der erfolgreichen Überprüfung werden Sie auf die abgesicherte Novalnet-Bestellseite umgeleitet, um die Zahlung fortzusetzen.<br>Bitte schließen Sie den Browser nach der erfolgreichen Zahlung nicht, bis Sie zum Shop zurückgeleitet wurden.{/s}"/>
<script type="text/javascript">
if(typeof(jQuery) == 'undefined') {
    ﻿document.write('<scr'+'ipt src="{link file='frontend/_resources/js/jquery-2.1.4.min.js'}"></sc'+'ript>');
}
</script>
<script src="{link file='frontend/_resources/js/novalnetpaypal.js'}"></script>
</div>
