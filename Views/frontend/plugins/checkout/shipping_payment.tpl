{block name='frontend_account_payment_error_messages' append}
{if $smarty.get.sNNError}
		{assign var="display_val" value="block"}
{else}
		{assign var="display_val" value="none"}
{/if}
{if $smarty.get.sNNInfo}
		{assign var="display_info" value="block"}
{else}
		{assign var="display_info" value="none"}
{/if}
{if $shopVersion gte '5.0.0'}
<div class="alert is--error is--rounded" style="display:{$display_val}">
<div class="alert--icon">
<i class="icon--element icon--cross"></i>
</div>
<div class="alert--content" id="nn_error">{$smarty.get.sNNError}</div>
</div>
<div class="alert is--info is--rounded" style="display:{$display_info}">
<div class="alert--icon">
	<div class="icon--element icon--info"></div>
</div>
<div class="alert--content">{$smarty.get.sNNInfo}</div>
</div>
{/if}
{/block}
