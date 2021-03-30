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

	var novalnetTargetOrgin = 'https://secure.novalnet.de';
	var iframe = jQuery('#nnIframe')[0];
	var iframeContent = iframe.contentWindow ? iframe.contentWindow : iframe.contentDocument.defaultView;
	
    jQuery(document).ready(function () {
        var nn_cc_paymentid = jQuery("#nn_cc_paymentid").val();
        if (jQuery('#cc3d').val() == 1) {
            jQuery("#payment_mean"+nn_cc_paymentid).parents('.payment--method').find('.method--description.is--last').html(jQuery('#cc3d_lang').val());
        }
        if (jQuery("#nn_cc_new_acc_details").length && jQuery("#nn_cc_new_acc_details").val() == 1) {
            document.getElementById('nn_cc_new_acc_details').value='1';
            jQuery('#novalnetcc_new_acc').css({"display":"none"});
        }
        var nn_checked = jQuery('input[id="payment_mean'+nn_cc_paymentid+'"]:checked').val();
        jQuery('input[id="payment_mean'+nn_cc_paymentid+'"]').click(function (e) {
            iframeContent.postMessage({'callBack' : 'getHeight'}, novalnetTargetOrgin);
        });
        if (jQuery("#basketButton").val() == undefined) {
            jQuery('button[type="submit"]').click(function (e) {
				var nn_checked = jQuery('input[id="payment_mean'+nn_cc_paymentid+'"]:checked').val();
                if (nn_checked != undefined &&  nn_cc_paymentid == nn_checked && jQuery('input[id="payment_mean'+nn_cc_paymentid+'"]').is(':checked')) {
                    novalnetcc_operation(e);
                    return false;
                }
            });
            jQuery('input[type="submit"]').click(function (e) {
                var nn_checked = jQuery('input[id="payment_mean'+nn_cc_paymentid+'"]:checked').val();
                if (nn_checked != undefined &&  nn_cc_paymentid == nn_checked && jQuery('input[id="payment_mean'+nn_cc_paymentid+'"]').is(':checked')) {
                    if (document.getElementById('nn_cc_new_acc_details').value == '1') {
                        novalnetcc_operation(e);
                        return false;
                    }
                }
            });
        } else {
            jQuery('input[id="basketButton"][type="submit"]').click(function (e) {
                if ( nn_checked != undefined &&  nn_cc_paymentid == nn_checked && jQuery('input[id="payment_mean'+nn_cc_paymentid+'"]').is(':checked')) {
                    novalnetcc_operation(e);
                    return false;
                }
            });
        }
        jQuery(window).resize(function () {
            if ($('#nnIframe').length > 0) {
                $('#nnIframe').attr('height', 0);
                iframeContent.postMessage({callBack : 'getHeight'}, novalnetTargetOrgin);
            }
        });
        
        jQuery(".abo-commerce-payment--selection-form").submit(function(e){
		    var nn_cc_paymentid = jQuery("#nn_cc_paymentid").val();
		    var nn_checked = jQuery('input[id="payment_mean'+nn_cc_paymentid+'"]:checked').val();
           if($('#novalnet_cc_hash').val() == '' && nn_checked != undefined &&  nn_cc_paymentid == nn_checked ){
               e.preventDefault();
		   }
         });

    });

    jQuery('#novalnetcc_new_acc').click(function () {
        var nn_cc_paymentid = jQuery("#nn_cc_paymentid").val();
        if (jQuery('#nn_cc_new_acc_details').val() == '0') {
            document.getElementById('novalnetcc_ref_details').style.display  ='none';
            document.getElementById('nnIframe').style.display  ='block';
            document.getElementById('nn_cc_confirm_save_check').style.display  ='block';
            iframeContent.postMessage({callBack : 'getHeight'}, novalnetTargetOrgin);
            document.getElementById('nnIframe').style.height  ='200px';
            jQuery('#nn_cc_new_acc_details').val('1');
            jQuery('#novalnetcc_new_acc').html('<b><u> ' + jQuery('#novalnetcc_given_account').val() + '</u></b>');
        } else {
            document.getElementById('novalnetcc_ref_details').style.display  ='block';
            document.getElementById('nnIframe').style.display  ='none';
            document.getElementById('nn_cc_confirm_save_check').style.display  ='none';
            jQuery('#nn_cc_confirm_save_check').find('input').prop('checked',false);
            jQuery('#nn_cc_new_acc_details').val('0');
            jQuery('#novalnetcc_new_acc').html('<b><u> ' + jQuery('#novalnetcc_new_account').val() + '</u></b>');
        }
    });

    function novalnetcc_operation(event)
    {
        var nn_cc_paymentid = jQuery("#nn_cc_paymentid").val();
        var nn_checked = jQuery('input[id="payment_mean'+nn_cc_paymentid+'"]:checked').val();
        if (jQuery('#nn_cc_new_acc_details').val() == '1' && nn_checked != undefined &&  nn_cc_paymentid == nn_checked) {
            iframeContent.postMessage({'callBack' : 'getHash'}, novalnetTargetOrgin);
        }
        event.preventDefault();
        var nnErrorMessage = '';
        if (jQuery('#nn_cc_new_acc_details').val() == '0') {
            var formid = jQuery("#nnIframe").closest("form").attr('id');
            jQuery('#'+formid).submit();
        }
    }

    function show_cc_error(nnErrorMessage)
    {
        if (jQuery("div[class='alert is--error is--rounded']").length) {
            jQuery("div[class='alert is--error is--rounded']").css('display','block');
            jQuery("div[class='alert--content']").html(nnErrorMessage);
            jQuery("div[class='alert is--info is--rounded']").css('display','none');
            jQuery(window).scrollTop(jQuery("div[class='alert is--error is--rounded']").offset().top);
        } else if (jQuery("div[class='error'][id='nn_error']").length) {
            jQuery("div[class='error'][id='nn_error']").html(nnErrorMessage).css('display','block');
            jQuery(window).scrollTop(jQuery("div[class='error'][id='nn_error']").offset().top);
        } else if (jQuery("div[class='error agb_confirm']").length ) {
            jQuery("div[class='error agb_confirm']").html(nnErrorMessage).css('display','block');
            jQuery(window).scrollTop(jQuery("div[class='error agb_confirm']").offset().top);
        } else if(jQuery("div[class='abo-payment-selection-error']").length ) {
			var span = jQuery('<div />').attr('class', 'alert--content').html(nnErrorMessage);
			jQuery('<div>', { class: 'alert is--error is--rounded'}).appendTo("div[class='abo-payment-selection-error']");
			jQuery('<div>', { class: 'alert--icon'}).append( jQuery('<i>', { class: 'icon--element icon--cross'})).appendTo("div[class='alert is--error is--rounded']");
			span.appendTo("div[class='alert is--error is--rounded']");
			jQuery(window).scrollTop(jQuery("div[class='abo-payment-selection-error']").offset().top);
		 }
        return false;
    }

    function getNumbersOnly(input_val)
    {
        return getCcValueTrimmed(input_val).replace(/[^0-9]/g, '');
    }
    
    if ( window.addEventListener ) {
            window.addEventListener('message', function (e) {
                addEvent(e);
            }, false);
    } else {
        window.attachEvent('onmessage', function (e) {
            addEvent(e);
        });
    }

    function loadCreditcardIframe()
    {
        //Default iframe style
        var novalnetDefaultLabel = $('#CreditcardDefaultLabel').val();
        var novalnetDefaultInput = $('#CreditcardDefaultInput').val();
        var novalnetDefaultCss   = $('#CreditcardDefaultCss').val();
        // Credit card holder style
        var novalnetHolderLabel = $('#CreditcardHolderLabel').val();
        var novalnetHolderInput = $('#CreditcardHolderInput').val();
        // Credit card Number style
        var novalnetNumberLabel = $('#CreditcardNumberLabel').val();
        var novalnetNumberInput = $('#CreditcardNumberInput').val();
        // Credit card Expiry date style
        var novalnetExpLabel = $('#CreditcardExpLabel').val();
        var novalnetExpInput = $('#CreditcardExpInput').val();
        // Credit card CVC style
        var novalnetCVCLabel  = $('#CreditcardCVCLabel').val();
        var novalnetCVCInput  = $('#CreditcardCVCInput').val();
        // Credit card holder language
        var novalnetHolderLabelLang = $('#CreditcardHolderLabelLang').val();
        var novalnetHolderInputLang = $('#CreditcardHolderInputLang').val();
        // Credit card Number language
        var novalnetNumberLabelLang = $('#CreditcardNumberLabelLang').val();
        var novalnetNumberInputLang = $('#CreditcardNumberInputLang').val();
        // Credit card Expiry date language
        var novalnetExpLabelLang = $('#CreditcardExpLabelLang').val();
        var novalnetExpInputLang = $('#CreditcardExpInputLang').val();
        // Credit card CVC language
        var novalnetCVCLabelLang  = $('#CreditcardCVCLabelLang').val();
        var novalnetCVCInputLang  = $('#CreditcardCVCInputLang').val();
        // CVC hint language
        var novalnetCVCHintLang  = $('#CreditcardCVCHintLang').val();
        // Error text language
        var novalnetCCErrorLang  = $('#CreditcardCCErrorLang').val();
        var textObj   = {
            card_holder: {
                labelText: novalnetHolderLabelLang,
                inputText: novalnetHolderInputLang,
            },
            card_number: {
                labelText: novalnetNumberLabelLang,
                inputText: novalnetNumberInputLang,
            },
            expiry_date: {
                labelText: novalnetExpLabelLang,
                inputText: novalnetExpInputLang,
            },
            cvc: {
                labelText: novalnetCVCLabelLang,
                inputText: novalnetCVCInputLang,
            },
            cvcHintText: novalnetCVCHintLang,
            errorText: novalnetCCErrorLang,
        };

        var request = {
            callBack    : 'createElements',
            customText: textObj,
            customStyle : {
                labelStyle : novalnetDefaultLabel,
                inputStyle : novalnetDefaultInput,
                styleText  : novalnetDefaultCss,
                card_holder : {
                    labelStyle : novalnetHolderLabel,
                    inputStyle : novalnetHolderInput,
                },
                card_number : {
                    labelStyle : novalnetNumberLabel,
                    inputStyle : novalnetNumberInput,
                },
                expiry_date : {
                    labelStyle : novalnetExpLabel,
                    inputStyle : novalnetExpInput,
                },
                cvc : {
                    labelStyle : novalnetCVCLabel,
                    inputStyle : novalnetCVCInput,
                }
            }
        };
        iframeContent.postMessage(request, novalnetTargetOrgin);
        iframeContent.postMessage({callBack : 'getHeight'}, novalnetTargetOrgin);
    }

    function addEvent(e)
    {
        if (e.origin === novalnetTargetOrgin) {
        var data = Function('"use strict";return (' + e.data + ')')();
            if (data['callBack'] == 'getHash') {
                e.preventDefault();
                if (data['error_message'] != undefined) {
                    nnErrorMessage = data['error_message'];
                    if (nnErrorMessage != '') {
                        e.preventDefault();
                        show_cc_error(nnErrorMessage);
                        return false;
                    }
                } else {
                    $('#novalnet_cc_hash').val(data['hash']);
                    $('#novalnet_cc_mask_no').val(data['card_number']);
                    $('#novalnet_cc_mask_type').val(data['card_type']);
                    $('#novalnet_cc_mask_holder').val(data['card_holder']);
                    $('#novalnet_cc_uniqueid').val(data['unique_id']);
                    $('#novalnet_cc_mask_month').val(data['card_exp_month']);
                    $('#novalnet_cc_mask_year').val(data['card_exp_year']);
                    var novalnetCCForm = $('#nnIframe').closest('form');
                    $(novalnetCCForm).submit();

                }
            } else if (data['callBack'] == 'getHeight') {
                $('#nnIframe').attr('height',data['contentHeight']);
            }
        }
    }
    function getCcValueTrimmed(value)
    {
        return jQuery.trim(value);
    }
