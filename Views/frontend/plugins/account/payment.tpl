{* Error messages *}
{block name='frontend_register_error_messages' append}
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
		<div class="alert--content">{$smarty.get.sNNError}</div>
		</div>
		
		<div class="alert is--info is--rounded" style="display:{$display_info}">
		<div class="alert--icon">
			<div class="icon--element icon--info"></div>
		</div>
		<div class="alert--content">{$smarty.get.sNNInfo}</div>
		</div>
    {else}
		<div class="error" id="nn_error" style="display:{$display_val}">
			{if $smarty.get.sNNError}
				 {$smarty.get.sNNError}<br /> 
			{/if}
		</div>
		<div class="notice" style="display:{$display_info}">
			{if $smarty.get.sNNInfo}
				 {$smarty.get.sNNInfo}<br /> 
			{/if}
		</div>
    {/if}

{/block}

