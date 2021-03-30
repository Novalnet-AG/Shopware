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
    jQuery(document).ready(function() {
        if (jQuery("#basketButton").val() == undefined) {
            var formid = jQuery("#nn_invoice_paymentid").closest("form").attr('id');
            if (formid != '') {
                jQuery('#' + formid).submit(function(event) {
                    var nn_invoice_paymentid = jQuery("#nn_invoice_paymentid").val();
                    var nn_checked = jQuery('input[id="payment_mean' + nn_invoice_paymentid + '"]:checked').val();
                    if (nn_checked != undefined && nn_invoice_paymentid == nn_checked) {
                        var nnErrorMessage = '';
                        if (jQuery("#pinNumberINVOICE").length) {
                            if (jQuery("#newPinINVOICE").is(':checked') == false && getInvoiceValueTrimmed(jQuery("#pinNumberINVOICE").val()).match(/^[a-zA-Z0-9]+$/) == null) {
                                nninvoice_highlights_error(['pinNumberINVOICE']);
                                nnErrorMessage = (getInvoiceValueTrimmed(jQuery("#pinNumberINVOICE").val()) == '') ? jQuery("#novalnetinvoice_pinbycallback_pin_error").val() : jQuery("#novalnetinvoice_pinbycallback_wrongpin_error").val();
                            }
                        } else {
                            if (jQuery("#nninvoice_telemobphone").length && getInvoiceValueTrimmed(jQuery("#nninvoice_telemobphone").val()).match(/^\d{8,}$/) == null) {
                                nninvoice_highlights_error(['nninvoice_telemobphone']);
                                nnErrorMessage = jQuery("#novalnetinvoice_pinbycallback_telephone_mobileno_error").val();
                            }
                        }
                        if (nnErrorMessage != '') {
                            show_invoice_error(nnErrorMessage);
                            return false;
                        }
                    }
                });
            }
        } else {
            jQuery('input[id="basketButton"][type="submit"]').click(function(event) {
                var nn_invoice_paymentid = jQuery("#nn_invoice_paymentid").val();
                var nn_checked = jQuery('input[id="payment_mean' + nn_invoice_paymentid + '"]:checked').val();
                if (nn_checked != undefined && nn_invoice_paymentid == nn_checked) {
                    var nnErrorMessage = '';
                    if (jQuery("#pinNumberINVOICE").length) {
                        if (jQuery("#newPinINVOICE").is(':checked') == false && getInvoiceValueTrimmed(jQuery("#pinNumberINVOICE").val()).match(/^[a-zA-Z0-9]+$/) == null) {
                            jQuery("#pinNumberINVOICE").val('');
                            nninvoice_highlights_error(['pinNumberINVOICE']);
                            nnErrorMessage = (getInvoiceValueTrimmed(jQuery("#pinNumberINVOICE").val()) == '') ? jQuery("#novalnetinvoice_pinbycallback_pin_error").val() : jQuery("#novalnetinvoice_pinbycallback_wrongpin_error").val();
                        }
                    } else {
                        if (jQuery("#nninvoice_telemobphone").length && getInvoiceValueTrimmed(jQuery("#nninvoice_telemobphone").val()).match(/^\d{8,}$/) == null) {
                            nninvoice_highlights_error(['nninvoice_telemobphone']);
                            nnErrorMessage = jQuery("#novalnetinvoice_pinbycallback_telephone_mobileno_error").val();
                        }
                    }
                    if (nnErrorMessage != '') {
                        show_invoice_error(nnErrorMessage);
                        return false;
                    }
                }
            });
        }
    });

    //display and higlights errors ;
    function nninvoice_highlights_error(params) {
        var version = jQuery('#novalnetinvoiceShopVersion').val();
        var text_field_error_class = (version >= '5.0.0') ? 'is--required has--error' : 'text instyle_error';
        jQuery('#' + params).addClass(text_field_error_class);
    }

    function show_invoice_error(nnErrorMessage) {
        if (jQuery("div[class='alert is--error is--rounded']").length) {
            jQuery("div[class='alert is--error is--rounded']").css('display', 'block');
            jQuery("div[class='alert--content'][id='nn_error']").html(nnErrorMessage);
            jQuery(window).scrollTop(jQuery("div[class='alert is--error is--rounded']").offset().top);
        } else if (jQuery("div[class='error'][id='nn_error']").length) {
            jQuery("div[class='error'][id='nn_error']").html(nnErrorMessage).css('display', 'block');
            jQuery(window).scrollTop(jQuery("div[class='error'][id='nn_error']").offset().top);
        } else if (jQuery("div[class='error agb_confirm']").length) {
            jQuery("div[class='error agb_confirm']").html(nnErrorMessage).css('display', 'block');
            jQuery(window).scrollTop(jQuery("div[class='error agb_confirm']").offset().top);
        }
        return false;
    }

    function getInvoiceValueTrimmed(value) {
        return jQuery.trim(value);
    }
