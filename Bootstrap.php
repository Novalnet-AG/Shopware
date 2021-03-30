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
require_once __DIR__ . '/Components/CSRFWhitelistAware.php';

class Shopware_Plugins_Frontend_NovalPayment_Bootstrap extends Shopware_Components_Plugin_Bootstrap
{
    
    /**
     * Plugin install method
     *
     * @param string $version
     * @return array
     */
    public function install($version = '11.2.5')
    {
        $lang         = Shopware()->Locale()->getLanguage();
        $novalnetLang = \Shopware_Plugins_Frontend_NovalPayment_lib_classes_TranslationHelper::novalnetGetLanguage($lang);
        $this->novalnetCreateEvents();
        Shopware_Plugins_Frontend_NovalPayment_lib_classes_NovalnetSql::novalnetSqlOperations();
        $this->novalnetAddOrderAttributes();
        $this->novalnetCreatePayment($version);
        $this->addMailContent();
        $this->novalnetCreateForm($version);
        $returnArray = array(
            'success' => true,
            'invalidateCache' => array('config', 'backend', 'proxy', 'frontend')
        );
        if (version_compare($version, '11.0.0', '>=')) {
            $returnArray = array_merge($returnArray, array(
                'message' => $novalnetLang['version11_suc_msg']
            ));
        }
        return $returnArray;
    }
    
    /**
     * Update function of the plugin
     *
     * @param string $version
     * @return bool|void
     */
    public function update($version)
    {
        $lang         = Shopware()->Locale()->getLanguage();
        $novalnetLang = \Shopware_Plugins_Frontend_NovalPayment_lib_classes_TranslationHelper::novalnetGetLanguage($lang);
        $this->install($version);
        Shopware()->Db()->query("UPDATE s_core_config_forms SET description = ? WHERE name=?", array($this->getDesc(),'NovalPayment'));
        return array(
            'success' => true,
            'message' => $novalnetLang['version11_suc_msg'],
            'invalidateCache' => array('config', 'backend', 'proxy', 'frontend')
        );
    }
    
    /**
     * Creates and subscribe the events and hooks.
     *
     * @param null
     * @return null
     */
    public function novalnetCreateEvents()
    {
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Frontend_NovalPayment', 'onGetNovalControllerPathFrontend');
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatch', 'novalnetOnPostDispatch');
        $this->subscribeEvent('Shopware_Modules_Order_SendMail_Send', 'novalnetMail');
        $this->subscribeEvent('Enlight_Controller_Action_PreDispatch', 'novalnetOnPreDispatch');
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Frontend_Checkout', 'onpayment');
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Frontend_Account', 'onpayment');
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Frontend_Account', 'onPostDispatchAccount');
        $this->subscribeEvent('Shopware_Components_Document::assignValues::after', 'onBeforeRenderDocument');
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Frontend_Checkout', 'onPostDispatchCheckout');
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatch', 'onPostDispatchNovalnetSave');
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_NovalnetSupport', 'onGetSupportControllerBackend');
        $this->subscribeEvent('Shopware_Controllers_Backend_Config_Before_Save_Config_Element', 'onGetbackControllerBackend');
        $this->subscribeEvent('Shopware_Modules_Admin_InitiatePaymentClass_AddClass', 'addNovalnetPaymentClass');
        // Extend backend order-overview
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Backend_Order', 'novalnetBackendOrder');
        $this->subscribeEvent('Enlight_Controller_Dispatcher_ControllerPath_Backend_NovalnetOrderOperations', 'novalnetBackendControllerOperations');
        // Affiliate process
        $this->subscribeEvent('Enlight_Controller_Action_PostDispatch_Frontend_Index', 'onPostDispatchFrontendIndex');
        $this->subscribeEvent('Enlight_Controller_Front_StartDispatch', 'onReturnStartup');
    }
    
    /**
     * get mail comments and send the order mail
     *
     * @param Enlight_Event_EventArgs $arguments
     * @return boolean
     */
    public function novalnetMail(Enlight_Event_EventArgs $arguments)
    {
        $context                       = $arguments->getVariables();
        Shopware()->Session()->context = $context;
        $paymentShortName              = $context['additional']['payment']['name'];
        $redirectFlag                  = $paymentShortName . '_gatewayAction';
        if (preg_match("/novalnet/i", $paymentShortName) && Shopware()->Session()->novalnet[$redirectFlag] == 1) {
            return false;
        }
        return;
    }
    
    /**
     * invoice/prepayment reference comments
     *
     * @param Enlight_Event_EventArgs $args
     * @return array
     */
    public function addNovalnetPaymentClass(\Enlight_Event_EventArgs $args)
    {
        $dirPath = $args->getReturn();
        $this->Application()->Loader()->registerNamespace('ShopwarePlugin\PaymentMethods\Components', __DIR__ . '/Components/');
        $dirPath['novalnetcc'] = $dirPath['novalnetinvoice'] = $dirPath['novalnetprepayment'] = 'ShopwarePlugin\PaymentMethods\Components\NovalnetComponent';
        return $dirPath;
    }
    
    /**
     * Returns path to a backend controller for an event.
     *
     * @param null
     * @return string
     */
    public function novalnetBackendControllerOperations()
    {
        Shopware()->Template()->addTemplateDir($this->Path() . 'Views/');
        return $this->Path() . 'Controllers/Backend/NovalnetOrderOperations.php';
    }
    
    /**
     * Extend shopware models with Novalnet specific attributes
     *
     * @param null
     * @return boolean
     */
    protected function novalnetAddOrderAttributes()
    {
        $objNovalSql = new Shopware_Plugins_Frontend_NovalPayment_lib_classes_NovalnetSql();
        if (!$objNovalSql->novalnetOrderAttributesExist()) {
            Shopware()->Db()->query('ALTER TABLE s_order_attributes ADD (novalnet_payment_tid bigint(20),novalnet_payment_gateway_status int(11),novalnet_payment_paid_amount int(11),novalnet_payment_order_amount int(11),novalnet_payment_current_amount int(11),novalnet_payment_subs_id varchar(30),novalnet_payment_due_date date,novalnet_payment_type varchar(30))');
        }
        
        $metaDataCache = Shopware()->Models()->getConfiguration()->getMetadataCacheImpl();
        $metaDataCache->deleteAll();
        Shopware()->Models()->generateAttributeModels(['s_order_attributes']);
        
        if (!$objNovalSql->novalnetSubsAttributesExist()) {
            Shopware()->Db()->query('ALTER TABLE s_order_attributes ADD novalnet_payment_subs_id varchar(30) AFTER novalnet_payment_current_amount');
            Shopware()->Db()->query('ALTER TABLE s_novalnet_transaction_detail ADD subs_id varchar(30) AFTER tariff_id');
        }
        return true;
    }
    
    /**
     * Eventhandler for display of Novalnet order operations in order detail view
     *
     * @param $arguments
     * @return null
     */
    public function novalnetBackendOrder($arguments)
    {
        $arguments->getSubject()->View()->addTemplateDir($this->Path() . 'Views/');
        if ($arguments->getRequest()->getActionName() === 'load') {
            $arguments->getSubject()->View()->extendsTemplate('backend/novalnet_order_operations/view/main/window.js');
        }
        if ($arguments->getRequest()->getActionName() === 'index') {
            $arguments->getSubject()->View()->extendsTemplate('backend/novalnet_order_operations/app.js');
        }
    }
    
    /**
     * Eventhandler for display of Novalnet support in payment view
     *
     * @param null
     * @return string
     */
    public function onGetSupportControllerBackend()
    {
        $this->Application()->Template()->addTemplateDir($this->Path() . 'Views/');
        return $this->Path() . 'Controllers/Backend/NovalnetSupport.php';
    }
    
    /**
     * For backend validation.
     *
     * @param Enlight_Event_EventArgs $args
     * @return null
     */
    public function onGetbackControllerBackend(Enlight_Event_EventArgs $args)
    {
        $request   = $args->getSubject()->Request();
        $reqValues = $request->getPost();
        $nHelper   = new Shopware_Plugins_Frontend_NovalPayment_lib_classes_NovalnetHelper;
        if ($reqValues['name'] == 'NovalPayment') {
            $getValidateAry = array();
            $needFields     = array(
                'novalnet_secret_key',
                'novalnetsepa_guaruntee_minimum',
                'novalnetinvoice_guaruntee_minimum',
                'novalnetsepa_due_date'
            );
            foreach ($reqValues['elements'] as $key) {
                foreach ($needFields as $field) {
                    if ($key['name'] == $field) {
                        foreach ($key['values'] as $keyval) {
                            $getValidateAry[$keyval['shopId']][$key['name']] = $keyval['value'];
                        }
                    }
                }
            }
            $errorMsg = false;
            foreach ($getValidateAry as $shops) {
                $invoiceMinimumAmt = !empty($shops['novalnetinvoice_guaruntee_minimum']) ? $shops['novalnetinvoice_guaruntee_minimum'] : 999;
                $sepaMinimumAmt    = !empty($shops['novalnetsepa_guaruntee_minimum']) ? $shops['novalnetsepa_guaruntee_minimum'] : 999;
                $sepaDueDate = !empty($shops['novalnetsepa_due_date']) ? $shops['novalnetsepa_due_date'] : 2;
                if (!ctype_digit($invoiceMinimumAmt) || ($invoiceMinimumAmt < 999)) {
                    $errorMsg = true;
                } elseif (!ctype_digit($sepaMinimumAmt) || ($sepaMinimumAmt < 999)) {
                    $errorMsg = true;
                } elseif ($sepaDueDate < 2 || $sepaDueDate > 14 || !$nHelper->isDigits($sepaDueDate)) {
                    $errorMsg = true;
                }
            }
            if ($errorMsg) {
                $data = array(
                    'success' => false
                );
                echo Zend_Json::encode($data);
                return;
            }
        }
    }
    
    /**
     * Create and save payment rows.
     *
     * @param $version
     * @return null
     */
    public function novalnetCreatePayment($version)
    {
        $objCreatePayment  = new Shopware_Plugins_Frontend_NovalPayment_lib_classes_CreatePayment();
        $novalnetHelper    = new Shopware_Plugins_Frontend_NovalPayment_lib_classes_NovalnetHelper;
        //For create the Novalnet payment methods from createPaymentModuleData class
        $paymentmoduleData = $objCreatePayment->createPaymentModuleData();
        
        /**@var $connection \Doctrine\DBAL\Connection*/
        $connection = $this->get('dbal_connection');
        
        if (version_compare(Shopware()->Config()->version, '5.6.0', '>=')) {
            $translationWriter = new \Shopware_Components_Translation($connection, Shopware()->Container());
        } else {
            $translationWriter = new \Shopware_Components_Translation();
        }
        
        //tariff update process
        if (version_compare($version, '11.1.4', '<')) {
            $oldKeyelementId          = Shopware()->Db()->fetchOne('SELECT id FROM s_core_config_elements WHERE name = ?', array(
                'novalnet_secret_key'
            ));
            $oldTariffelementId       = Shopware()->Db()->fetchRow('SELECT id,options FROM s_core_config_elements WHERE name = ?', array(
                'novalnet_tariff'
            ));
            $oldKey                   = Shopware()->Db()->fetchAll('SELECT shop_id,value FROM s_core_config_values WHERE element_id = ?', array(
                $oldKeyelementId
            ));
            $oldTariff                = Shopware()->Db()->fetchRow('SELECT shop_id,value FROM s_core_config_values WHERE element_id = ?', array(
                $oldTariffelementId['id']
            ));
            $getOldTariffFinalOptions = str_replace('~', '-', $oldTariffelementId['options']);
            Shopware()->Db()->query('update s_core_config_elements set options = ? where id = ?', array(
                $getOldTariffFinalOptions,
                $oldTariffelementId['id']
            ));
            $getOldTariff               = $novalnetHelper->getUnserializedData($oldTariff['value']);
            $getOldTariffFinal          = (string) '(' . str_replace('~', '-', $getOldTariff) . ')';
            $getOldTariffFinalConverted = serialize($getOldTariffFinal);
            Shopware()->Db()->query('update s_core_config_values set value = ? where shop_id = ? and element_id = ?', array(
                $getOldTariffFinalConverted,
                $oldTariff['shop_id'],
                $oldTariffelementId['id']
            ));
            foreach ($oldKey as $oldKeyfVal) {
                Shopware()->Db()->query('INSERT INTO s_novalnet_tariff(shopid, tariff) VALUES(?,?)', array(
                    $oldKeyfVal['shop_id'],
                    $novalnetHelper->getUnserializedData($oldKeyfVal['value'])
                ));
            }
        }
        //tariff update process
        foreach ($paymentmoduleData as $paymentname => $moduleData) {
            $getNovalPayment = Shopware()->Db()->fetchRow('SELECT id,action FROM s_core_paymentmeans where name = ?', array(
                $paymentname
            ));
            if (version_compare($version, '11.1.0', '<')) {
                if ($paymentname == 'novalnetpaypal' && !empty($getNovalPayment['id'])) {
                    Shopware()->Db()->query('update s_core_paymentmeans set template = ?,class=? where name = ?', array(
                        'novalnetpaypal.tpl',
                        'novalnetpaypal.php',
                        'novalnetpaypal'
                    ));
                }
                if ($paymentname == 'novalnetcc' && !empty($getNovalPayment['id'])) {
                    Shopware()->Db()->query('update s_core_paymentmeans set additionaldescription = ? where name = ?', array(
                        $moduleData['additionalDescription'],
                        'novalnetcc'
                    ));
                }
            }
            if (!$getNovalPayment || empty($getNovalPayment['id'])) {
                $paymentId = $this->createPayment($moduleData)->getId();
            }
            $snippets = Shopware()->Db()->fetchAll('SELECT localeID,value FROM s_core_snippets WHERE namespace LIKE "%novalnet%" AND name = ?
                ORDER BY localeID ASC', array(
                'payment_name_' . $paymentname
            ));
            foreach ($snippets as $paymentInfo) {
                $translationWriter->write($paymentInfo['localeID'], 'config_payment', $paymentId, array(
                    'description' => $paymentInfo['value'],
                    'additionalDescription' => $paymentInfo['value']
                ), true);
            }
        }
    }
    
    /**
     * Creates and save the payment configuration form.
     *
     * @param $version
     * @return null
     */
    public function novalnetCreateForm($version)
    {
        $form              = $this->Form();
        $translationHelper = new Shopware_Plugins_Frontend_NovalPayment_lib_classes_TranslationHelper();
        //get backend config fields from Shopware_Plugins_Frontend_NovalPayment_lib_classes_BackendConfig class
        $Config            = Shopware_Plugins_Frontend_NovalPayment_lib_classes_BackendConfig::getConfigFields();
        if ($version == '11.0.0') {
            $removeElementArray = array(
                'payment_logo_display',
                'novalnet_callback_notify_url',
                'novalnetcc_shopping_type_one_click',
                'novalnetsepa_shopping_type_one_click',
                'novalnetcc_maestro_logo',
                'novalnetcc_form_type',
                'novalnetcc_valid_year_limit'
            );
        } elseif ($version == '11.1.0') {
            $removeElementArray = array(
                'novalnetcc_shopping_type_one_click',
                'novalnetcc_csv',
                'novalnetcc_csv_label',
                'novalnetcc_csv_field',
                'novalnetsepa_shopping_type_one_click',
                'novalnetpaypal_zero_amount_booking',
                'novalnetpaypal_shopping_type_one_click'
            );
        }
        
        if ($version <= '11.2.0') {
            Shopware()->Db()->query('DELETE FROM s_core_config_elements WHERE name like "%novalnet%reference%"');
            
            $removeElementArray = array(
                'email_notification_testmode', 'novalnetcc_cartasi_enabled', 'novalnetcc_field_configuration',
                'novalnetcc_holder_field', 'novalnetcc_card_number_field', 'novalnetcc_expiry_date_field',
                'novalnetcc_cvc_field', 'novalnetcc_holder_name', 'novalnetcc_card_number', 'novalnetcc_expiry_date',
                'novalnetcc_cvc_label', 'novalnetcc_expiry_date_label', 'novalnetcc_card_number_label',
                'novalnetcc_holder_label', 'novalnetcc_cvc', 'novalnet_auto_refill', 'novalnetsepa_auto_refill'
            );
        }
        
        if (isset($removeElementArray)) {
            foreach ($removeElementArray as $rmval) {
                Shopware()->Db()->query('DELETE FROM s_core_config_elements WHERE name = "' . $rmval . '"');
            }
        }
        foreach ($Config as $key => $value) {
            $getNovalPaymentForm = Shopware()->Db()->fetchOne('SELECT id FROM s_core_config_elements where name = ?', array(
                $key
            ));
            if ($getNovalPaymentForm && !empty($getNovalPaymentForm)) {
                Shopware()->Db()->query('update s_core_config_elements set position = ? where name = ?', array(
                    $value['options']['position'],
                    $key
                ));
            } elseif (!$getNovalPaymentForm || empty($getNovalPaymentForm)) {
                $form->setElement($value['type'], $key, $value['options']);
            }
        }
        $translationHelper->changePluginTranslation($form, $Config);
    }
    
    
    /**
     * Set the request object for payment process
     *
     * @static
     * @param Enlight_Event_EventArgs $args
     * return boolean
     */
    public static function onpayment(Enlight_Event_EventArgs $args)
    {
        $request               = $args->getSubject()->Request();
        $view                  = $args->getSubject()->View();
        $novalnetHelper        = new Shopware_Plugins_Frontend_NovalPayment_lib_classes_NovalnetHelper;
        $novalnetPayments      = static::novalnetPayments();
        $novalnetLang          = Shopware_Plugins_Frontend_NovalPayment_lib_classes_TranslationHelper::novalnetGetLanguage(Shopware()->Locale()->getLanguage());
        $admin                 = Shopware()->Modules()->Admin();
        $nnCustomerData        = $admin->sGetUserData();
        $shippingcost          = $admin->sGetPremiumShippingcosts();
        $amount_basket         = Shopware()->Modules()->Basket()->sGetAmount();
        $amount                = sprintf('%0.2f', ($amount_basket['totalAmount'] + $shippingcost['value'])) * 100;
        $session               = Shopware()->Session();
        $countryIso            = Shopware()->Db()->fetchRow('SELECT countryiso FROM s_core_countries where id = ?', array(
            $_SESSION['Shopware']['sCountry']
        ));
        $billingCountry        = ($nnCustomerData['additional']['country']['countryiso']) ? $nnCustomerData['additional']['country']['countryiso'] : $countryIso['countryiso'];
        $enabledActivePayments = Shopware()->Db()->fetchAll('SELECT * FROM s_core_paymentmeans where active = 1');
        $paymentsEnabled       = !empty($enabledActivePayments) ? $enabledActivePayments : (($_SESSION['Shopware']['sOrderVariables']['sPayments']) ? $_SESSION['Shopware']['sOrderVariables']['sPayments'] : '');
        $paymentName           = $_SESSION['Shopware']['sOrderVariables']['sPayment']['name'];
        //Get farud prevention related informations
        $callbackPaymentsInfo  = $novalnetHelper->callbackPaymentsInfo();
        $allowedPinCountry     = $callbackPaymentsInfo['Country'];
        $pinFields             = $callbackPaymentsInfo['pinFields'];
        $pinPayments           = $callbackPaymentsInfo['pinPayments'];
        $billing_details  = ($nnCustomerData['billingaddress']) ? $nnCustomerData['billingaddress'] : $_SESSION['Shopware']['sOrderVariables']['sUserData']['billingaddress'];
        $shipping_details = ($nnCustomerData['shippingaddress']) ? $nnCustomerData['shippingaddress'] : $_SESSION['Shopware']['sOrderVariables']['sUserData']['shippingaddress'];
        $currency         = Shopware()->Config()->get('sCURRENCY');
        if (version_compare(Shopware()->Config()->version, '5.6.0', '>=')) {
            $novalnetConfig        = Shopware()->Container()->get('shopware.plugin.config_reader')->getByPluginName('NovalPayment');
        } else {
            $novalnetConfig        = Shopware()->Plugins()->Frontend()->NovalPayment()->Config();
        }
        //Get Novalnet merchant configuration details
        $novalnetConfig        = $novalnetHelper->getNovalConfigDetails($novalnetConfig);
        foreach ($paymentsEnabled as $sPaymentValue) {
            foreach ($pinPayments as $nnPayment) {
                $isGuaranteed        =  ($novalnetHelper->isguaranteed($amount, $billing_details, $shipping_details, $billingCountry, $currency, $novalnetConfig, $paymentName, true)) ? true : false;
                if (array_search($nnPayment, $sPaymentValue)) {
                    if (((!$novalnetConfig[$nnPayment . '_pin_limit'] || $novalnetHelper->isDigits($novalnetConfig[$nnPayment . '_pin_limit'])) && ($amount >= $novalnetConfig[$nnPayment . '_pin_limit'])) && in_array($billingCountry, $allowedPinCountry) && (!$novalnetConfig[$nnPayment . '_guarantee_payment'] || (empty($isGuaranteed) && $novalnetConfig[$nnPayment . '_force_guarantee_payment']))) {
                        if (in_array($novalnetConfig[$nnPayment . '_fraud_module'], $pinFields)) {
                            $view->assign($nnPayment . 'PinSms', $novalnetConfig[$nnPayment . '_fraud_module']);
                        }
                        
                        if ($session->novalnet[$nnPayment]['sPaymentPinTIDNumber']) {
                            if ($session->novalnetCallback[$nnPayment]['invalidCount']) {
                                $view->assign($nnPayment . 'sPaymentPinMaxEntry', 1);
                            } else {
                                $view->assign($nnPayment . 'sPaymentPinNumber', $session->novalnet[$nnPayment]['sPaymentPinTIDNumber']);
                            }
                        }
                    }
                    $view->assign($nnPayment . '_pinbycallback_telephone_mobileno_error', (($novalnetConfig[$nnPayment . '_fraud_module'] == 'pin') ? $novalnetLang['payment_novalnet_emptytelephone'] : $novalnetLang['payment_novalnet_emptymobile']));
                    $view->assign($nnPayment . '_pinbycallback_pin_error', $novalnetLang['payment_novalnet_emptypin']);
                    $view->assign($nnPayment . '_pinbycallback_wrongpin_error', $novalnetLang['payment_novalnet_wrongpin']);
                }
            }
        }
        if (($request->getControllerName() === 'account' && $request->getActionName() === 'payment') || ($request->getControllerName() === 'checkout' && $request->getActionName() === 'confirm') || ($request->getControllerName() === 'checkout' && $request->getActionName() === 'shippingPayment')) {
            foreach ($novalnetPayments as $paymentName) {
                if (in_array($paymentName, $pinPayments) && $session->novalnetCallback[$paymentName]['invalidCount'] && (time() >= $session->novalnetCallback[$paymentName]['sPaymentPinTime'])) {
                    $session->novalnetCallback[$paymentName]['invalidCount'] = false;
                }
                if (!$novalnetHelper->validateBackendConfig($novalnetConfig, false) || (in_array($paymentName, $pinPayments) && $session->novalnetCallback[$paymentName]['invalidCount']) || empty($novalnetConfig['novalnet_secret_key'])) {
                    if ($request->getControllerName() === 'account' && $request->getActionName() === 'payment') {
                        $view->sPaymentMeans = $novalnetHelper->disableFrontendPayments($view->sPaymentMeans, $paymentName);
                    } else {
                        $view->sPayments = $novalnetHelper->disableFrontendPayments($view->sPayments, $paymentName);
                    }
                }
            }
        }
        return true;
    }
    
    /**
     * Returns the path to a frontend controller for an event.
     *
     * @param null
     * @return string
     */
    public static function onGetNovalControllerPathFrontend()
    {
        Shopware()->Template()->addTemplateDir(dirname(__FILE__) . '/Views/');
        return dirname(__FILE__) . '/Controllers/Frontend/NovalPayment.php';
    }
    
    /**
     * Add the transaction in document render
     *
     * @param Enlight_Hook_HookArgs $args
     * @return null
     */
    public function onBeforeRenderDocument(Enlight_Hook_HookArgs $args)
    {
        $nHelper  = new Shopware_Plugins_Frontend_NovalPayment_lib_classes_NovalnetHelper;
        $document = $args->getSubject();
        if (!in_array($document->_order->payment['name'], static::novalnetPayments())) {
            return null;
        }
        $view                                   = $document->_view;
        $orderData                              = $view->getTemplateVars('Order');
        $novalnetLang                           = Shopware_Plugins_Frontend_NovalPayment_lib_classes_TranslationHelper::novalnetGetLanguage(Shopware()->Locale()->getLanguage());
        $paymentName                            = $orderData['_order']['attributes']['novalnet_payment_type'];
        $orderAmount                            = ($orderData['_order']['attributes']['novalnet_payment_current_amount']) ? $orderData['_order']['attributes']['novalnet_payment_current_amount'] : $orderData['_order']['invoice_amount'];
        $tid                                    = $orderData['_order']['transactionID'];
        $callbackAmount                         = Shopware()->Db()->fetchOne('SELECT sum(amount) FROM s_novalnet_callback_history WHERE org_tid = ?', array(
            $tid
        ));
        $getTestmode                            = Shopware()->Db()->fetchRow('SELECT test_mode FROM s_novalnet_transaction_detail where tid = ?', array(
            $tid
        ));
        $testmode                               = $getTestmode['test_mode'];
        $orderData['_order']['customercomment'] = nl2br((in_array($paymentName, array(
            'novalnetprepayment',
            'novalnetinvoice'
        )) && $callbackAmount >= $orderAmount) ? $nHelper->removePrepaymentNovalnetBankInfo($novalnetLang, $paymentName, $tid, $testmode) : $orderData['_order']['customercomment']);
        $view->assign('Order', $orderData);
    }
    
    /**
     * Extends account order detail template.
     *
     * @param Enlight_Event_EventArgs $args
     * @return null
     */
    public static function onPostDispatchAccount(Enlight_Event_EventArgs $args)
    {
        $request  = $args->getSubject()->Request();
        $response = $args->getSubject()->Response();
        $view     = $args->getSubject()->View();
        
        if (!$request->isDispatched() || $response->isException() || $request->getModuleName() !== 'frontend') {
            return null;
        }
        
        $view->addTemplateDir(dirname(__FILE__) . '/Views/');
        
        if ($request->getControllerName() === 'account' && $request->getActionName() === 'payment') {
            $view->extendsTemplate('frontend/plugins/account/payment.tpl');
        }
        //For extend the Novalnet template from core template
        if ($request->getControllerName() === 'account' && $request->getActionName() === 'orders') {
            $novalnetHelper = new Shopware_Plugins_Frontend_NovalPayment_lib_classes_NovalnetHelper;
            $view->assign('shopVersion', Shopware()->Config()->version);
            $view->extendsTemplate('frontend/plugins/account/order_item_details.tpl');
            $view->extendsTemplate('frontend/plugins/account/orders.tpl');
            $view->assign('shopVersion', Shopware()->Config()->version);
        }
        if ($request->getControllerName() === 'account' && $request->getActionName() === 'index') {
            $view->extendsTemplate('frontend/plugins/account/index.tpl');
        }
    }
    
    /**
     * Extends the checkout confirmation to show novalnet error messages.
     *
     * @param Enlight_Event_EventArgs $args
     * @return null
     */
    public static function onPostDispatchCheckout(Enlight_Event_EventArgs $args)
    {
        $request        = $args->getSubject()->Request();
        $response       = $args->getSubject()->Response();
        $view           = $args->getSubject()->View();
        $novalnetHelper = new Shopware_Plugins_Frontend_NovalPayment_lib_classes_NovalnetHelper;
        if (version_compare(Shopware()->Config()->version, '5.6.0', '>=')) {
            $configDetails = $novalnetHelper->getNovalConfigDetails(Shopware()->Container()->get('shopware.plugin.config_reader')->getByPluginName('NovalPayment'));
        } else {
            $configDetails = $novalnetHelper->getNovalConfigDetails(Shopware()->Plugins()->Frontend()->NovalPayment()->Config());
        }
        if (!$request->isDispatched() || $response->isException() || $request->getModuleName() !== 'frontend') {
            return null;
        }
        //For extend the Novalnet template from core template
        $view->addTemplateDir(dirname(__FILE__) . '/Views/');
        if ($request->getControllerName() === 'checkout' && $request->getActionName() === 'confirm') {
            if (!empty(Shopware()->Session()->cp_checkout_token)) {
                unset(Shopware()->Session()->cp_checkout_token, Shopware()->Session()->transaction_mode);
            }
            $view->extendsTemplate('frontend/plugins/checkout/confirm.tpl');
        }
        if ($request->getControllerName() === 'checkout' && $request->getActionName() === 'cart' && in_array($_SESSION['Shopware']['sOrderVariables']['sPayment']['name'], static::novalnetPayments()) || $request->getControllerName() === 'checkout' && $request->getActionName() === 'cart' && $configDetails['novalnetcc_cc3d']) {
            $view->assign('errormsg', Shopware()->Session()->novalnet['errormsg']);
            $view->assign('shopErrormsg', Shopware()->Session()->novalnet['shopErrormsg']);
            $view->extendsTemplate('frontend/plugins/checkout/cart.tpl');
            unset(Shopware()->Session()->novalnet['errormsg']);
        }
        if ($request->getControllerName() === 'checkout' && $request->getActionName() === 'shippingPayment') {
            $view->extendsTemplate('frontend/plugins/checkout/shipping_payment.tpl');
        }
        if ($request->getControllerName() === 'checkout' && $request->getActionName() === 'finish' && in_array($_SESSION['Shopware']['sOrderVariables']['sPayment']['name'], static::novalnetPayments())) {
            $view->assign('nnComment', Shopware()->Session()->nnComment);
            $view->assign('sTransactionumber', '');
            // Assign Cashpayment checkout token and test mode in view
            if (!empty(Shopware()->Session()->cp_checkout_token)) {
                $view->assign('cp_checkout_token', Shopware()->Session()->cp_checkout_token);
                $view->assign('transaction_mode', Shopware()->Session()->transaction_mode);
            }
            
            $view->extendsTemplate('frontend/plugins/checkout/finish.tpl');
        }
    }
    
    /**
     * Extends the checkout to save the shop default payment selection data.
     *
     * @param Enlight_Event_EventArgs $args
     * @return null
     */
    public static function onPostDispatchNovalnetSave(Enlight_Event_EventArgs $args)
    {
        $request = $args->getSubject()->Request();
        if (in_array($request->getActionName(), array(
            'saveShippingPayment',
            'savePayment'
        ))) {
            $admin          = Shopware()->Modules()->Admin();
            $nnCustomerData = $admin->sGetUserData();
            //Assign the form related values into session
            static::assignValues($request);
        }
    }
    
    /**
     * Setter for payment data and assign the datas to the template.
     *
     * @param $request
     * @return null
     */
    public static function assignValues($request = null)
    {
        $request           = ($request != null) ? $request : Shopware()->Front()->Request();
        $session           = Shopware()->Session();
        $novalnetHelper    = new Shopware_Plugins_Frontend_NovalPayment_lib_classes_NovalnetHelper;
        $paymentId         = $request->getParam('register');
        if (version_compare(Shopware()->Config()->version, '5.6.0', '>=')) {
            $configDetails     = $novalnetHelper->getNovalConfigDetails(Shopware()->Container()->get('shopware.plugin.config_reader')->getByPluginName('NovalPayment'));
        } else {
            $configDetails     = $novalnetHelper->getNovalConfigDetails(Shopware()->Plugins()->Frontend()->NovalPayment()->Config());
        }
        $currentPayment    = (static::getPaymentName(($paymentId['payment']) ? $paymentId['payment'] : $request->getParam('payment'))) ? static::getPaymentName(($paymentId['payment']) ? $paymentId['payment'] : $request->getParam('payment')) : $session->sOrderVariables->user['additional']['payment']['name'];
        $payment           = str_replace('novalnet', '', $currentPayment);
        $admin             = Shopware()->Modules()->Admin();
        $nnCustomerData    = $admin->sGetUserData();
        $configPinSms      = $configDetails[$currentPayment . '_fraud_module'];
        $configPinLimit    = $configDetails[$currentPayment . '_pin_limit'];
        $callbackPayments  = $novalnetHelper->callbackPaymentsInfo();
        $allowedPinCountry = $callbackPayments['Country'];
        $pinFields         = $callbackPayments['pinFields'];
        $billingCountry    = $nnCustomerData['additional']['country']['countryiso'];
        $shippingcost      = $admin->sGetPremiumShippingcosts();
        $amount_basket     = Shopware()->Modules()->Basket()->sGetAmount();
        $amount            = sprintf('%0.2f', ($amount_basket['totalAmount'] + $shippingcost['value'])) * 100;
        $request           = Shopware()->Front()->Request();
        if (!empty($session->novalnet[$currentPayment]['sPaymentPinTIDNumber'])) {
            if ($configPinSms != 'email') {
                $paymentUpperCase                                  = strtoupper($payment);
                $session->novalnet[$currentPayment]['newNovalPin'] = $request->getParam('newPin' . $paymentUpperCase);
                $session->novalnet[$currentPayment]['novalPin']    = trim($request->getParam('pinNumber' . $paymentUpperCase));
            }
            $session->novalnet['novalnetcc']['nn_cc_new_acc_details']     = $request->getParam('nn_cc_new_acc_details');
            $session->novalnet['novalnetsepa']['nn_sepa_new_acc_details'] = $request->getParam('nn_sepa_new_acc_details');
        } else {
            if ($currentPayment == 'novalnetcc' && $request->getParam('nn_cc_new_acc_details') != '') {
                $session->novalnet['novalnetcc']['cc_hash']                 = trim($request->getParam('novalnet_cc_hash'));
                $session->novalnet['novalnetcc']['novalnet_cc_mask_no']     = trim($request->getParam('novalnet_cc_mask_no'));
                $session->novalnet['novalnetcc']['novalnet_cc_mask_type']   = trim($request->getParam('novalnet_cc_mask_type'));
                $session->novalnet['novalnetcc']['novalnet_cc_mask_holder'] = trim($request->getParam('novalnet_cc_mask_holder'));
                $session->novalnet['novalnetcc']['cc_uniqueid']             = trim($request->getParam('novalnet_cc_uniqueid'));
                $session->novalnet['novalnetcc']['novalnet_cc_mask_month']  = trim($request->getParam('novalnet_cc_mask_month'));
                $session->novalnet['novalnetcc']['novalnet_cc_mask_year']   = trim($request->getParam('novalnet_cc_mask_year'));
                $session->novalnet['novalnetcc']['nn_cc_new_acc_details']   = $request->getParam('nn_cc_new_acc_details');
            } elseif ($currentPayment == 'novalnetsepa' && $request->getParam('nn_sepa_new_acc_details') != '') {
                $session->novalnet['novalnetsepa']['sepa_owner']              = trim($request->getParam('novalnet_sepa_account_holder'));
                $session->novalnet['novalnetsepa']['nn_sepa_iban']            = $request->getParam('novalnet_sepa_iban');
                $session->novalnet['novalnetsepa']['nn_sepa_new_acc_details'] = $request->getParam('nn_sepa_new_acc_details');
                $year                                                         = ($request->getParam('sepaDateOfBirthYear')) ? $request->getParam('sepaDateOfBirthYear') . '-' . $request->getParam('sepaDateOfBirthMonth') . '-' . $request->getParam('sepaDateOfBirthDay') : '';
                $session->novalnet['novalnetsepa']['birth_date']              = $year;
            } elseif ($currentPayment == 'novalnetinvoice') {
                $year                                               = ($request->getParam('invoiceDateOfBirthYear')) ? $request->getParam('invoiceDateOfBirthYear') . '-' . $request->getParam('invoiceDateOfBirthMonth') . '-' . $request->getParam('invoiceDateOfBirthDay') : '';
                $session->novalnet['novalnetinvoice']['birth_date'] = $year;
            } elseif ($currentPayment == 'novalnetpaypal') {
                $session->novalnet['novalnetpaypal']['nn_paypal_new_acc_details'] = $request->getParam('nn_paypal_new_acc_details');
                $session->novalnet['novalnetpaypal']['nn_paypal_new_acc_form']    = $request->getParam('nn_paypal_new_acc_form');
            }
            $session->novalnet['confirm_save'] = $request->getParam('confirm_save_check');
            if ((in_array($configPinSms, $pinFields) && ((!$configPinLimit || $novalnetHelper::isDigits($configPinLimit)) && ($amount >= $configPinLimit)) && in_array($billingCountry, $allowedPinCountry))) {
                $session->novalnet[$currentPayment]['telePhone'] = $request->getParam('nn' . $payment . '_telemobphone');
            }
        }
    }
    
    /**
     * Current Payment Name
     *
     * @param $paymentId
     * @return null|string
     */
    public static function getPaymentName($paymentId)
    {
        if ($paymentId == '') {
            return null;
        }
        return Shopware()->Db()->fetchOne('SELECT name FROM s_core_paymentmeans WHERE id = ?', array(
            $paymentId
        ));
    }
    
    /**
     * Set the request object for payment process
     *
     * @param Enlight_Event_EventArgs $args
     * @return null
     */
    public static function novalnetOnPreDispatch(Enlight_Event_EventArgs $args)
    {
        $request               = $args->getSubject()->Request();
        $view                  = $args->getSubject()->View();
        $novalnetHelper        = new Shopware_Plugins_Frontend_NovalPayment_lib_classes_NovalnetHelper;
        $pageControl           = $request->getParams();
        $currentLanguage       = strtoupper(Shopware()->Locale()->getLanguage());
        $novalnetLang          = Shopware_Plugins_Frontend_NovalPayment_lib_classes_TranslationHelper::novalnetGetLanguage($currentLanguage);
        if (version_compare(Shopware()->Config()->version, '5.6.0', '>=')) {
            $novalnetConfig        = Shopware()->Container()->get('shopware.plugin.config_reader')->getByPluginName('NovalPayment');
        } else {
            $novalnetConfig        = Shopware()->Plugins()->Frontend()->NovalPayment()->Config();
        }
        $novalnetConfigAsArray = $novalnetHelper->getNovalConfigDetails($novalnetConfig);
        if ($request->getModuleName() === 'frontend') {
            $router          = Shopware()->Front()->Router();
            $userData        = Shopware()->System()->sMODULES['sAdmin']->sGetUserData();
            $paymentName     = ($userData['additional']['payment']['name']) ? $userData['additional']['payment']['name'] : $_SESSION['Shopware']['sOrderVariables']['sPayment']['name'];
            $userData        = Shopware()->System()->sMODULES['sAdmin']->sGetUserData();
            $nnCustomerData  = $novalnetHelper->getPaymentCustomerAddressInfo($userData);
            $companyValue = ($userData['billingaddress']['company']) ? $userData['billingaddress']['company'] : ($userData['additional']['user']['company'] ? $userData['additional']['user']['company'] : '');
            $indexController = $args->getSubject();
            if ($request->getControllerName() == 'account') {
                $errorUrl = (version_compare(Shopware()->Config()->version, '5.0.0', '>=')) ? $router->assemble(array(
                    'controller' => 'account',
                    'action' => 'payment',
                    'sTarget' => 'checkout'
                )) : (($nnCustomerData['additional']['user']['accountmode'] == 1) ? $router->assemble(array(
                    'controller' => 'checkout',
                    'action' => 'confirm'
                )) : $router->assemble(array(
                    'controller' => 'account',
                    'action' => 'payment',
                    'sTarget' => 'checkout'
                )));
            } else {
                $errorUrl = (version_compare(Shopware()->Config()->version, '5.0.0', '>=')) ? $router->assemble(array(
                    'controller' => 'checkout',
                    'action' => 'shippingPayment',
                    'sTarget' => 'checkout'
                )) : (($nnCustomerData['additional']['user']['accountmode'] == 1) ? $router->assemble(array(
                    'controller' => 'checkout',
                    'action' => 'confirm'
                )) : $router->assemble(array(
                    'controller' => 'account',
                    'action' => 'payment',
                    'sTarget' => 'checkout'
                )));
            }
            //Load Novalnet affiliate details
            $nnAffiliateDetails = $novalnetHelper->getNovalAffiliateDetails(Shopware()->Session()->nnAffId, $nnCustomerData['customer_no']);
            if ($nnAffiliateDetails && $novalnetHelper->isDigits($nnAffiliateDetails['aff_id']) && $nnAffiliateDetails['aff_authcode'] && $nnAffiliateDetails['aff_accesskey']) {
                $novalnetConfigAsArray['novalnet_vendor']    = $nnAffiliateDetails['aff_id'];
                $novalnetConfigAsArray['novalnet_auth_code'] = $nnAffiliateDetails['aff_authcode'];
                $novalnetConfigAsArray['novalnet_password']  = $nnAffiliateDetails['aff_accesskey'];
            }
            $admin               = Shopware()->Modules()->Admin();
            $nnCheckCustomerData = $admin->sGetUserData();
            $view->assign('nnConfigArray', $novalnetConfigAsArray);
            $view->assign('redirectAry', array(
                'redirect',
                'iframe'
            ));
            $view->assign('novalnet_vendor', $novalnetConfigAsArray['novalnet_vendor']);
            $view->assign('novalnet_auth_code', $novalnetConfigAsArray['novalnet_auth_code']);
            $view->assign('currentLang', $currentLanguage);
            $view->assign('language', Shopware()->Locale()->getLanguage());
            $view->assign('payment_name', $paymentName);
            $view->assign('company_value', $companyValue);
            //subscription language
            $view->assign('subs_empty', $novalnetLang['subscription_novalnet_order_operations_subscription_title']);
            $view->assign('subs_confirm', $novalnetLang['confirm_subs_cancel']);
            $shippingcost     = $admin->sGetPremiumShippingcosts();
            $amount_basket    = Shopware()->Modules()->Basket()->sGetAmount();
            $amount           = sprintf('%0.2f', ($amount_basket['totalAmount'] + $shippingcost['value'])) * 100;
            $countryIso       = Shopware()->Db()->fetchRow('SELECT countryiso FROM s_core_countries where id = ?', array(
                $_SESSION['Shopware']['sCountry']
            ));
            $currency         = Shopware()->Config()->get('sCURRENCY');
            $billing_details  = ($userData['billingaddress']) ? $userData['billingaddress'] : $_SESSION['Shopware']['sOrderVariables']['sUserData']['billingaddress'];
            $shipping_details = ($userData['shippingaddress']) ? $userData['shippingaddress'] : $_SESSION['Shopware']['sOrderVariables']['sUserData']['shippingaddress'];
            $billingCountry   = (Shopware()->Session()->sOrderVariables->sUserData['additional']['country']['countryiso']) ? Shopware()->Session()->sOrderVariables->sUserData['additional']['country']['countryiso'] : $countryIso['countryiso'];
            $getBirthdate     = $userData['additional']['user']['birthday'];
            $birthdate        = $getBirthdate ? explode('-', $getBirthdate) : '';
            $birthdate        = array(
                'day' => $birthdate[2],
                'month' => $birthdate[1],
                'year' => $birthdate[0]
            );
            $date_birth_field = ($novalnetHelper->isguaranteed($amount, $billing_details, $shipping_details, $billingCountry, $currency, $novalnetConfigAsArray, $paymentName, true)) ? true : false;
            $view->assign('date_birth_field', $date_birth_field);
            $view->assign('birthdate_val', $birthdate);
            $randomString = $novalnetHelper->randomString();
            $view->assign('novalnet_random_string', $randomString);
            $country = Shopware()->Models()->getRepository('Shopware\Models\Country\Country')->getCountriesQuery()->getArrayResult();
            foreach ($country as $v) {
                $nnCountry[$v['iso']] = $v['name'];
            }
            $view->assign('country_list', $nnCountry);
            $view->assign('nn_customer_data', $nnCustomerData);
            $view->assign('nn_customer_full_name', $nnCustomerData['first_name'] . ' ' . $nnCustomerData['lastname']);
            $view->assign('nn_paypal_new_acc_form', (Shopware()->Session()->novalnet['novalnetpaypal']['nn_paypal_new_acc_form']) ? Shopware()->Session()->novalnet['novalnetpaypal']['nn_paypal_new_acc_form'] : 1);
            if ($pageControl['controller'] == 'checkout' && $pageControl['action'] == 'shippingPayment' && $paymentName == 'novalnetcc') {
                unset(Shopware()->Session()->novalnet['novalnetcc']['nn_cc_new_acc_details']);
            }
            if (in_array($pageControl['controller'], array(
                'checkout',
                'account',
                'NovalPayment'
            )) && $paymentName == 'novalnetcc' && isset($novalnetConfigAsArray['novalnetcc_shopping_type']) && $novalnetConfigAsArray['novalnetcc_shopping_type'] == 'one' && $nnCheckCustomerData['additional']['user']['accountmode'] == 0 && !$novalnetConfigAsArray['novalnetcc_cc3d'] && !$novalnetConfigAsArray['novalnetcc_force_cc3d'] && (!Shopware()->Session()->novalnet[$paymentName]['nn_cc_new_acc_details'] || Shopware()->Session()->novalnet[$paymentName]['nn_cc_new_acc_details'] == 0)) {
                $nnCcDetails   = $novalnetHelper->getCustomerOrders('novalnetcc', $nnCustomerData['customer_no']);
                $ConfigDetails = $novalnetHelper->getUnserializedData($nnCcDetails['configuration_details']);
                $ConfigDetails = array_merge($ConfigDetails, array(
                    'curr_payment_name' => $paymentName
                ));
                if (!empty($ConfigDetails) && (isset($ConfigDetails['create_payment_ref']) && $ConfigDetails['create_payment_ref'] == '1')) {
                    $ConfigDetails                                               = array_merge($ConfigDetails, array(
                        'tid' => $nnCcDetails['tid'],
                        'curr_payment_name' => $paymentName
                    ));
                    Shopware()->Session()->novalnet['one_order_' . $paymentName] = 1;
                    $view->assign('novalnetcc_account_details', $ConfigDetails);
                    Shopware()->Session()->novalnet[$paymentName . '_reference_tid'] = $ConfigDetails['tid'];
                }
            } else {
                unset(Shopware()->Session()->novalnet['one_order_novalnetcc']);
            }
            $remoteIp  = $novalnetHelper->getIp();
            $request   = ($request != null) ? $request : Shopware()->Front()->Request();
            $serverIp  = $novalnetHelper->getIp('SERVER_ADDR');
            $signature = base64_encode("vendor=".$novalnetConfigAsArray['novalnet_vendor']."&product=".$novalnetConfigAsArray['novalnet_product']."&server_ip=".$serverIp);
            $iframeurl = 'https://secure.novalnet.de/cc?api=' . $signature . '&ln=' . Shopware()->Locale()->getLanguage();
            $view->assign('iframeurl', $iframeurl);
            $view->assign('remoteIp', $remoteIp);
            $view->assign('controller', $request->getParam('controller'));
            if ($pageControl['controller'] == 'checkout' && $pageControl['action'] == 'shippingPayment' && $paymentName == 'novalnetsepa') {
                unset(Shopware()->Session()->novalnet['novalnetsepa']['nn_sepa_new_acc_details']);
            }
            if (in_array($pageControl['controller'], array(
                'checkout',
                'account',
                'NovalPayment'
            )) && $paymentName == 'novalnetsepa' && isset($novalnetConfigAsArray['novalnetsepa_shopping_type']) && $novalnetConfigAsArray['novalnetsepa_shopping_type'] == 'one' && $nnCheckCustomerData['additional']['user']['accountmode'] == 0 && (!Shopware()->Session()->novalnet[$paymentName]['nn_sepa_new_acc_details'] || Shopware()->Session()->novalnet[$paymentName]['nn_sepa_new_acc_details'] == 0)) {
                $nnSepaDetails = $novalnetHelper->getCustomerOrders('novalnetsepa', $nnCustomerData['customer_no']);
                $ConfigDetails = $novalnetHelper->getUnserializedData($nnSepaDetails['configuration_details']);
                $ConfigDetails = array_merge($ConfigDetails, array(
                    'curr_payment_name' => $paymentName
                ));
                if (!empty($ConfigDetails) && (isset($ConfigDetails['create_payment_ref']) && $ConfigDetails['create_payment_ref'] == '1')) {
                    $ConfigDetails                                               = array_merge($ConfigDetails, array(
                        'tid' => $nnSepaDetails['tid'],
                        'curr_payment_name' => $paymentName
                    ));
                    Shopware()->Session()->novalnet['one_order_' . $paymentName] = 1;
                    $birthdate                                                   = !empty($ConfigDetails['birth_date']) ? explode('-', $ConfigDetails['birth_date']) : '';
                    $view->assign('novalnetsepa_account_details', $ConfigDetails);
                    $view->assign('birthdate_val', $birthdate);
                    Shopware()->Session()->novalnet['birth_date' . $paymentName]     = ($ConfigDetails['birth_date']) ? $ConfigDetails['birth_date'] : '';
                    Shopware()->Session()->novalnet[$paymentName . '_reference_tid'] = $ConfigDetails['tid'];
                }
            } else {
                unset(Shopware()->Session()->novalnet['one_order_novalnetsepa']);
            }
            if ($pageControl['controller'] == 'checkout' && $pageControl['action'] == 'shippingPayment' && $paymentName == 'novalnetpaypal') {
                unset(Shopware()->Session()->novalnet['novalnetpaypal']['nn_paypal_new_acc_details']);
            }
            if (in_array($pageControl['controller'], array(
                'checkout',
                'account',
                'NovalPayment'
            )) && $paymentName == 'novalnetpaypal' && isset($novalnetConfigAsArray['novalnetpaypal_shopping_type']) && $novalnetConfigAsArray['novalnetpaypal_shopping_type'] == 'one' && $nnCheckCustomerData['additional']['user']['accountmode'] == 0 && (!Shopware()->Session()->novalnet['novalnetpaypal']['nn_paypal_new_acc_details'] || Shopware()->Session()->novalnet['novalnetpaypal']['nn_paypal_new_acc_details'] == 0)) {
                $nnpaypalDetails = $novalnetHelper->getCustomerOrders('novalnetpaypal', $nnCustomerData['customer_no']);
                $ConfigDetails   = $novalnetHelper->getUnserializedData($nnpaypalDetails['configuration_details']);
                $ConfigDetails   = array_merge($ConfigDetails, array(
                    'curr_payment_name' => $paymentName
                ));
                if (!empty($ConfigDetails) && (isset($ConfigDetails['create_payment_ref']) && $ConfigDetails['create_payment_ref'] == '1')) {
                    Shopware()->Session()->novalnet['one_order_' . $paymentName] = 1;
                    if ($nnpaypalDetails['tid']) {
                        $ConfigDetails = array_merge($ConfigDetails, array(
                            'tid' => $nnpaypalDetails['tid'],
                            'curr_payment_name' => $paymentName
                        ));
                        $view->assign('nn_paypal_new_acc_form', '0');
                        $view->assign('novalnetpaypal_account_details', $ConfigDetails);
                        Shopware()->Session()->novalnet[$paymentName . '_reference_tid'] = $ConfigDetails['tid'];
                    }
                }
            } else {
                unset(Shopware()->Session()->novalnet['one_order_novalnetpaypal']);
            }
            $paymentOnly = str_replace('novalnet', '', $paymentName);
            if ($paymentName == 'novalnetcc' && Shopware()->Session()->novalnet[$paymentName][$paymentOnly . '_hash']) {
                $maskAry = array(
                        'maskccholder' => Shopware()->Session()->novalnet['novalnetcc']['novalnet_cc_mask_holder'],
                        'maskccno' => Shopware()->Session()->novalnet['novalnetcc']['novalnet_cc_mask_no'],
                        'maskcctype' => Shopware()->Session()->novalnet['novalnetcc']['novalnet_cc_mask_type'],
                        'maskccdate' => Shopware()->Session()->novalnet['novalnetcc']['novalnet_cc_mask_month'] . "/" . Shopware()->Session()->novalnet['novalnetcc']['novalnet_cc_mask_year']
                    );
                $view->assign('maskdetails', $maskAry);
            }
            if ($paymentName == 'novalnetsepa') {
                $maskAry = array(
                        'masksepaholder' => Shopware()->Session()->novalnet['novalnetsepa']['sepa_owner'],
                        'masksepaiban' => Shopware()->Session()->novalnet['novalnetsepa']['nn_sepa_iban']
                    );
                $view->assign('maskdetails', $maskAry);
            }
            
            
            if (in_array($pageControl['controller'], array(
                'checkout',
                'account'
            )) && $paymentName == 'novalnetpaypal' && isset($novalnetConfigAsArray['novalnetpaypal_shopping_type']) && $novalnetConfigAsArray['novalnetpaypal_shopping_type'] == 'zero') {
                unset(Shopware()->Session()->novalnet['one_order_' . $paymentName]);
            }
            if ($pageControl['controller'] == 'checkout' && $pageControl['action'] == 'confirm' && ((in_array($paymentName, array(
                'novalnetsepa',
                'novalnetinvoice'
            )) && ((($novalnetConfig[$paymentName . '_fraud_module'] && in_array($novalnetConfig[$paymentName . '_fraud_module'], array(
                'pin',
                'sms'
            )) && !$novalnetConfig[$paymentName . '_guarantee_payment'] && ($paymentName != 'novalnetsepa' || ($paymentName == 'novalnetsepa' && $novalnetConfigAsArray['novalnetsepa_shopping_type'] != 'one'))) || ($novalnetConfig[$paymentName . '_guarantee_payment'] && $date_birth_field)))) || ($paymentName == 'novalnetcc' && ($novalnetConfig[$paymentName . '_cc3d'] || Shopware()->Session()->novalnet['one_order_novalnetcc'] == '' && empty(Shopware()->Session()->novalnet['novalnetcc']['cc_hash']))))) {
                $indexController->redirect($errorUrl);
            }
            $view->assign('nn_customer_counry', $nnCustomerData['country']);
            $view->assign('shopVersion', Shopware()->Config()->version);
            
            if ($request->getParam('controller') === 'AboCommerce' && $request->getParam('action') === 'updateAboPayment') {
                $novalnetHelper->createSubscriptionTable();
                if ($request->getParam('novalnet_sepa_iban') != '' || $request->getParam('novalnet_cc_hash') != '') {
                    if ($request->getParam('novalnet_cc_hash') != '') {
                        $param['nn_it']     = 'iframe';
                        $param['pan_hash']  = $request->getParam('novalnet_cc_hash');
                        $param['unique_id'] = $request->getParam('novalnet_cc_uniqueid');
                    }
                    if ($request->getParam('novalnet_sepa_iban') != '') {
                        $param['bank_account_holder'] = $novalnetHelper->getValidHolderName($request->getParam('novalnet_sepa_account_holder'));
                        $param['novalnet_sepa_iban'] = $request->getParam('novalnet_sepa_iban');
                    }
                }
                $novalnetHelper->getSubsConfig($request->getParam('subscriptionId'), $param, $request->getParam('payment'));
            }
        }
    }
    
    /*
     * Used for affiliate management
     *
     * @param Enlight_Event_EventArgs $arguments
     * @return null
     */
    public function onPostDispatchFrontendIndex(Enlight_Event_EventArgs $arguments)
    {
        $indexController = $arguments->getSubject();
        $nnAffId         = trim($indexController->Request()->get('nn_aff_id'));
        
        if ($nnAffId) {
            Shopware()->Session()->nnAffId = $nnAffId;
        }
    }
    
    /**
     * To uninstall the installed Novalnet payment modules
     *
     * @param null
     * @return mixed
     */
    public function uninstall()
    {
        // For lower versions
        $snippetsPath = Shopware()->DocPath() . 'snippets/backend/order/view/novalnet.ini';
        if (file_exists($snippetsPath)) {
            fclose($snippetsPath);
            unlink($snippetsPath);
        }
        Shopware_Plugins_Frontend_NovalPayment_lib_classes_TranslationHelper::dropSnippets();
        Shopware()->Db()->query('DELETE FROM s_novalnet_tariff');
        $this->secureUninstall();
        return array(
            'success' => true,
            'invalidateCache' => array('config','backend','proxy','frontend')
        );
    }
    
    /**
     * To enable all novalnet payments
     * method is called every time the plugin is enabled or the configuration of the plugin is saved.
     *
     * @param null
     * @return boolean
     */
    public function enable()
    {
        $currentLanguage       = strtoupper(Shopware()->Locale()->getLanguage());
        $novalnetLang          = Shopware_Plugins_Frontend_NovalPayment_lib_classes_TranslationHelper::novalnetGetLanguage($currentLanguage);
        $options               = array(
            'label' => $novalnetLang['select_tariff'],
            'itemId' => 'novalnet_tariff',
            'required' => true,
            'position' => 4,
            'emptyText' => $novalnetLang['config_description_novalnet_tariff_val'],
            'description' => $novalnetLang['config_description_novalnet_tariff'],
            'valueField' => 'id',
            'displayField' => 'id',
            'queryMode' => 'remote',
            'queryCaching' => 'false',
            'store' => 'new Ext.data.Store({
                                parent: this,
                                fields: ["id"],
                                proxy : {
                                 type : "ajax",
                                 api : {
                                     read: document.location.pathname + \'NovalnetOrderOperations/getTariff\',
                                 },
                                 reader : {
                                     type : "json",
                                     root : "data"
                                 },
                                 extraParams : {
                                    field_name: me.name
                                 },
                                }
                            })',
            'scope' => \Shopware\Models\Config\Element::SCOPE_SHOP
        );
        $getNovalPaymentTariff = Shopware()->Db()->fetchOne('SELECT options FROM s_core_config_elements where name = ?', array(
            'novalnet_tariff'
        ));
        if (!preg_match('/getTariff/', $getNovalPaymentTariff)) {
            Shopware()->Db()->query('update s_core_config_elements set options = ? where name = ?', array(
                serialize($options),
                'novalnet_tariff'
            ));
        }
        return true;
    }
    
    
    
    /**
     * To disable all novalnet payments method is always called when the plugin is deactivated.
     * Here, you should immediately disable active payment methods.
     *
     * @param null
     * @return boolean
     */
    public function disable()
    {
        foreach (static::novalnetPayments() as $paymentname) {
            $payment = $this->Payments()->findOneBy(array(
                'name' => $paymentname
            ));
            if ($payment) {
                $payment->setActive(false);
            }
        }
        return true;
    }
    
    /**
     * Uninstall the payment plug-in
     *
     * @param null
     * @return boolean
     */
    public function secureUninstall()
    {
        return true;
    }
    
    /**
     * Returns the available Capabilities
     *
     * @param null
     * @return array
     */
    public function getCapabilities()
    {
        return array(
            'install' => true,
            'update' => true,
            'enable' => true,
            'delete' => true
        );
    }
    
    /**
     * Returns the novalnetpayments available
     *
     * @param null
     * @return array
     */
    public static function novalnetPayments()
    {
        $nHelper = new Shopware_Plugins_Frontend_NovalPayment_lib_classes_NovalnetHelper();
        return array_keys($nHelper->getPaymentTypeInfoAry());
    }
    
    /**
     * Returns the informations of plugin as array.
     *
     * @param null
     * @return array
     */
    public function getInfo()
    {
        $langValue    = Shopware()->Locale()->getLanguage();
        $novalnetLang = Shopware_Plugins_Frontend_NovalPayment_lib_classes_TranslationHelper::novalnetGetLanguage($langValue);
        return array(
            'version' => $this->getVersion(),
            'author' => 'Novalnet',
            'label' => $this->getLabel(),
            'link' => $novalnetLang['novalnet_url'],
            'copyright' => 'Copyright © ' . date('Y') . ', Novalnet AG',
            'support' => $novalnetLang['novalnet_url'],
            'description' => $this->getDesc()
            );
    }
    
    /**
     * Returns the novalnet version Desc
     *
     * @param null
     * @return array
     */
    public function getDesc()
    {
        $root_url     = substr($_SERVER['HTTP_REFERER'], '0', strpos($_SERVER['HTTP_REFERER'], 'backend'));
        $langValue    = Shopware()->Locale()->getLanguage();
        $novalnetLang = Shopware_Plugins_Frontend_NovalPayment_lib_classes_TranslationHelper::novalnetGetLanguage($langValue);
        
        return $novalnetLang['plugin_topic1'] . '<br>' . $novalnetLang['plugin_topic1_content'] . '<br>' . $novalnetLang['plugin_topic2'] . '<br>' . $novalnetLang['plugin_topic2_content'] . '<br><br>' . '<img style="width:830px" src="' . $root_url . 'engine/Shopware/Plugins/Community/Frontend/NovalPayment/Views/frontend/_resources/images/setup/' . $langValue . '/projects_tab.png"><br><br>' . '<img style="width:820px" src="' . $root_url . 'engine/Shopware/Plugins/Community/Frontend/NovalPayment/Views/frontend/_resources/images/setup/' . $langValue . '/product_activation_key.png"><br>' . $novalnetLang['plugin_topic3'] . '<br>' . $novalnetLang['plugin_topic3_content'] . '<br><br>' . '<img style="width:830px" src="' . $root_url . 'engine/Shopware/Plugins/Community/Frontend/NovalPayment/Views/frontend/_resources/images/setup/' . $langValue . '/projects_tab.png"><br><br>' . '<img style="width:820px" src="' . $root_url . 'engine/Shopware/Plugins/Community/Frontend/NovalPayment/Views/frontend/_resources/images/setup/' . $langValue . '/system_ip_configuration.png"><br>' . $novalnetLang['plugin_topic4'] . '<br>' . $novalnetLang['plugin_topic4_content'] . '<br><br>' . '<img style="width:830px" src="' . $root_url . 'engine/Shopware/Plugins/Community/Frontend/NovalPayment/Views/frontend/_resources/images/setup/' . $langValue . '/projects_tab.png"><br><br>' . '<img style="width:820px" src="' . $root_url . 'engine/Shopware/Plugins/Community/Frontend/NovalPayment/Views/frontend/_resources/images/setup/' . $langValue . '/vendor_script_configuration.png"><br>' . $novalnetLang['plugin_topic8'] . '<br>' . $novalnetLang['plugin_topic8_content'] . '<br><br>' . '<img style="width:830px" src="' . $root_url . 'engine/Shopware/Plugins/Community/Frontend/NovalPayment/Views/frontend/_resources/images/setup/' . $langValue . '/projects_tab.png"><br><br>' . '<img style="width:820px" src="' . $root_url . 'engine/Shopware/Plugins/Community/Frontend/NovalPayment/Views/frontend/_resources/images/setup/' . $langValue . '/paypal_config_home.png"><br><br>' . '<img style="width:820px" src="' . $root_url . 'engine/Shopware/Plugins/Community/Frontend/NovalPayment/Views/frontend/_resources/images/setup/' . $langValue . '/paypal_config.png"><br>' . $novalnetLang['plugin_topic5'] . '<br>' . $novalnetLang['plugin_topic5_content'] . '<br>' . $novalnetLang['plugin_topic6'] . '<br>' . $novalnetLang['plugin_topic6_content'] . '<br>' . $novalnetLang['plugin_topic7'] . '<br>' . $novalnetLang['plugin_topic7_content'] . '<br>' . '<br><ul>' . '<li style="font-weight: bold; color:#878787;">
                             ' . sprintf($novalnetLang['novalnet_description_0'], 'window.open("https://admin.novalnet.de","' . $novalnetLang['novalnet_support_window_title'] . '","width=auto,height=auto,scrollbars=yes, resizable=yes");') . '</li>' . '</ul>';
    }
    
    /**
     * Returns the novalnet version
     *
     * @param null
     * @return mixed
     */
    public function getVersion()
    {
        $info = json_decode(file_get_contents(__DIR__ . '/plugin.json'), true);
        if ($info) {
            return $info['currentVersion'];
        } else {
            throw new Exception('The plugin has an invalid version file.');
        }
    }
    
    /**
     * Returns the novalnet label
     * @return string
     */
    public function getLabel()
    {
        return 'Novalnet Payment';
    }
    
    /**
     * Event listener method assign language.
     *
     * @param Enlight_Event_EventArgs $args
     * @return null
     */
    public static function novalnetOnPostDispatch(Enlight_Event_EventArgs $args)
    {
        $request = $args->getSubject()->Request();
        $view    = $args->getSubject()->View();
        if ($request->getModuleName() === 'frontend') {
            $view->addTemplateDir(dirname(__FILE__) . '/Views/');
            $view->novalnet_lang = Shopware_Plugins_Frontend_NovalPayment_lib_classes_TranslationHelper::novalnetGetLanguage(Shopware()->Locale()->getLanguage());
        }
    }
    
    /**
     * adds a default aboMail
     */
    public function addMailContent()
    {
        Shopware()->Db()->query('INSERT IGNORE INTO `s_core_config_mails` (`stateId`, `name`, `frommail`, `fromname`, `subject`, `content`, `contentHTML`, `ishtml`, `attachment`, `mailtype`, `context`)
         VALUES (NULL, \'sNOVALNETGUARANTEEMAILEN\', \'{config name=mail}\', \'{config name=shopName}\', \'Your Order {$sOrderNumber} with {config name=shopName} has been confirmed\', \'{include file=\"string:{config name=emailheaderplain}\"}\n        \nDear {$billingaddress.salutation|salutation} {$billingaddress.lastname},\n\nWe are pleased to inform you that your order has been confirmed, kindly refer further details below.\nInformation on your order:\n\nPos.  Art.No.               Description                                      Quantities       Price       Total\n{foreach item=details key=position from=$sOrderDetails}\n{{$position+1}|fill:4}  {$details.ordernumber|fill:20}  {$details.articlename|fill:49}  {$details.quantity|fill:6}  {$details.price|padding:8|currency|unescape:\"htmlall\"}      {$details.amount|padding:8|currency|unescape:\"htmlall\"}\n{/foreach}\n\nShipping costs: {$sShippingCosts|currency|unescape:\"htmlall\"}\nNet total: {$sAmountNet|currency|unescape:\"htmlall\"}\n{if !$sNet}\n{foreach $sTaxRates as $rate => $value}\nplus {$rate|number_format:0}% MwSt. {$value|currency|unescape:\"htmlall\"}\n{/foreach}\nTotal gross: {$sAmount|currency|unescape:\"htmlall\"}\n{/if}\n\nSelected payment type: {$additional.payment.description}\n{$additional.payment.additionaldescription}\n\n\nSelected shipping type: {$sDispatch.name}\n{$sDispatch.description}\n\n{if $sComment}\nYour comment:\n{$sComment}\n{/if}\n\nBilling address:\n{$billingaddress.company}\n{$billingaddress.firstname} {$billingaddress.lastname}\n{$billingaddress.street} {$billingaddress.streetnumber}\n{if {config name=showZipBeforeCity}}{$billingaddress.zipcode} {$billingaddress.city}{else}{$billingaddress.city} {$billingaddress.zipcode}{/if}\n\n{$additional.country.countryname}\n\nShipping address:\n{$shippingaddress.company}\n{$shippingaddress.firstname} {$shippingaddress.lastname}\n{$shippingaddress.street} {$shippingaddress.streetnumber}\n{if {config name=showZipBeforeCity}}{$shippingaddress.zipcode} {$shippingaddress.city}{else}{$shippingaddress.city} {$shippingaddress.zipcode}{/if}\n\n{$additional.countryShipping.countryname}\n\n{if $billingaddress.ustid}\nYour VAT-ID: {$billingaddress.ustid}\nIn case of a successful order and if you are based in one of the EU countries, you will receive your goods exempt from turnover tax.\n{/if}\n\n\nIf you have any questions, do not hesitate to contact us.\n\n{include file=\"string:{config name=emailfooterplain}\"}\', \'<div style=\"font-family:arial; font-size:12px;\">\n            {include file=\"string:{config name=emailheaderhtml}\"}\n            <br/><br/>\n            <p>Dear {$billingaddress.salutation|salutation} {$billingaddress.lastname},<br/>\n                <br/>\n                We are pleased to inform you that your order has been confirmed, kindly refer further details below.<br />\n      <br/>\n          <strong>Information on your order:</strong></p><br/>\n            <table width=\"80%\" border=\"0\" style=\"font-family:Arial, Helvetica, sans-serif; font-size:12px;\">\n                <tr>\n                    <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Pos.</strong></td>\n                    <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Article</strong></td>\n                    <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\">Description</td>\n                    <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Quantities</strong></td>\n                    <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Price</strong></td>\n                    <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Total</strong></td>\n                </tr>\n\n                {foreach item=details key=position from=$sOrderDetails}\n                <tr>\n                    <td style=\"border-bottom:1px solid #cccccc;\">{$position+1|fill:4} </td>\n                    <td style=\"border-bottom:1px solid #cccccc;\">{if $details.image.src.0 && $details.modus == 0}<img style=\"height: 57px;\" height=\"57\" src=\"{$details.image.src.0}\" alt=\"{$details.articlename}\" />{else} {/if}</td>\n                    <td style=\"border-bottom:1px solid #cccccc;\">\n                      {$details.articlename|wordwrap:80|indent:4}<br>\n                      Article-No: {$details.ordernumber|fill:20}\n                    </td>\n                    <td style=\"border-bottom:1px solid #cccccc;\">{$details.quantity|fill:6}</td>\n                    <td style=\"border-bottom:1px solid #cccccc;\">{$details.price|padding:8|currency}</td>\n                    <td style=\"border-bottom:1px solid #cccccc;\">{$details.amount|padding:8|currency}</td>\n                </tr>\n                {/foreach}\n\n            </table>\n        \n            <p>\n                <br/>\n                <br/>\n                Shipping costs: {$sShippingCosts|currency}<br/>\n                Net total: {$sAmountNet|currency}<br/>\n                {if !$sNet}\n                {foreach $sTaxRates as $rate => $value}\n                plus. {$rate|number_format:0}% MwSt. {$value|currency}<br/>\n                {/foreach}\n                <strong>Total gross: {$sAmount|currency}</strong><br/>\n                {/if}\n                <br/>\n                <br/>\n                <strong>Selected payment type:</strong> {$additional.payment.description}<br/>\n                {$additional.payment.additionaldescription}<br/>\n  <br/>\n              <strong>Selected shipping type:</strong> {$sDispatch.name}<br/>\n                {$sDispatch.description}<br/>\n            </p>\n            <p>\n                {if $sComment}\n                <strong>Your comment:</strong><br/>\n                {$sComment}<br/>\n                {/if}\n                <br/>\n                <br/>\n                <strong>Billing address:</strong><br/>\n                {$billingaddress.company}<br/>\n                {$billingaddress.firstname} {$billingaddress.lastname}<br/>\n                {$billingaddress.street} {$billingaddress.streetnumber}<br/>\n                {if {config name=showZipBeforeCity}}{$billingaddress.zipcode} {$billingaddress.city}{else}{$billingaddress.city} {$billingaddress.zipcode}{/if}<br/>\n                {$additional.country.countryname}<br/>\n                <br/>\n                <br/>\n                <strong>Shipping address:</strong><br/>\n                {$shippingaddress.company}<br/>\n                {$shippingaddress.firstname} {$shippingaddress.lastname}<br/>\n                {$shippingaddress.street} {$shippingaddress.streetnumber}<br/>\n                {if {config name=showZipBeforeCity}}{$shippingaddress.zipcode} {$shippingaddress.city}{else}{$shippingaddress.city} {$shippingaddress.zipcode}{/if}<br/>\n                {$additional.countryShipping.countryname}<br/>\n                <br/>\n                {if $billingaddress.ustid}\n                Your VAT-ID: {$billingaddress.ustid}<br/>\n                In case of a successful order and if you are based in one of the EU countries, you will receive your goods exempt from turnover tax.<br/>\n                {/if}\n     <br/>\n     <br/>\n                If you have any questions, do not hesitate to contact us.<br/>\n                {include file=\"string:{config name=emailfooterhtml}\"}\n            </p>\n        </div>\', 1, \'\', 2, \'N;\')');
         
        Shopware()->Db()->query('INSERT IGNORE INTO `s_core_config_mails` (`stateId`, `name`, `frommail`, `fromname`, `subject`, `content`, `contentHTML`, `ishtml`, `attachment`, `mailtype`, `context`)
         VALUES (NULL, \'sNOVALNETGUARANTEEMAILDE\', \'{config name=mail}\', \'{config name=shopName}\', \'Ihre Bestellung {$sOrderNumber} bei {config name=shopName} wurde bestätigt!\', \'{include file=\"string:{config name=emailheaderplain}\"}\n        \nHallo {$billingaddress.salutation|salutation} {$billingaddress.lastname},\n\nWir freuen uns Ihnen mitteilen zu können, dass Ihre Bestellung bestätigt wurde. Bitte beachten Sie weitere Details unten.\nInformationen zu Ihrer Bestellung:\n\nPos.  Art.Nr.               Beschreibung                                      Menge       Preis       Summe \n{foreach item=details key=position from=$sOrderDetails}\n{{$position+1}|fill:4}  {$details.ordernumber|fill:20}  {$details.articlename|fill:49}  {$details.quantity|fill:6}  {$details.price|padding:8|currency|unescape:\"htmlall\"}      {$details.amount|padding:8|currency|unescape:\"htmlall\"}\n{/foreach}\n\nVersandkosten: {$sShippingCosts|currency|unescape:\"htmlall\"}\OnGesamtkosten Netto: {$sAmountNet|currency|unescape:\"htmlall\"}\n{if !$sNet}\n{foreach $sTaxRates as $rate => $value}\nzzgl. {$rate|number_format:0}% MwSt. {$value|currency|unescape:\"htmlall\"}\n{/foreach}\nGesamtkosten Brutto: {$sAmount|currency|unescape:\"htmlall\"}\n{/if}\n\nGewählte Zahlungsart: {$additional.payment.description}\n{$additional.payment.additionaldescription}\n\n\nGewählte Versandart: {$sDispatch.name}\n{$sDispatch.description}\n\n{if $sComment}\nIhr Kommentar:\n{$sComment}\n{/if}\n\nRechnungsadresse:\n{$billingaddress.company}\n{$billingaddress.firstname} {$billingaddress.lastname}\n{$billingaddress.street} {$billingaddress.streetnumber}\n{if {config name=showZipBeforeCity}}{$billingaddress.zipcode} {$billingaddress.city}{else}{$billingaddress.city} {$billingaddress.zipcode}{/if}\n\n{$additional.country.countryname}\n\nLieferadresse:\n{$shippingaddress.company}\n{$shippingaddress.firstname} {$shippingaddress.lastname}\n{$shippingaddress.street} {$shippingaddress.streetnumber}\n{if {config name=showZipBeforeCity}}{$shippingaddress.zipcode} {$shippingaddress.city}{else}{$shippingaddress.city} {$shippingaddress.zipcode}{/if}\n\n{$additional.countryShipping.countryname}\n\n{if $billingaddress.ustid}\nIhre Umsatzsteuer-ID: {$billingaddress.ustid}\nBei erfolgreicher Prüfung und sofern Sie aus dem EU-Ausland\nbestellen, erhalten Sie Ihre Ware umsatzsteuerbefreit.\n{/if}\n\n\nFür Rückfragen stehen wir Ihnen jederzeit gerne zur Verfügung.\n\n{include file=\"string:{config name=emailfooterplain}\"}\', \'<div style=\"font-family:arial; font-size:12px;\">\n            {include file=\"string:{config name=emailheaderhtml}\"}\n            <br/><br/>\n            <p>Hallo {$billingaddress.salutation|salutation} {$billingaddress.lastname},<br/>\n                <br/>\n   Wir freuen uns Ihnen mitteilen zu können, dass Ihre Bestellung bestätigt wurde. Bitte beachten Sie weitere Details unten.<br />\n      <br/>\n          <strong>Informationen zu Ihrer Bestellung:</strong></p><br/>\n            <table width=\"80%\" border=\"0\" style=\"font-family:Arial, Helvetica, sans-serif; font-size:12px;\">\n                <tr>\n                    <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Pos.</strong></td>\n                    <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Artikel</strong></td>\n                    <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\">Bezeichnung</td>\n                    <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Menge</strong></td>\n                    <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Preis</strong></td>\n                    <td bgcolor=\"#F7F7F2\" style=\"border-bottom:1px solid #cccccc;\"><strong>Summe</strong></td>\n                </tr>\n\n                {foreach item=details key=position from=$sOrderDetails}\n                <tr>\n                    <td style=\"border-bottom:1px solid #cccccc;\">{$position+1|fill:4} </td>\n                    <td style=\"border-bottom:1px solid #cccccc;\">{if $details.image.src.0 && $details.modus == 0}<img style=\"height: 57px;\" height=\"57\" src=\"{$details.image.src.0}\" alt=\"{$details.articlename}\" />{else} {/if}</td>\n                    <td style=\"border-bottom:1px solid #cccccc;\">\n                      {$details.articlename|wordwrap:80|indent:4}<br>\n                      Artikel-Nr: {$details.ordernumber|fill:20}\n                    </td>\n                    <td style=\"border-bottom:1px solid #cccccc;\">{$details.quantity|fill:6}</td>\n                    <td style=\"border-bottom:1px solid #cccccc;\">{$details.price|padding:8|currency}</td>\n                    <td style=\"border-bottom:1px solid #cccccc;\">{$details.amount|padding:8|currency}</td>\n                </tr>\n                {/foreach}\n\n            </table>\n        \n            <p>\n                <br/>\n                <br/>\n                Versandkosten: {$sShippingCosts|currency}<br/>\n                Gesamtkosten Netto: {$sAmountNet|currency}<br/>\n                {if !$sNet}\n                {foreach $sTaxRates as $rate => $value}\n                zzgl. {$rate|number_format:0}% MwSt. {$value|currency}<br/>\n                {/foreach}\n                <strong>Gesamtkosten Brutto: {$sAmount|currency}</strong><br/>\n                {/if}\n                <br/>\n                <br/>\n                <strong>Gewählte Zahlungsart:</strong> {$additional.payment.description}<br/>\n                {$additional.payment.additionaldescription}<br/>\n  <br/>\n              <strong>Gewählte Versandart:</strong> {$sDispatch.name}<br/>\n                {$sDispatch.description}<br/>\n            </p>\n            <p>\n                {if $sComment}\n                <strong>Ihr Kommentar:</strong><br/>\n                {$sComment}<br/>\n                {/if}\n                <br/>\n                <br/>\n                <strong>Rechnungsadresse:</strong><br/>\n                {$billingaddress.company}<br/>\n                {$billingaddress.firstname} {$billingaddress.lastname}<br/>\n                {$billingaddress.street} {$billingaddress.streetnumber}<br/>\n                {if {config name=showZipBeforeCity}}{$billingaddress.zipcode} {$billingaddress.city}{else}{$billingaddress.city} {$billingaddress.zipcode}{/if}<br/>\n                {$additional.country.countryname}<br/>\n                <br/>\n                <br/>\n                <strong>Lieferadresse:</strong><br/>\n                {$shippingaddress.company}<br/>\n                {$shippingaddress.firstname} {$shippingaddress.lastname}<br/>\n                {$shippingaddress.street} {$shippingaddress.streetnumber}<br/>\n                {if {config name=showZipBeforeCity}}{$shippingaddress.zipcode} {$shippingaddress.city}{else}{$shippingaddress.city} {$shippingaddress.zipcode}{/if}<br/>\n                {$additional.countryShipping.countryname}<br/>\n                <br/>\n                {if $billingaddress.ustid}\n                Ihre Umsatzsteuer-ID: {$billingaddress.ustid}<br/>\n                Bei erfolgreicher Prüfung und sofern Sie aus dem EU-Ausland<br/>\n                bestellen, erhalten Sie Ihre Ware umsatzsteuerbefreit.<br/>\n                {/if}\n     <br/>\n       <br/>\n                Für Rückfragen stehen wir Ihnen jederzeit gerne zur Verfügung.<br/>\n                {include file=\"string:{config name=emailfooterhtml}\"}\n            </p>\n        </div>\', 1, \'\', 2, \'N;\')');
    }
    
    /**
     * set the session ID for chrome samesite issue.
     *
     * @param Enlight_Event_EventArgs $arguments
     * @return null
     */
    public function onReturnStartup(Enlight_Event_EventArgs $arguments)
    {
        $userAgentObj = new Zend_Http_UserAgent();
        $userAgent = $userAgentObj->getUserAgent();
        $pattern = '#(?:Chrome|CriOS)/(\d+)#';

        if (preg_match($pattern, $userAgent, $matchedAgent)) {
            if (!empty($matchedAgent[1]) && $matchedAgent[1] >= 80) {
                $nnSid = (Shopware()->Front()->Request()->getPost('inputval5')
                    ? Shopware()->Front()->Request()->getPost('inputval5')
                    : (Shopware()->Front()->Request()->getPost('nn_sid')
                        ? Shopware()->Front()->Request()->getPost('nn_sid') : ''));

                if (!empty($nnSid)) {
                    Zend_Session::setId($nnSid);
                }
            }
        }
    }
}
