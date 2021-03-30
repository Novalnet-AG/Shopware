{block name="frontend_account_order_item_repeat_order" append}
<script type="text/javascript">
if(typeof(jQuery) == 'undefined') {
   ﻿   document.write('<scr'+'ipt src="{link file='frontend/_resources/js/jquery-2.1.4.min.js'}"></sc'+'ript>');
}
jQuery(document).ready(function(event){
    var order_no = "{$offerPosition.ordernumber}";
    var empty_message = jQuery( "#subs_empty" ).val();
    var confirm_message = jQuery( "#subs_confirm" ).val();
    jQuery("button[value='"+order_no+"']").on("click", function(){
        var cancelval = jQuery( "#novalnet_subs_termination_reason_"+order_no ).val();
        if(cancelval == '') {
            alert(empty_message);
            return false;
        } else {
            r = confirm(confirm_message);
            if (r == true) {
                return true;
            } else {
                return false;
            } 
        }
        
    });
    jQuery('a[rel="order{$offerPosition.ordernumber}"], a[href="#order{$offerPosition.ordernumber}"]').click(function(){
        var order_no = "{$offerPosition.ordernumber}";
        jQuery.post('{url controller=NovalPayment action=isSubscriptionOrder forceSecure}',{ order_no : "{$offerPosition.ordernumber}" } ,function (data) {
            if(data == 'true'){
                jQuery('div[id="subscription_novalnet_order{$offerPosition.ordernumber}"]').css('display','block');
            }
        });
    });
});
</script>
<div class="doublespace"> </div>
<div id="subscription_novalnet_order{$offerPosition.ordernumber}" style="display:none;" class="panel--tr">
<div class="{if $shopVersion gte '5.0.0'}panel--title{else}table_head{/if}">
<strong>{$novalnet_lang['subscription_novalnet_order_operations_subscription_head_title']}</strong>
</div>
<div class="{if $shopVersion gte '5.0.0'}panel--body is--wide{else}table_row{/if}">
<div class="grid_4">
<strong>{$novalnet_lang['subscription_novalnet_order_operations_subscription_title']}</strong>
</div>
<form method="post" action="{url controller='NovalPayment' action='subscriptionCancel'}" id="nn_form{$offerPosition.ordernumber}">
<div class="grid_4">
    <input type="hidden" name="orderid" id="orderid" value={$offerPosition.id}>
    <input type="hidden" name="ordernumber" id="ordernumber" value={$offerPosition.ordernumber}>
    <input type="hidden" name="subs_empty" id="subs_empty" value="{$subs_empty}">
    <input type="hidden" name="subs_confirm" id="subs_confirm" value="{$subs_confirm}">
    <select name='novalnet_subs_termination_reason_{$offerPosition.ordernumber}' id='novalnet_subs_termination_reason_{$offerPosition.ordernumber}' {if $shopVersion lt '5.0.0'}style="width:350px;"{/if}>
    <option value="">{$novalnet_lang['subscription_novalnet_order_operations_please_select']}</option>
    {foreach from=$subscriptionOptions item=value}
        <option value="{$value}">{$value}</option>
    {/foreach}
    </select>
</div>
<div class="grid_4 push_4 left">
    <button type="submit" form="nn_form{$offerPosition.ordernumber}" value="{$offerPosition.ordernumber}">{$novalnet_lang['novalnet_order_operations_update_button']}</button>
</div>
</form>
</div>
</div>
{/block}
