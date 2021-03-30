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
 
    jQuery(document).ready(function () {
        var nn_paypal_paymentid = jQuery("#nn_paypal_paymentid").val();
        if (jQuery('#paypalRef').val() == 1) {
            jQuery("#payment_mean"+nn_paypal_paymentid).parents('.payment--method').find('.method--description.is--last').html(jQuery('#paypalref_lang').val());
        }
    });
    jQuery('#novalnetpaypal_new_acc').click(function () {
        var nn_paypal_paymentid = jQuery("#nn_paypal_paymentid").val();
        if (document.getElementById('nn_paypal_new_acc_form').value  == '0') {
            jQuery("#payment_mean"+nn_paypal_paymentid).parents('.payment--method').find('.method--description.is--last').html(jQuery('#paypalref_lang_before').val());
            document.getElementById('novalnetpaypal_ref_details').style.display  ='none';
            document.getElementById('nn_paypal_confirm_save_check').style.display  ='block';
            document.getElementById('nn_paypal_new_acc_details').value           ='1';
            document.getElementById('nn_paypal_new_acc_form').value              ='1';
            jQuery('#novalnetpaypal_new_acc').html('<b><u> ' + jQuery('#novalnetpaypal_given_account').val() + '</u></b>');
        } else {
            jQuery("#payment_mean"+nn_paypal_paymentid).parents('.payment--method').find('.method--description.is--last').html(jQuery('#paypalref_lang').val());
            document.getElementById('novalnetpaypal_ref_details').style.display  ='block';
            document.getElementById('nn_paypal_confirm_save_check').style.display  ='none';
            jQuery('#nn_paypal_confirm_save_check').find('input').prop('checked',false);
            document.getElementById('nn_paypal_new_acc_details').value           ='0';
            document.getElementById('nn_paypal_new_acc_form').value              ='0';
            jQuery('#novalnetpaypal_new_acc').html('<b><u> ' + jQuery('#novalnetpaypal_new_account').val() + '</u></b>');
        }
    });
