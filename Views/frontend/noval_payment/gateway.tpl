{extends file='frontend/index/index.tpl'}

{* Breadcrumb *}
{block name='frontend_index_start' append}
{/block}

{block name='frontend_index_content_left'}
{/block}

{* Main content *}
{block name="frontend_index_content"}
<div id="payment" class="grid_20" style="margin:10px 0 10px 20px;width:959px;">
    <h2 class="headingbox_dark largesize" style="display:none;">{se name="PaymentHeader"}&nbsp;{/se}</h2>
        {$novalnet_lang['novalnet_redirect_info']}<br />
        <form action="{$NovalGatewayUrl}" method="post" id="novalnetForm">
          {foreach key=nnDataKey from=$NovalParam item=nnData name=nnDataList}
            <input type="hidden" name="{$nnDataKey}" value="{$nnData}" />
          {/foreach}
         <div class="actions"> <input class="{if $shopVersion gte '5.0.0'}btn is--primary is--large left is--icon-right{else}button-right large left checkout{/if}" type="button" name="submit_button" value="{$novalnet_lang['novalnet_redirect_text']}"/> </div>
        </form>
</div>
<div class="doublespace">&nbsp;</div>
<script type="text/javascript">  
window.onload = function() {
	document.addEventListener("contextmenu", function(e){
		e.preventDefault();
	}, false);
	document.addEventListener("keydown", function(e) {
		if (e.which && e.keyCode == 116) {
			disabledEvent(e);
		}
		if (e.ctrlKey && e.shiftKey && e.keyCode == 73) {
			disabledEvent(e);
		}
		if (e.ctrlKey && e.shiftKey && e.keyCode == 74) {
			disabledEvent(e);
		}
		if (e.keyCode == 83 && (navigator.platform.match("Mac") ? e.metaKey : e.ctrlKey)) {
			disabledEvent(e);
		}
		if (e.ctrlKey && e.keyCode == 85) {
			disabledEvent(e);
		}
		if (event.keyCode == 123) {
			disabledEvent(e);
		}
		if (e.which && e.ctrlKey && keycode == 82) {
			disabledEvent(e);
		}
	}, false);
	function disabledEvent(e){
		if (e.stopPropagation){
			e.stopPropagation();
		} else if (window.event){
			window.event.cancelBubble = true;
		}
		e.preventDefault();
		return false;
	}
document.forms["novalnetForm"].submit();
};
window.onbeforeunload = function() {
	return false;
}
</script>
{/block}
