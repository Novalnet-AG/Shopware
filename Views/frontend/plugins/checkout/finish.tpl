{* Transaction number *}
{if $shopVersion gte '5.0.0'}
    {block name='frontend_checkout_finish_teaser_title' append}
		{if $nnComment}
			<p class="panel--body is--align-center">
				{$nnComment}
				{if $cp_checkout_token}
					<button class="bz-checkout-btn nn-btn" id="novalnet_button" >
						{s namespace='frontend/novalnet/payment' name='novalnetcashpayment_button'}Bezahlen mit Barzahlen{/s}						
					</button>
					<style>
					  .nn-btn {
						display:inline-block !important;
						float:none !important;
						background-color: rgb(99, 169, 36);
						background-image: none;
						border-color: rgb(82, 145, 25);
						border-radius: 2px;
						border-style: solid;
						border-width: 1px;
						box-sizing: border-box;
						color: rgb(255, 255, 255);
						cursor: pointer;
						font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
						font-size: 14px;
						font-stretch: normal;
						font-style: normal;
						font-variant: normal;
						font-weight: normal;
						letter-spacing: normal;
						line-height: 20px;
						overflow: visible;
						padding: 6px 12px;
						position: relative;
						text-align: center;
						text-indent: 0px;
						text-rendering: auto;
						text-shadow: none;
						text-transform: none;
						touch-action: manipulation;
						vertical-align: middle;
						visibility: visible;
						white-space: nowrap;
						text-decoration: none;
						margin: 10px;
					  }
					</style>
				{/if}
			</p>
			{if $cp_checkout_token}
				{if $transaction_mode eq '1'}
					<script src="https://cdn.barzahlen.de/js/v2/checkout-sandbox.js" class="bz-checkout" data-token="{$cp_checkout_token}" data-auto-display="true"></script>
				{else}
					<script src="https://cdn.barzahlen.de/js/v2/checkout.js" class="bz-checkout" data-token="{$cp_checkout_token}" data-auto-display="true"></script>
				{/if}
				
				<style type="text/css">
					iframe#bz-checkout-modal {
					position: fixed !important; }
				</style>
			{/if}
		{/if}
	{/block}
{else}
    {block name='frontend_checkout_finish_transaction_number' append}
		{if $nnComment}
			<p> {$nnComment} </p>
		{/if}
    {/block}
{/if}



