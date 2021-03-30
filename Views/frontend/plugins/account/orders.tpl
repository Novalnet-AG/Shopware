{* Breadcrumb *}
{block name="frontend_account_orders_table_head" prepend}
    {if $shopVersion lt '5.0.0'}
        {if $smarty.get.sNNError}
            {if $smarty.get.sNNStatus == 'failure'}
            <script>
            jQuery(document).ready(function(){
                    jQuery('div[id="order{$smarty.get.sOrderNo}"]').css('display','block');
                    jQuery('div[id="subscription_novalnet_order{$smarty.get.sOrderNo}"]').css('display','block');
            });
            </script>
            {elseif $smarty.get.sNNStatus == 'success'}
            <script>
                jQuery(document).ready(function(){
                        jQuery('div[id="order{$smarty.get.sOrderNo}"]').attr('class','displaynone active');
                        jQuery('div[id="order{$smarty.get.sOrderNo}"]').css('display','block');
                        jQuery('div[id="subscription_novalnet_order{$smarty.get.sOrderNo}"]').css('display','none');
                });
            </script>
            {/if}
        <fieldset>
            <div class="{if $smarty.get.sNNStatus == 'success'}success{else}error{/if}">{$smarty.get.sNNError}</div>
            <input type="hidden" value="{$nnOrderId}">
        </fieldset>
        {/if}
    {/if}
{/block}
{block name="frontend_account_orders_welcome" prepend}
    {if $shopVersion gte '5.0.0'}
        <script type="text/javascript">
        if(typeof(jQuery) == 'undefined') {
            ﻿   document.write('<scr'+'ipt src="{link file='frontend/_resources/js/jquery-2.1.4.min.js'}"></sc'+'ript>');
        }
        </script>
        {if $smarty.get.sNNError}
                {assign var="display_val" value="block"}
                {if $smarty.get.sNNStatus == 'failure'}
                <script>
                jQuery(document).ready(function(){
                        jQuery('div[id="order{$smarty.get.sOrderNo}"]').css('display','block');
                        jQuery('div[id="subscription_novalnet_order{$smarty.get.sOrderNo}"]').css('display','block');
                });
                </script>
                {elseif $smarty.get.sNNStatus == 'success'}
                <script>
                    jQuery(document).ready(function(){
                            jQuery('div[id="order{$smarty.get.sOrderNo}"]').attr('class','displaynone active');
                            jQuery('div[id="order{$smarty.get.sOrderNo}"]').css('display','block');
                            jQuery('div[id="subscription_novalnet_order{$smarty.get.sOrderNo}"]').css('display','none');
                    });
                </script>
                {/if}

        {else}
                {assign var="display_val" value="none"}
        {/if}
        <div class="{if $smarty.get.sNNStatus == 'success'}alert is--success is--rounded{else}alert is--error is--rounded{/if}" style="display:{$display_val}">
        <div class="alert--icon">
        <i class="{if $smarty.get.sNNStatus == 'success'}icon--element icon--check{else}icon--element icon--cross{/if}"></i>
        </div>
        <div class="alert--content">{$smarty.get.sNNError}</div>
        </div>
    {/if}
{/block}
