<?php
/**
* Novalnet payment plugin
*
* NOTICE OF LICENSE
*
* This source file is subject to Novalnet End User License Agreement
*
* DISCLAIMER
*
* If you wish to customize Novalnet payment extension for your needs, please contact technic@novalnet.de for more information.
*
* @author Novalnet AG
* @copyright Copyright (c) Novalnet
* @license https://www.novalnet.de/payment-plugins/kostenlos/lizenz
* @link https://www.novalnet.de
*
* This free contribution made by request.
*
* If you have found this script useful a small
* recommendation as well as a comment on merchant
*
*/

class Shopware_Plugins_Frontend_NovalPayment_lib_classes_BackendConfig
{
    /**
     * Returns the Global field Configuration Data
     *
     * @return array
     */
    public static function getConfigFields()
    {
        $enNovalLang                                               = Shopware_Plugins_Frontend_NovalPayment_lib_classes_TranslationHelper::novalnetGetLanguage('en');
        $deNovalLang                                               = Shopware_Plugins_Frontend_NovalPayment_lib_classes_TranslationHelper::novalnetGetLanguage('de');
        $language                                                  = Shopware_Plugins_Frontend_NovalPayment_lib_classes_TranslationHelper::novalnetGetLanguage(Shopware()->Locale()->getLanguage());
        $versionSupported                                          = version_compare(Shopware()->Config()->version, '4.3.0', '>=');
        $optionsFraudModule                                        = ($versionSupported) ? array(
            array(
                0,
                array(
                    'de_DE' => $deNovalLang['config_pin_field_not_active'],
                    'en_GB' => $enNovalLang['config_pin_field_not_active']
                )
            ),
            array(
                'pin',
                array(
                    'de_DE' => $deNovalLang['config_pin_field_callback'],
                    'en_GB' => $enNovalLang['config_pin_field_callback']
                )
            ),
            array(
                'sms',
                array(
                    'de_DE' => $deNovalLang['config_pin_field_sms'],
                    'en_GB' => $enNovalLang['config_pin_field_sms']
                )
            )
        ) : array(
            array(
                0,
                'None'
            ),
            array(
                'pin',
                'PIN by callback'
            ),
            array(
                'sms',
                'PIN by SMS'
            )
        );
        $optionsPaymentType                                        = ($versionSupported) ? array(
            array(
                0,
                array(
                    'de_DE' => $deNovalLang['config_pin_field_not_active'],
                    'en_GB' => $enNovalLang['config_pin_field_not_active']
                )
            ),
            array(
                'one',
                array(
                    'de_DE' => $deNovalLang['config_shop_type_oneclick'],
                    'en_GB' => $enNovalLang['config_shop_type_oneclick']
                )
            ),
            array(
                'zero',
                array(
                    'de_DE' => $deNovalLang['config_shop_type_zerobooking'],
                    'en_GB' => $enNovalLang['config_shop_type_zerobooking']
                )
            )
        ) : array(
            array(
                0,
                'None'
            ),
            array(
                'one',
                'One click shopping'
            ),
            array(
                'zero',
                'Zero amount booking'
            )
        );
        $optionsTrueFalse                                          = ($versionSupported) ? array(
            array(
                0,
                array(
                    'de_DE' => $deNovalLang['config_field_no'],
                    'en_GB' => $enNovalLang['config_field_no']
                )
            ),
            array(
                1,
                array(
                    'de_DE' => $deNovalLang['config_field_yes'],
                    'en_GB' => $enNovalLang['config_field_yes']
                )
            )
        ) : array(
            array(
                0,
                'False'
            ),
            array(
                1,
                'True'
            )
        );
        
        $authorizeCaptureOptions = array(
            array(
                'capture',
                array(
                    'de_DE' => $deNovalLang['config_capture'],
                    'en_GB' => $enNovalLang['config_capture']
                )
            ),
            array(
                'authorize',
                array(
                    'de_DE' => $deNovalLang['config_authorize'],
                    'en_GB' => $enNovalLang['config_authorize']
                )
            )
        );
        $configElement                                             = array(
            'novalnet_api' => array(
                'type' => 'button',
                'options' => array(
                    'label' => $language['config_novalnet_api'],
                    'position' => -3
                )
            ),
            'novalnet_secret_key' => array(
                'type' => 'text',
                'options' => array(
                    'label' => '',
                    'description' => $language['config_description_novalnet_secret_key'],
                    'value' => '',
                    'uniqueId' => 'novalnet_secret_key',
                    'position' => -2,
                    'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
                )
            ),
            'NClass' => array(
                'type' => 'button',
                'options' => array(
                    'label' => '',
                    'position' => -1,
                    'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP,
                    'handler' => 'function (){
                        var me = this,
                        panel = me.up(\'panel\');
                        win = me.up(\'window\');
                        // define one of the keywords (new or store) at this place.
                        var nnTextfieldObject = panel.down(\'textfield[uniqueId=novalnet_secret_key]\');
                        // Get value from this field
                        var nnKey = nnTextfieldObject.getValue();
                        var shop_id = nnTextfieldObject.getName();
                        // Only proceed if input available
                        if (!nnKey){
                            Ext.MessageBox.alert(\'\',\'Füllen Sie bitte alle Pflichtfelder aus.\');
                            return;
                        }
                        var url = window.location.pathname.split(\'/backend\')[0]
                                + \'/backend/NovalnetOrderOperations/autofillConfiguration\';
                        Ext.Ajax.request({
                           scope:this,
                           url: url,
                           success: function(result) {
                            var jsonData = Ext.JSON.decode(result.responseText);
                            if(jsonData.data != undefined){
                            var vendor = jsonData.data.vendor_id;
                            var nnObjVendor = panel.down(\'textfield[uniqueId=novalnet_vendor]\');
                            nnObjVendor.setValue(vendor);
                            var product = jsonData.data.product_id;
                            var nnObjProduct = panel.down(\'textfield[uniqueId=novalnet_product]\');
                            nnObjProduct.setValue(product);
                            var auth = jsonData.data.auth_code;
                            var nnObjAuth = panel.down(\'textfield[uniqueId=novalnet_auth_code]\');
                            nnObjAuth.setValue(auth);
                            var password = jsonData.data.access_key;
                            var nnObjPass = panel.down(\'textfield[uniqueId=novalnet_password]\');
                            nnObjPass.setValue(password);
						    }
                            var resultMessage = jsonData.sNovalError;
                            if (resultMessage){
                            Ext.MessageBox.alert(\'Status\',resultMessage);
                            } else {
                                Ext.MessageBox.alert(\'Status\',"' . $language['config_novalnet_success_msg'] . '");
                                win.close();
                            }
                           },
                           failure: function() {
                           Ext.MessageBox.alert(\'Status\',\'failure\');
                           },
                           // Pass all needed parameters
                           params: {
                           novalnet_secret_key: nnKey,
                           shop: shop_id
                           }
                        });
                }'
                )
            ),
            'novalnet_vendor' => array(
                'type' => 'text',
                'options' => array(
                    'label' => '',
                    'required' => true,
                    'uniqueId' => 'novalnet_vendor',
                    'readOnly' => true,
                    'position' => 1,
                    'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
                )
            ),
            'novalnet_auth_code' => array(
                'type' => 'text',
                'options' => array(
                    'label' => '',
                    'required' => true,
                    'uniqueId' => 'novalnet_auth_code',
                    'readOnly' => true,
                    'position' => 2,
                    'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
                )
            ),
            'novalnet_product' => array(
                'type' => 'text',
                'options' => array(
                    'label' => '',
                    'required' => true,
                    'uniqueId' => 'novalnet_product',
                    'readOnly' => true,
                    'position' => 3,
                    'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
                )
            ),
            'novalnet_tariff' => array(
                'type' => 'combo',
                'options' => array(
                    'position' => 4,
                    'label' => $language['select_tariff'],
                    'itemId' => 'novalnet_tariff',
                    'required' => true,
                    'emptyText' => $language['config_description_novalnet_tariff_val'],
                    'description' => $language['config_description_novalnet_tariff'],
                    'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
                )
            ),
            'novalnet_password' => array(
                'type' => 'text',
                'options' => array(
                    'label' => '',
                    'required' => true,
                    'uniqueId' => 'novalnet_password',
                    'readOnly' => true,
                    'position' => 5,
                    'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
                )
            ),
            
            'novalnet_proxy' => array(
                'type' => 'text',
                'options' => array(
                    'label' => '',
                    'description' => $language['config_description_novalnet_proxy'],
                    'position' => 7,
                    'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
                )
            ),
            
            'novalnet_curl_timeout' => array(
                'type' => 'text',
                'options' => array(
                    'label' => '',
                    'value' => '240',
                    'description' => $language['config_description_novalnet_curl_timeout'],
                    'position' => 8,
                    'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
                )
            ),
            
            'novalnet_payment_logo_display' => array(
                'type' => 'select',
                'options' => array(
                    'label' => '',
                    'value' => 1,
                    'description' => $language['config_description_novalnet_payment_logo_display'],
                    'store' => $optionsTrueFalse,
                    'position' => 9,
                    'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
                )
            ),
            
            'novalnet_order_status' => array(
                'type' => 'button',
                'options' => array(
                    'label' => $language['config_novalnet_order_status'],
                    'position' => 11
                )
            ),
            
            'novalnet_onhold_order_complete' => array(
                'type' => 'select',
                'options' => array(
                    'value' => 33, // payment has been ordered
                    'label' => '',
                    'store' => 'base.PaymentStatus',
                    'position' => 12,
                    'displayField' => 'description',
                    'valueField' => 'id',
                    'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
                )
            ),
            
            'novalnet_onhold_order_cancelled' => array(
                'type' => 'select',
                'options' => array(
                    'value' => 35, // process has been cancelled
                    'label' => '',
                    'position' => 13,
                    'store' => 'base.PaymentStatus',
                    'displayField' => 'description',
                    'valueField' => 'id',
                    'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
                )
            ),
            
            'novalnetcallback' => array(
                'type' => 'button',
                'options' => array(
                    'label' => '',
                    'position' => 14
                )
            ),
            
            'novalnetcallback_test_mode' => array(
                'type' => 'select',
                'options' => array(
                    'label' => '',
                    'position' => 15,
                    'value' => 0,
                    'store' => $optionsTrueFalse,
                    'description' => $language['config_description_novalnetcallback_test_mode'],
                    'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
                )
            ),
            
            'novalnet_callback_mail_send' => array(
                'type' => 'select',
                'options' => array(
                    'label' => '',
                    'value' => 0,
                    'position' => 16,
                    'store' => $optionsTrueFalse,
                    'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
                )
            ),
            
            'novalnet_callback_mail_send_to' => array(
                'type' => 'text',
                'options' => array(
                    'label' => '',
                    'value' => '',
                    'position' => 17,
                    'description' => $language['config_description_novalnet_callback_mail_send_to'],
                    'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
                )
            ),
            
            'novalnet_callback_mail_send_to_bcc' => array(
                'type' => 'text',
                'options' => array(
                    'label' => '',
                    'position' => 18,
                    'description' => $language['config_description_novalnet_callback_mail_send_to_bcc'],
                    'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
                )
            ),
            
            'novalnet_callback_notification_url' => array(
                'type' => 'text',
                'options' => array(
                    'label' => '',
                    'position' => 19,
                    'description' => $language['config_description_novalnet_callback_notification_url'],
                    'value' => substr($_SERVER['HTTP_REFERER'], '0', strpos($_SERVER['HTTP_REFERER'], 'backend')) . 'NovalPayment/status',
                    'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
                )
            )
        );
        /* Novalnet cc */
        $configElement['novalnetcc']                               = array(
            'type' => 'button',
            'options' => array(
                'label' => '',
                'position' => 20
            )
        );
        $configElement['novalnetcc_test_mode']                     = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 0,
                'store' => $optionsTrueFalse,
                'position' => 21,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetcc_shopping_type']                 = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 0,
                'position' => 22,
                'store' => $optionsPaymentType,
                'description' => $language['config_description_novalnetcc_shopping_type'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetcc_cc3d']                          = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 0,
                'position' => 23,
                'store' => $optionsTrueFalse,
                'description' => $language['config_description_novalnetcc_cc3d'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetcc_force_cc3d']                    = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 0,
                'position' => 24,
                'store' => $optionsTrueFalse,
                'description' => $language['config_description_novalnetcc_force_cc3d'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetcc_amex_enabled']                  = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 0,
                'position' => 25,
                'description' => $language['config_description_novalnetcc_amex_enabled'],
                'store' => $optionsTrueFalse,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetcc_maestro_enabled']               = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 0,
                'position' => 26,
                'description' => $language['config_description_novalnetcc_maestro_enabled'],
                'store' => $optionsTrueFalse,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetcc_capture']               = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 'capture',
                'position' => 28,
                'store' => $authorizeCaptureOptions,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetcc_manual_check_limit']            = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'description' => $language['config_description_novalnet_manual_check_limit'],
                'position' => 29,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetcc_payment_notification_to_buyer'] = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 30,
                'description' => $language['config_description_novalnetcc_payment_notification_to_buyer'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetcc_after_paymenstatus']            = array(
            'type' => 'select',
            'options' => array(
                'value' => 12, // Completely paid
                'label' => '',
                'position' => 31,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetcc_cciframe']                      = array(
            'type' => 'button',
            'options' => array(
                'label' => '',
                'position' => 32
            )
        );
        $configElement['novalnetcc_standard_configuration']          = array(
            'type' => 'button',
            'options' => array(
                'label' => '',
                'position' => 33
            )
        );
        $configElement['novalnetcc_standard_label']                  = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 34,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetcc_standard_field']                  = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 35,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetcc_standard_text']                   = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 36,
                'value' => 'body{color: #8798a9;font-family:Helvetica,Arial,sans-serif;font-weight: 500;}input{border-radius: 3px;background-clip: padding-box;box-sizing: border-box;line-height: 1.1875rem;padding: .625rem .625rem .5625rem .625rem;box-shadow: inset 0 1px 1px #dadae5;background: #f8f8fa;border: 1px solid #dadae5;border-top-color: #cbcbdb;color: #8798a9;text-align: left;font: inherit;letter-spacing: normal;margin: 0;word-spacing: normal;text-transform: none;text-indent: 0px;text-shadow: none;display: inline-block;height:40px;font-family:Helvetica,Arial,sans-serif;font-weight: 500;}input:focus{background-color: white;font-family:Helvetica,Arial,sans-serif;font-weight: 500;}',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        /* Novalnet SEPA */
        $configElement['novalnetsepa']                               = array(
            'type' => 'button',
            'options' => array(
                'label' => '',
                'position' => 37
            )
        );
        $configElement['novalnetsepa_test_mode']                     = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 0,
                'store' => $optionsTrueFalse,
                'position' => 38,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetsepa_shopping_type']                 = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 0,
                'position' => 39,
                'store' => $optionsPaymentType,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetsepa_capture']               = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 'capture',
                'position' => 40,
                'store' => $authorizeCaptureOptions,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetsepa_manual_check_limit']            = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'description' => $language['config_description_novalnet_manual_check_limit'],
                'position' => 41,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetsepa_due_date']                      = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 42,
                'description' => $language['config_description_novalnetsepa_due_date'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetsepa_fraud_module']                  = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'position' => 43,
                'value' => 0,
                'store' => $optionsFraudModule,
                'description' => $language['config_description_novalnetsepa_fraud_module'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetsepa_pin_limit']                     = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 44,
                'description' => $language['config_description_novalnetsepa_pin_limit'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetsepa_payment_notification_to_buyer'] = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 45,
                'description' => $language['config_description_novalnetsepa_payment_notification_to_buyer'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetsepa_after_paymenstatus']            = array(
            'type' => 'select',
            'options' => array(
                'value' => 12, // Completely paid
                'label' => '',
                'position' => 46,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetsepa_guarantee_configuration']       = array(
            'type' => 'button',
            'options' => array(
                'label' => '',
                'position' => 47
            )
        );
        $configElement['novalnetsepa_guarantee_payment']             = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 0,
                'position' => 48,
                'store' => $optionsTrueFalse,
                'description' => $language['config_description_novalnetsepa_guarantee_payment'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        
        $configElement['novalnetsepa_guarantee_before_paymenstatus']           = array(
            'type' => 'select',
            'options' => array(
                'value' => 17, // Open
                'label' => '',
                'position' => 49,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        
        $configElement['novalnetsepa_guaruntee_minimum']             = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 50,
                'description' => $language['config_description_novalnetsepa_guaruntee_minimum'],
                'value' => '',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        
        $configElement['novalnetsepa_force_guarantee_payment']          = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 1,
                'position' => 51,
                'store' => $optionsTrueFalse,
                'description' => $language['config_description_novalnetsepa_force_guarantee_payment'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        /* Novalnet payPal */
        $configElement['novalnetpaypal']                                = array(
            'type' => 'button',
            'options' => array(
                'label' => '',
                'position' => 52
            )
        );
        $configElement['novalnetpaypal_test_mode']                      = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 0,
                'store' => $optionsTrueFalse,
                'position' => 53,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetpaypal_shopping_type']                  = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 0,
                'position' => 54,
                'store' => $optionsPaymentType,
                'description' => $language['config_description_novalnetpaypal_shopping_type'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetpaypal_capture']               = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 'capture',
                'position' => 55,
                'store' => $authorizeCaptureOptions,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetpaypal_manual_check_limit']             = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'description' => $language['config_description_novalnet_manual_check_limit'],
                'position' => 56,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetpaypal_payment_notification_to_buyer']  = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 57,
                'description' => $language['config_description_novalnetpaypal_payment_notification_to_buyer'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetpaypal_before_paymenstatus']            = array(
            'type' => 'select',
            'options' => array(
                'value' => 17, // Open
                'label' => '',
                'position' => 58,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetpaypal_after_paymenstatus']             = array(
            'type' => 'select',
            'options' => array(
                'value' => 12, // Completely paid
                'label' => '',
                'position' => 59,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        /* Novalnet Instant */
        $configElement['novalnetinstant']                               = array(
            'type' => 'button',
            'options' => array(
                'label' => '',
                'position' => 60
            )
        );
        $configElement['novalnetinstant_test_mode']                     = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 0,
                'store' => $optionsTrueFalse,
                'position' => 61,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetinstant_payment_notification_to_buyer'] = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 62,
                'description' => $language['config_description_novalnetinstant_payment_notification_to_buyer'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetinstant_after_paymenstatus']            = array(
            'type' => 'select',
            'options' => array(
                'value' => 12, // Completely paid
                'label' => '',
                'position' => 63,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        /* Novalnet iDeal */
        $configElement['novalnetideal']                                 = array(
            'type' => 'button',
            'options' => array(
                'label' => '',
                'position' => 64
            )
        );
        $configElement['novalnetideal_test_mode']                       = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 0,
                'store' => $optionsTrueFalse,
                'position' => 65,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetideal_payment_notification_to_buyer']   = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 66,
                'description' => $language['config_description_novalnetideal_payment_notification_to_buyer'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetideal_after_paymenstatus']              = array(
            'type' => 'select',
            'options' => array(
                'value' => 12, // Completely paid
                'label' => '',
                'position' => 67,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        /* Novalnet eps */
        $configElement['novalneteps']                                   = array(
            'type' => 'button',
            'options' => array(
                'label' => '',
                'position' => 68
            )
        );
        $configElement['novalneteps_test_mode']                         = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 0,
                'store' => $optionsTrueFalse,
                'position' => 69,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalneteps_payment_notification_to_buyer']     = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 70,
                'description' => $language['config_description_novalneteps_payment_notification_to_buyer'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalneteps_after_paymenstatus']                = array(
            'type' => 'select',
            'options' => array(
                'value' => 12, // Completely paid
                'label' => '',
                'position' => 71,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        /* Novalnet giropay */
        $configElement['novalnetgiropay']                               = array(
            'type' => 'button',
            'options' => array(
                'label' => '',
                'position' => 72
            )
        );
        $configElement['novalnetgiropay_test_mode']                     = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 0,
                'store' => $optionsTrueFalse,
                'position' => 73,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetgiropay_payment_notification_to_buyer'] = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 74,
                'description' => $language['config_description_novalnetgiropay_payment_notification_to_buyer'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetgiropay_after_paymenstatus']            = array(
            'type' => 'select',
            'options' => array(
                'value' => 12, // Completely paid
                'label' => '',
                'position' => 75,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        /* Novalnet Invoice */
        $configElement['novalnetinvoice']                               = array(
            'type' => 'button',
            'options' => array(
                'label' => '',
                'position' => 76
            )
        );
        $configElement['novalnetinvoice_test_mode']                     = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 0,
                'store' => $optionsTrueFalse,
                'position' => 77,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetinvoice_due_date']                      = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 78,
                'description' => $language['config_description_novalnetinvoice_due_date'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetinvoice_capture']               = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 'capture',
                'position' => 79,
                'store' => $authorizeCaptureOptions,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetinvoice_manual_check_limit']            = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'description' => $language['config_description_novalnet_manual_check_limit'],
                'position' => 80,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetinvoice_fraud_module']                  = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'position' => 81,
                'value' => 0,
                'store' => $optionsFraudModule,
                'description' => $language['config_description_novalnetinvoice_fraud_module'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetinvoice_pin_limit']                     = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 82,
                'description' => $language['config_description_novalnetinvoice_pin_limit'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetinvoice_payment_notification_to_buyer'] = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 83,
                'description' => $language['config_description_novalnetinvoice_payment_notification_to_buyer'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetinvoice_before_paymenstatus']           = array(
            'type' => 'select',
            'options' => array(
                'value' => 17, // Open
                'label' => '',
                'position' => 84,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetinvoice_after_paymenstatus']            = array(
            'type' => 'select',
            'options' => array(
                'value' => 12, // Completely paid
                'label' => '',
                'position' => 85,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetinvoice_guarantee_configuration']       = array(
            'type' => 'button',
            'options' => array(
                'label' => '',
                'position' => 86
            )
        );
        $configElement['novalnetinvoice_guarantee_payment']             = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 0,
                'position' => 87,
                'store' => $optionsTrueFalse,
                'description' => $language['config_description_novalnetinvoice_guarantee_payment'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetinvoice_guarantee_before_paymenstatus']           = array(
            'type' => 'select',
            'options' => array(
                'value' => 17, // Open
                'label' => '',
                'position' => 88,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetinvoice_guaruntee_minimum']             = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 89,
                'description' => $language['config_description_novalnetinvoice_guaruntee_minimum'],
                'value' => '',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        
        $configElement['novalnetinvoice_force_guarantee_payment'] = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 1,
                'position' => 90,
                'store' => $optionsTrueFalse,
                'description' => $language['config_description_novalnetinvoice_force_guarantee_payment'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        
        
        /* Novalnet Prepayment */
        $configElement['novalnetprepayment']                               = array(
            'type' => 'button',
            'options' => array(
                'label' => '',
                'position' => 91
            )
        );
        $configElement['novalnetprepayment_test_mode']                     = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 0,
                'store' => $optionsTrueFalse,
                'position' => 92,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetprepayment_payment_notification_to_buyer'] = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 93,
                'description' => $language['config_description_novalnetprepayment_payment_notification_to_buyer'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetprepayment_before_paymenstatus']           = array(
            'type' => 'select',
            'options' => array(
                'value' => 17, // Open
                'label' => '',
                'position' => 94,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetprepayment_after_paymenstatus']            = array(
            'type' => 'select',
            'options' => array(
                'value' => 12, // Completely paid
                'label' => '',
                'position' => 95,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        
        /* Novalnet przelewy24 */
        $configElement['novalnetprzelewy24']                               = array(
            'type' => 'button',
            'options' => array(
                'label' => '',
                'position' => 96
            )
        );
        $configElement['novalnetprzelewy24_test_mode']                     = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 0,
                'store' => $optionsTrueFalse,
                'position' => 97,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        
        $configElement['novalnetprzelewy24_payment_notification_to_buyer'] = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 98,
                'description' => $language['config_description_novalnetprzelewy24_payment_notification_to_buyer'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetprzelewy24_before_paymenstatus']           = array(
            'type' => 'select',
            'options' => array(
                'value' => 17, // Open
                'label' => '',
                'position' => 99,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetprzelewy24_after_paymenstatus']            = array(
            'type' => 'select',
            'options' => array(
                'value' => 12, // Completely paid
                'label' => '',
                'position' => 100,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        
        /* Novalnet Cashpayment */
        $configElement['novalnetcashpayment']           = array(
            'type' => 'button',
            'options' => array(
                'label' => '',
                'position' => 101
            )
        );
        $configElement['novalnetcashpayment_test_mode'] = array(
            'type' => 'select',
            'options' => array(
                'label' => '',
                'value' => 0,
                'store' => $optionsTrueFalse,
                'position' => 102,
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetcashpayment_due_date']  = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 103,
                'description' => $language['config_description_novalnetcashpayment_due_date'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        
        $configElement['novalnetcashpayment_payment_notification_to_buyer'] = array(
            'type' => 'text',
            'options' => array(
                'label' => '',
                'position' => 104,
                'description' => $language['config_description_novalnetcashpayment_payment_notification_to_buyer'],
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetcashpayment_before_paymenstatus']           = array(
            'type' => 'select',
            'options' => array(
                'value' => 17, // Open
                'label' => '',
                'position' => 105,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        $configElement['novalnetcashpayment_after_paymenstatus']            = array(
            'type' => 'select',
            'options' => array(
                'value' => 12, // Completely paid
                'label' => '',
                'position' => 106,
                'store' => 'base.PaymentStatus',
                'displayField' => 'description',
                'valueField' => 'id',
                'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
            )
        );
        
        return $configElement;
    }
}
