{block name="frontend_account_index_payment_method_content" append}
    <div class="panel--body is--wide" style="height:160px;">
        {if $novalnetcc_account_details.cc_no neq '' && $novalnetcc_account_details.curr_payment_name eq 'novalnetcc'}
        <div id="novalnetcc_ref_details" style="display:{$novalnetcc_ref_details_display}">
            <p class="none">
                <label  style="width:50%;">{s namespace='frontend/novalnet/payment' name='novalnetcc_card_holder'}Name des Karteninhabers{/s}:{$novalnetcc_account_details.cc_holder}</label>
            </p>
            <p class="none">
                <label  style="width:50%;">{s namespace='frontend/novalnet/payment' name='novalnetcc_card_number'}Kreditkartennummer{/s}:{$novalnetcc_account_details.cc_no}</label>
            </p>
        </div>
        {elseif $maskdetails.maskccno neq ''}
        <div id="novalnetcc_ref_details" >
            <p class="none">
                <label  style="width:50%;">{s namespace='frontend/novalnet/payment' name='novalnetcc_card_holder'}Name des Karteninhabers{/s}: {$maskdetails.maskccholder}</label>
            </p>
            <p class="none">
                <label  style="width:50%;">{s namespace='frontend/novalnet/payment' name='novalnetcc_card_number'}Kreditkartennummer{/s}: {$maskdetails.maskccno}</label>
            </p>
        </div>
        {/if}
        {if $novalnetsepa_account_details.iban neq '' && $novalnetsepa_account_details.curr_payment_name eq 'novalnetsepa'}
            <div id="novalnetsepa_ref_details" style="display:{$novalnetsepa_ref_details_display}">
                <p class="none">
                    <label style="width:50%;">{s namespace='frontend/novalnet/payment' name='frontend_novalnet_sepa_account_holder'}Kontoinhaber{/s}:{$novalnetsepa_account_details.bankaccount_holder}</label>
                </p>
                <p class="none">
                    <label style="width:50%;">{s namespace='frontend/novalnet/payment' name='frontend_novalnet_sepa_iban'}IBAN{/s}:{$novalnetsepa_account_details.iban}</label>
                </p>
            </div>
        {elseif $maskdetails.masksepaiban neq ''}
        <div id="novalnetsepa_ref_details" >
            <p class="none">
                <label style="width:50%;">{s namespace='frontend/novalnet/payment' name='frontend_novalnet_sepa_account_holder'}Kontoinhaber{/s}: {$maskdetails.masksepaholder}</label>
            </p>
            <p class="none">
                <label style="width:50%;">{s namespace='frontend/novalnet/payment' name='frontend_novalnet_sepa_iban'}IBAN{/s}: {$maskdetails.masksepaiban}</label>
            </p>
        </div>
        {/if}
    </div>
{/block}
