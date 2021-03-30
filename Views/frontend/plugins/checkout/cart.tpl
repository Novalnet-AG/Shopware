{* Empty basket *}
            
{block name='frontend_basket_basket_is_empty'}
	{if $errormsg || $shopErrormsg}
	   <div class="basket--info-messages">
		   {if $errormsg}
			   {include file="frontend/_includes/messages.tpl" type="warning" content="$errormsg"}
		   {else}
			   {include file="frontend/_includes/messages.tpl" type="warning" content="$shopErrormsg"}
		   {/if}
	   </div>
	{else}
	   {$smarty.block.parent}
	{/if}
{/block}
