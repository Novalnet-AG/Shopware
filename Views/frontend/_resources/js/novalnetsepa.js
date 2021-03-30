/**
* Novalnet payment plugin
* 
* NOTICE OF LICENSE
* 
* This source file is subject to Novalnet End User License Agreement
* 
* @author Novalnet AG
* @copyright Copyright (c) Novalnet
* @license https://www.novalnet.de/payment-plugins/kostenlos/lizenz
* @link https://www.novalnet.de
*/

    jQuery(document).ready(function (event) {
        // start removing input element highlight error
            jQuery("#novalnet_sepa_account_holder").on("keyup paste", function () {
                nnsepa_remove_error_class('novalnet_sepa_account_holder');
            });
            jQuery("#novalnet_sepa_iban").on("keyup paste", function () {
                nnsepa_remove_error_class('novalnet_sepa_iban');
            });
            jQuery("#nnsepa_telemobphone").on("keyup paste", function () {
                nnsepa_remove_error_class('nnsepa_telemobphone');
            });
            jQuery("#pinNumberSEPA").on("keyup paste", function () {
                nnsepa_remove_error_class('pinNumberSEPA');
            });

            jQuery("#newPinSEPA").on("click", function () {
                nnsepa_remove_error_class('pinNumberSEPA');
            });
            
            jQuery('input[type="submit"]').click(function (event) {
                var nn_sepa_paymentid = jQuery("#nn_sepa_paymentid").val();
                var nn_checked = jQuery('input[id="payment_mean'+nn_sepa_paymentid+'"]:checked').val();
                if (nn_checked != undefined &&  nn_sepa_paymentid == nn_checked) {
                    novalnetsepa_operation(event);
                }
            });
            // end removing input element highlight error
        if (jQuery("#nn_sepa_new_acc_details").length && jQuery("#nn_sepa_new_acc_details").val() == 1) {
            document.getElementById('novalnetsepa_acc').style.display='block';
            document.getElementById('novalnetsepa_ref_details').style.display='none';
            jQuery('#nn_sepa_new_acc_details').val('1');
            document.getElementById('novalnetsepa_fraud_module').style.display='block';
            if (jQuery('#novalnetsepa_after_error').val() =='1') {
                jQuery('#novalnetsepa_new_acc').html('<b><u> ' + jQuery('#novalnetsepa_given_account').val() + '</u></b>');
                jQuery('#novalnetsepa_ref_details').css({"display":"none"});
            }
        }
        if (jQuery("#basketButton").val() == undefined) {
            var formid = jQuery("#nn_sepa_id").closest("form").attr('id');
            jQuery('#'+formid).submit(function (event) {
                var nn_sepa_paymentid = jQuery("#nn_sepa_paymentid").val();
                var nn_checked = jQuery('input[id="payment_mean'+nn_sepa_paymentid+'"]:checked').val();
                if (nn_checked != undefined &&  nn_sepa_paymentid == nn_checked) {
                    novalnetsepa_operation(event);
                }
            });
        } else {
            jQuery('input[id="basketButton"][type="submit"]').click(function (e) {
                var nn_sepa_paymentid = jQuery("#nn_sepa_paymentid").val();
                var nn_checked = jQuery('input[id="payment_mean'+nn_sepa_paymentid+'"]:checked').val();
                var nnErrorMessage = '';
                if (nn_checked != undefined &&  nn_sepa_paymentid == nn_checked) {
                    novalnetsepa_operation(event);
                }
            });
        }
        
        jQuery('#sepa_mandate').click(function () {
        jQuery('#sepa_mandate_details_desc').toggle();
       });
        
        jQuery('#novalnet_sepa_iban').keyup(function (event) {
                           this.value = this.value.toUpperCase();
                           var field = this.value;
                           var value = "";
                           for(var i = 0; i < field.length;i++){
                                   if(i <= 1){
                                           if(field.charAt(i).match(/^[A-Za-z]/)){
                                                   value += field.charAt(i);
                                           }
                                   }
                                   if(i > 1){
                                           if(field.charAt(i).match(/^[0-9]/)){
                                                   value += field.charAt(i);
                                           }
                                   }
                           }
                           field = this.value = value;
          });
    });

    function novalnetsepa_operation(event)
    {
        var nnErrorMessage = '';
        var formid = jQuery("#nn_sepa_id").closest("form").attr('id');
        if (jQuery("#nn_sepa_new_acc_details").val() != 0 && jQuery("#novalnet_sepa_iban").val() == '') {
            nnErrorMessage =  (jQuery("#nn_lang_valid_account_details").val());
        } else if ( jQuery("#nn_sepa_new_acc_details").val() != 0 && jQuery("#newPinSEPA").length && jQuery("#newPinSEPA").is(':checked') == false && getSepaValueTrimmed(jQuery("#pinNumberSEPA").val()).match(/^[a-zA-Z0-9]+$/) == null) {
            nnErrorMessage =  (getSepaValueTrimmed(jQuery("#pinNumberSEPA").val()) == '') ? jQuery("#novalnetsepa_pinbycallback_pin_error").val() : jQuery("#novalnetsepa_pinbycallback_wrongpin_error").val();
            nnsepa_highlights_error(['pinNumberSEPA']);
        } else if ( jQuery("#nn_sepa_new_acc_details").val() != 0 && jQuery("#nnsepa_telemobphone").length && jQuery("#nnsepa_telemobphone").val().match(/^\d{8,}$/) == null) {
            nnsepa_highlights_error(['nnsepa_telemobphone']);
            nnErrorMessage = jQuery("#novalnetsepa_pinbycallback_telephone_mobileno_error").val();
        }
        if (nnErrorMessage != '') {
            event.preventDefault();
            show_sepa_error(nnErrorMessage);
            return false;
        } else {
            jQuery('#'+formid).submit();
        }
    }

    function getNumbersOnly(input_val)
    {
        return input_val.replace(/[^0-9]/g,'');
    }
    
    function sepaHolderFormat(evt)
    {
        var keycode = ( 'which' in evt ) ? evt.which : evt.keyCode;
        return (String.fromCharCode(keycode) || keycode == 0 || keycode == 8 || keycode == 45 );
    }
     //display and higlights errors ;
    function nnsepa_highlights_error(params)
    {
        var version = jQuery('#novalnetsepaShopVersion').val();
         jQuery('#'+params).addClass('text instyle_error');

    }

    function nnsepa_remove_error_class(params)
    {
        var version = jQuery('#novalnetsepaShopVersion').val();
        jQuery('#'+params).attr('class', 'text');
    }

    function show_sepa_error(nnErrorMessage)
    {
        if (jQuery("div[class='alert is--error is--rounded']").length) {
            jQuery("div[class='alert is--error is--rounded']").css('display','block');
            jQuery("div[class='alert is--info is--rounded']").css('display','none');
            jQuery("div[class='alert--content']").html(nnErrorMessage);
            jQuery(window).scrollTop(jQuery("div[class='alert is--error is--rounded']").offset().top);
        } else if (jQuery("div[class='error'][id='nn_error']").length) {
            jQuery("div[class='error'][id='nn_error']").html(nnErrorMessage).css('display','block');
            jQuery(window).scrollTop(jQuery("div[class='error'][id='nn_error']").offset().top);
        } else if (jQuery("div[class='error agb_confirm']").length ) {
            jQuery("div[class='error agb_confirm']").html(nnErrorMessage).css('display','block');
            jQuery(window).scrollTop(jQuery("div[class='error agb_confirm']").offset().top);
        }
        return false;
    }

    function getSepaValueTrimmed(value)
    {
        return jQuery.trim(value);
    }

    jQuery('#novalnetsepa_new_acc').click(function () {
        if (jQuery('#novalnetsepa_acc').css('display') == 'none') {
            document.getElementById('novalnetsepa_acc').style.display='block';
            document.getElementById('novalnetsepa_fraud_module').style.display='block';
            document.getElementById('novalnetsepa_ref_details').style.display='none';
            document.getElementById('nn_sepa_confirm_save_check').style.display  ='block';
            jQuery('#nn_sepa_new_acc_details').val('1');
            jQuery('#novalnetsepa_new_acc').html('<b><u> ' + jQuery('#novalnetsepa_given_account').val() + '</u></b>');
        } else {
            document.getElementById('novalnetsepa_acc').style.display='none';
            document.getElementById('novalnetsepa_fraud_module').style.display='none';
            document.getElementById('novalnetsepa_ref_details').style.display='block';
            document.getElementById('nn_sepa_confirm_save_check').style.display  ='none';
            jQuery('#nn_sepa_confirm_save_check').find('input').prop('checked',false);
            jQuery('#nn_sepa_new_acc_details').val('0');
            jQuery('#novalnetsepa_new_acc').html('<b><u> ' + jQuery('#novalnetsepa_new_account').val() + '</u></b>');
        }
    });
