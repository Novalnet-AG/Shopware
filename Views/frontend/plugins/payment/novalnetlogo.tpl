    {if $nnConfigArray["{$payment_mean.name}_payment_notification_to_buyer"]}
        <p>{$nnConfigArray["{$payment_mean.name}_payment_notification_to_buyer"]|strip_tags:true }</p>
    {/if}
    {if $nnConfigArray['novalnet_payment_logo_display']}
    <br />
    <div>
        <a title="{$novalnet_lang['payment_name_{$payment_mean.name}']}" target="_blank" style="text-decoration:none;">
            <img src={link file="frontend/_resources/images/{$payment_mean.name}.png"} alt='{$novalnet_lang["payment_name_{$payment_mean.name}"]}' title='{$novalnet_lang["payment_name_{$payment_mean.name}"]}' style="border:none;display: inline-block;"/>
        </a>
        {if $payment_mean.name == 'novalnetcc'}
            {if $nnConfigArray['novalnet_payment_logo_display']}

            <a title="{$novalnet_lang['payment_name_novalnetcc']}" target="_blank" style="text-decoration:none;">
                    <img src="{link file='frontend/_resources/images/master.png'}" alt="{$novalnet_lang['payment_name_novalnetcc']}" title='{$novalnet_lang["payment_name_{$payment_mean.name}"]}' style="border:none;display: inline-block;"/>
                </a>
            {/if}
            {if  $nnConfigArray['novalnetcc_amex_enabled']}
                <a title="{s namespace='frontend/novalnet/payment' name='payment_name_novalnetcc'}Kreditkarte{/s}" target="_blank" style="text-decoration:none;">
                    <img src="{link file='frontend/_resources/images/amex.png'}" alt="{s namespace='frontend/novalnet/payment' name='payment_name_novalnetcc'}Kreditkarte{/s}" title="{s namespace='frontend/novalnet/payment' name='payment_name_novalnetcc'}Kreditkarte{/s}" style="border:none;display: inline-block;"/>
                </a>
            {/if}
            {if $nnConfigArray['novalnetcc_maestro_enabled']}
                <a title="Visa & Mastercard" target="_blank" style="text-decoration:none;">
                    <img src="{link file='frontend/_resources/images/maestro.png'}" alt="{s namespace='frontend/novalnet/payment' name='payment_name_novalnetcc'}Kreditkarte{/s}" title="{s namespace='frontend/novalnet/payment' name='payment_name_novalnetcc'}Kreditkarte{/s}" style="display: inline-block;border:none;margin-left: 6px;"/>
                </a>
            {/if}
        {/if}
    </div>
    {/if}
    {if $nnConfigArray["{$payment_mean.name}_shopping_type"] == 'zero' && $nnConfigArray["{$payment_mean.name}_guarantee_payment"] != 1 }
        <div id="novalnetZeroAmountBooking">
            <p style="color:red;">{s namespace='frontend/novalnet/payment' name='novalnet_zero_amount_booking'}Diese Bestellung wird als Nullbuchung verarbeitet. Ihre Zahlungsdaten werden für zukünftige Online-Einkäufe gespeichert.{/s}</p>
        </div>
    {/if}
    {if $nnConfigArray["{$payment_mean.name}_test_mode"]}
        <div id="novalnetTestMode">
            <p style="color:red;">{s namespace='frontend/novalnet/payment' name='novalnet_test_mode_message'}Die Zahlung wird im Testmodus durchgeführt, daher wird der Betrag für diese Transaktion nicht eingezogen.{/s}</p>
        </div>
    {/if}
