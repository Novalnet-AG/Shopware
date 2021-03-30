{namespace name='frontend/novalnet/payment'}
{include file='frontend/plugins/payment/novalnetlogo.tpl'}
<div class="space"></div>
<div class="debit" >
   <input type="hidden" name="novalnetinvoiceShopVersion" id = "novalnetinvoiceShopVersion" value="{$shopVersion}"/>
   {assign var="is_form" value="0"}
   {assign var="addGtTel" value="0"}
   {assign var="pinenable" value="0"}
   <p class="none">
      {if $novalnetinvoicesPaymentPinNumber}
      {if $novalnetinvoicesPaymentPinMaxEntry}
      {assign var="is_form" value="1"}
      {else}
      {assign var="pinenable" value="1"}
      <input type="hidden" name="pinenable" id = "pinenable" value="{$pinenable}"/>
      <label style="width:50%;" for="pinNumberINVOICE"> <b>{s namespace='frontend/novalnet/payment' name='payment_novalnet_pinnumber'}PIN zu Ihrer Transaktion<sup style='color:red;'>*</sup>:{/s}</b> </label><br/>
      <input type="text" name="pinNumberINVOICE" id = "pinNumberINVOICE"  class="text" autocomplete="off" style="width:{if $shopVersion gte '5.0.0'}70%;{else}45%{/if}" /><br/><br/>
      <input type="checkbox" name="newPinINVOICE" id = "newPinINVOICE" value="1" class=" "> <b>{s namespace='frontend/novalnet/payment' name='payment_novalnet_newpin'}PIN vergessen?{/s}</b>
      <input type="hidden" id="novalnetinvoice_pinbycallback_pin_error"  name="novalnetinvoice_pinbycallback_pin_error" value="{$novalnetinvoice_pinbycallback_pin_error}"/>
      <input type="hidden" id="novalnetinvoice_pinbycallback_wrongpin_error"  name="novalnetinvoice_pinbycallback_wrongpin_error" value="{$novalnetinvoice_pinbycallback_wrongpin_error}"/>
      {/if}
      {elseif $novalnetinvoicesPaymentPinTIDNumber}
      {assign var="pinenable" value="1"}
      <input type="hidden" name="pinenable" id = "pinenable" value="{$pinenable}"/>
      {else}
      {assign var="is_form" value="1"}
      {/if}
      <input type="hidden" id="novalnetinvoice_pinbycallback_telephone_mobileno_error"  name="novalnetinvoice_pinbycallback_telephone_mobileno_error" value="{$novalnetinvoice_pinbycallback_telephone_mobileno_error}"/>
      <input type="hidden" id="nn_invoice_paymentid"  name="nn_invoice_paymentid" value="{$payment_mean.id}"/>
      <input type="hidden" id="nn_invoice_date_error"  name="nn_invoice_date_error" value="{$payment_mean.id}"/>
   </p>
   {if $is_form eq 1}
   <p class="none">
      {if $novalnetinvoicePinSms eq 'pin' || $novalnetinvoicePinSms eq 'sms'}
      {assign var="addGtTel" value="1"}
   <p class="none">
      <input type="hidden" name="addGtTel" id = "addGtTel" value="{$addGtTel}"/>
      <label style="width:50%;" for="nninvoice_telemobphone">{if $novalnetinvoicePinSms eq 'pin'}{s namespace='frontend/novalnet/payment' name='payment_novalnet_telephone'}Telefonnummer<sup style='color:red;'>*</sup>:{/s}{else}{s namespace='frontend/novalnet/payment' name='payment_novalnet_mobile_num'}Mobiltelefonnummer<sup style='color:red'>*</sup> :{/s}{/if}</label><br/>
      <input type="text" name="nninvoice_telemobphone" id="nninvoice_telemobphone" class="text" value="{$nn_customer_data.tel}"  autocomplete="off" style="width:{if $shopVersion gte '5.0.0'}70%;{else}45%{/if}" />
   </p>
   {/if}
   {if $nnConfigArray.novalnetinvoice_guarantee_payment && $date_birth_field && $company_value eq ''}
   <input type="hidden" id="date_birth_field"  name="date_birth_field" value="{$date_birth_field}"/>
   <input type="hidden" id="language"  name="language" value="{$language}"/>
   <label style="width:50%;">{s namespace='frontend/novalnet/payment' name='frontend_date_of_birth'}Ihr Geburtsdatum:{/s}<sup style='color:red;'>*</sup>:</label>
   <div class="register--birthday field--select">
      <div class="select-field">
         <select class="invoiceDateOfBirthDay" id="invoiceDateOfBirthDay" name="invoiceDateOfBirthDay">
            <option value="" >{s namespace='frontend/novalnet/payment' name='novalnet_guarantee_payment_date'}Tag{/s}</option>
            {for $day = 1 to 31}
            <option value="{$day}" {if $day == $birthdate_val.day}selected{/if}>{$day}</option>
            {/for}
         </select>
      </div>
      <div class="select-field">
         <select class="invoiceDateOfBirthMonth" name="invoiceDateOfBirthMonth" id="invoiceDateOfBirthMonth">
            <option value="" >{s namespace='frontend/novalnet/payment' name='novalnet_guarantee_payment_month'}Monat{/s}</option>
            {for $month = 1 to 12}
            <option value="{$month}" {if $month == $birthdate_val.month}selected{/if}>{$month}</option>
            {/for}
         </select>
      </div>
      <div class="select-field">
         <select class="invoiceDateOfBirthYear" name="invoiceDateOfBirthYear" id="invoiceDateOfBirthYear">
            <option value="" >{s namespace='frontend/novalnet/payment' name='novalnet_guarantee_payment_year'}Jahr{/s}</option>
            {for $year = date("Y") to date("Y")-120 step=-1}
            <option value="{$year}" {if $year == $birthdate_val.year}selected{/if}>{$year}</option>
            {/for}
         </select>
      </div>
   </div>
   {/if}
   {/if}
   <script type="text/javascript">
      if(typeof(jQuery) == 'undefined') {
          ﻿document.write('<scr'+'ipt src="{link file='frontend/_resources/js/jquery-2.1.4.min.js'}"></sc'+'ript>');
      }
   </script>
   <script src="{link file='frontend/_resources/js/novalnetinvoice.js'}"></script>
</div>
