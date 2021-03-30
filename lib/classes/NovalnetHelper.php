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

class Shopware_Plugins_Frontend_NovalPayment_lib_classes_NovalnetHelper
{
    private $novalnetLang = array();

    /**
     * Constructor
     *
     * @param null
     * @return null
     */
    public function __construct()
    {
        $this->novalnetLang = Shopware_Plugins_Frontend_NovalPayment_lib_classes_TranslationHelper::novalnetGetLanguage(Shopware()->Locale()->getLanguage());
    }

    /**
     * Encode the config parameters before transaction.
     *
     * @param $fields
     * @param $key
     * @param $toBeEncoded
     * @return boolean
     */
    public function encodeParams(&$fields, $key, $toBeEncoded = array())
    {
        foreach ($toBeEncoded as $value) {
            try {
                $fields[$value] = htmlentities(base64_encode(openssl_encrypt($fields[$value], "aes-256-cbc", $key, true, $fields['uniqid'])));
            } catch (Exception $e) {
                throw new Enlight_Exception($e);
            }
        }
    }

    /**
     * Decode the basic parameters after transaction.
     *
     * @param $fields
     * @param $key
     * @param $toBeDecoded
     * @return boolean
     */
    public function decodeParams(&$fields, $key, $toBeDecoded = array())
    {
        foreach ($toBeDecoded as $value) {
            try {
                $fields[$value] = openssl_decrypt(base64_decode($fields[$value]), "aes-256-cbc", $key, true, $fields['uniqid']);
            } catch (Exception $e) {
                throw new Enlight_Exception($e);
            }
        }
    }

    /**
     * Generate the 32 digit hash code
     *
     * @param $data
     * @param $key
     * @return string
     */
    public function generateHash($data, $key)
    {
        $str = '';
        $hashFields = array(
            'auth_code',
            'product',
            'tariff',
            'amount',
            'test_mode',
            'uniqid'
        );
        foreach ($hashFields as $value) {
            $str .= $data[$value];
        }
        return hash('sha256', $str.strrev($key));
    }

    /**
     * Send order confirmation email for merchant/enduser
     *
     * @param array $variables
     * @param boolean $guaranteemail
     *
     * @return null
     */
    public function sendNovalnetOrderMail($variables, $guaranteemail = false)
    {
        $variables = Shopware()->Events()->filter('Shopware_Modules_Order_SendMail_FilterVariables', $variables, array(
            'subject' => $this
        ));

        $context = $variables;
        $mail    = null;
        if ($event = Shopware()->Events()->notifyUntil('Shopware_Modules_Order_SendMail_Create', array(
            'subject' => $this,
            'context' => $context,
            'variables' => $variables
        ))) {
            $mail = $event->getReturn();
        }

        if ($guaranteemail) {
            $localeLang = strtoupper(Shopware()->Locale()->getLanguage()) == 'de' ? 'DE' : 'EN';
            $mail = Shopware()->TemplateMail()->createMail('sNOVALNETGUARANTEEMAIL' . $localeLang, $context);
        } else {
            $mail = Shopware()->TemplateMail()->createMail('sORDER', $context);
        }
        
        $mail->addTo($context['additional']['user']['email']);
        if (!Shopware()->Config()->get('sNO_ORDER_MAIL')) {
            $mail->addBcc(Shopware()->Config()->get('sMAIL'));
        }

        $mail = Shopware()->Events()->filter('Shopware_Modules_Order_SendMail_Filter', $mail, array(
            'subject' => $this,
            'context' => $context,
            'variables' => $variables
        ));
        if (!($mail instanceof \Zend_Mail)) {
            return;
        }

        Shopware()->Events()->notify('Shopware_Modules_Order_SendMail_BeforeSend', array(
            'subject' => $this,
            'mail' => $mail,
            'context' => $context,
            'variables' => $variables
        ));
        $shouldSendMail = Shopware()->Events()->notifyUntil('Shopware_Modules_Order_SendMail_Send', array(
            'subject' => $this,
            'mail' => $mail,
            'context' => $context,
            'variables' => $variables
        ));
        $shouldSendMail = ($shouldSendMail) ? $shouldSendMail : "1";
        if ($shouldSendMail && Shopware()->Config()->get('sendOrderMail')) {
            $mail->send();
        }
    }

    /**
     * Request to payment gateway action
     *
     * @params $params
     * @params $url
     * @params $xmlRequest
     * @return array
     */
    public function curlCallRequest($params, $url, $xmlRequest = false)
    {
        if ($xmlRequest) {
            $xmlRequestParams = '<?xml version="1.0" encoding="UTF-8"?>';
            $xmlRequestParams .= '<nnxml><info_request>';
            foreach ($params as $key => $value) {
                $xmlRequestParams .= "<$key>$value</$key>";
            }
            $xmlRequestParams .= '</info_request></nnxml>';
            $params = $xmlRequestParams;
        }
        if (version_compare(Shopware()->Config()->version, '5.6.0', '>=')) {
            $novalnetConfig  = Shopware()->Container()->get('shopware.plugin.config_reader')->getByPluginName('NovalPayment');
        } else {
            $novalnetConfig  = Shopware()->Plugins()->Frontend()->NovalPayment()->Config();
        }
        $curlCallTimeout = trim($novalnetConfig->novalnet_curl_timeout);
        $curlCallTimeout = ($this->isDigits($curlCallTimeout)) ? $curlCallTimeout : 240;
        $novalnetProxy   = trim($novalnetConfig->novalnet_proxy);
        $client          = new Zend_Http_Client($url);
        $client->setAdapter('Zend_Http_Client_Adapter_Curl');
        $client->getAdapter()->setCurlOption(CURLOPT_SSL_VERIFYHOST, 0);
        $client->getAdapter()->setCurlOption(CURLOPT_SSL_VERIFYPEER, 0);
        $client->getAdapter()->setCurlOption(CURLOPT_CONNECTTIMEOUT, $curlCallTimeout);
        if ($novalnetProxy) {
            $client->getAdapter()->setCurlOption(CURLOPT_PROXY, trim($novalnetProxy));
        }
        if ($xmlRequest) {
            $response = $client->setRawData($params, 'text/xml')->request('POST');
        } else {
            $client->setParameterPost($params);
            $client->setMethod(Zend_Http_Client::POST);
            $response = $client->request();
        }
        return $response;
    }

    /**
     * Get the customer remote address
     *
     * @param string $type
     * @return string
     */
    public function getIp($type = 'REMOTE_ADDR')
    {
        // Check to determine the IP address type
        if ($type == 'SERVER_ADDR') {
            if (empty($_SERVER['SERVER_ADDR'])) {
                // Handled for IIS server
                $ipAddress = gethostbyname($_SERVER['SERVER_NAME']);
            } else {
                $ipAddress = $_SERVER['SERVER_ADDR'];
            }
        } else { // For remote address
            $ipAddress = $this->getRemoteAddress();
        }
        
        return (filter_var($ipAddress, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) ? '127.0.0.1' : $ipAddress;
    }

    /**
     * Get the  remote address
     *
     * @param null
     * @return string
     */
    public function getRemoteAddress()
    {
        $ipKeys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_REAL_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    // trim for safety measures
                    return trim($ip);
                }
            }
        }
    }

    /**
     * Get the server status text
     *
     * @param $response
     * @param $message
     * @return string
     */
    public function getStatusDesc($response, $message = null)
    {
        return $this->setHtmlEntity((isset($response['status_desc'])) ? $response['status_desc'] : (isset($response['status_text']) ? $response['status_text'] : (isset($response['config_result']) ? $response['config_result'] : $message)), 'decode');
    }

    /**
     * Get formatted comments prepayment
     *
     * @param $transactionInfo
     * @param $paymentName
     * @param $tid
     * @param $testmode
     * @return string
     */
    public function removePrepaymentNovalnetBankInfo($transactionInfo, $paymentName, $tid, $testmode)
    {
        $finalComments = $transactionInfo['novalnet_payment_transdetails_info'] . "<br>";
        $finalComments .= $transactionInfo['payment_name_' . $paymentName] . "<br>";
        $finalComments .= $transactionInfo['novalnet_tid_label'] . ": " . $tid . "<br>";
        if ($testmode) {
            $finalComments .= $transactionInfo['novalnet_message_test_order'] . "<br>";
        }
        return $finalComments;
    }

    /**
     * Check the integer value
     *
     * @param $element
     * @return boolean
     */
    public function isDigits($element)
    {
        $validator = new Zend_Validate_Digits();
        return $validator->isValid($element);
    }

    /**
     * Check the successfull transaction of the order
     *
     * @param $novalnetResult
     * @return boolean
     */
    public function isSuccessTransaction($novalnetResult)
    {
        return ((isset($novalnetResult['status']) && $novalnetResult['status'] == '100') || (in_array($novalnetResult['tid_status'], array(
            '90',
            '86',
            '85'
        ))));
    }

    /**
     * Set the html entity for string
     *
     * @param $str
     * @param $type
     * @return string
     */
    public function setHtmlEntity($str, $type = 'encode')
    {
        if (!is_string($str)) {
            throw new Enlight_Exception('Invalid encoding specified');
        }
        return ($type == 'encode') ? htmlentities($str) : html_entity_decode($str, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate the email
     *
     * @param $email
     * @return boolean
     */
    public function validateEmail($email)
    {
        $validator = new Zend_Validate_EmailAddress();
        if ($validator->isValid($email)) {
            return true;
        }
        return false;
    }

    /**
     * Get random string to generate unique id
     *
     * @param null
     * @return string
     */
    public function randomString()
    {
        return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 30);
    }

    /**
     * Returns the customer address information
     *
     * @param array $datas
     * @return array|null
     */
    public function getPaymentCustomerAddressInfo($datas)
    {
        if (!empty($datas)) {
            $userData['email']       = $datas['additional']['user']['email'];
            $userData['first_name']  = $datas['billingaddress']['firstname'];
            $userData['last_name']   = $datas['billingaddress']['lastname'];
            $userData['customer_no'] = ($datas['billingaddress']['customernumber']) ? $datas['billingaddress']['customernumber'] : $datas['additional']['user']['customernumber'];
            $userData['street']      = $datas['billingaddress']['street'];
            $userData['city']        = $datas['billingaddress']['city'];
            $userData['country']     = $datas['additional']['country']['countryiso'];
            $userData['zip']         = $datas['billingaddress']['zipcode'];
            if (!empty($datas['billingaddress']['phone'])) {
                $userData['tel'] = $datas['billingaddress']['phone'];
            }
            if (!empty($datas['billingaddress']['fax'])) {
                $userData['fax'] = $datas['billingaddress']['fax'];
            }
            $companyvalue = ($datas['billingaddress']['company']) ? $datas['billingaddress']['company'] : ($datas['additional']['user']['company'] ? $datas['additional']['user']['company'] : '');
            if (!empty($companyvalue)) {
                $userData['company'] = $companyvalue;
            }
            $vatId = ($datas['billingaddress']['vatId']) ? $datas['billingaddress']['vatId'] : '';
            if (!empty($vatId)) {
                $userData['vat_id'] = $vatId;
            }
            $userData = array_map('trim', $userData);
            return $userData;
        }

        return null;
    }

    /**
     * Log the transaction information
     *
     * @param array $datas
     * @param boolean $updateOrderAttr
     * @return boolean
     */
    public function logInitialTransaction($datas, $updateOrderAttr = true)
    {
        $tableValues = array(
            'tid' => $datas['tid'],
            'tariff_id' => $datas['tariff_id'],
            'subs_id' => (int) $datas['subs_id'],
            'payment_id' => $datas['payment_id'],
            'payment_type' => $datas['payment_type'],
            'payment_key' => $datas['payment_key'],
            'amount' => (!empty($datas['amount'])) ? $datas['amount'] : 0,
            'currency' => $datas['currency'],
            'status' => $datas['status'],
            'gateway_status' => $datas['gateway_status'],
            'order_no' => $datas['order_no'],
            'date' => $datas['date'],
            'test_mode' => ($datas['test_mode']) ? 1 : 0,
            'additional_note' => $datas['additional_note'],
            'customer_id' => $datas['customer_id'],
            'lang' => $datas['lang'],
            'is_ref_order' => $datas['is_ref_order'],
            'configuration_details' => $datas['configuration_details']
        );
        Shopware()->Db()->insert('s_novalnet_transaction_detail', $tableValues);
        $sOrder = Shopware()->Db()->fetchRow('SELECT id,temporaryID FROM s_order WHERE ordernumber = ?', array(
            $datas['order_no']
        ));
        if ($updateOrderAttr) {
            $sOrderAttributes['novalnet_payment_tid']            = $datas['tid'];
            $sOrderAttributes['novalnet_payment_gateway_status'] = $datas['gateway_status'];
            if (($datas['payment_type'] == 'novalnetpaypal' && $datas['gateway_status'] == 90)) {
                $sOrderAttributes['novalnet_payment_order_amount'] = 0;
            } else {
                $sOrderAttributes['novalnet_payment_order_amount'] = $datas['amount'];
            }
            if ($datas['due_date']) {
                $sOrderAttributes['novalnet_payment_due_date'] = $datas['due_date'];
            }
            $sOrderAttributes['novalnet_payment_order_amount']   = $datas['amount'];
            $sOrderAttributes['novalnet_payment_current_amount'] = $datas['amount'];
            $sOrderAttributes['novalnet_payment_type']           = $datas['payment_type'];
            $this->novalnetDbUpdate('s_order_attributes', $sOrderAttributes, 'orderID="' . $sOrder['id'] . '"');
        }
        if (!empty($datas['tid']) && !empty($sOrder['temporaryID']) && $datas['tid'] != $sOrder['temporaryID']) {
            Shopware()->Db()->query('update s_order set temporaryID=? where ordernumber=?', array(
                $datas['tid'],
                $datas['order_no']
            ));
        }
        return true;
    }

    /**
     * Update the product reduce in core table
     *
     * @param array $novalnetResponse
     * @return null
     */
    public function updateStockReduce($novalnetResponse)
    {
        $id              = Shopware()->Db()->fetchOne('SELECT id FROM s_order WHERE  ordernumber = ?', array(
            $novalnetResponse['order_no']
        ));
        $s_order_details = Shopware()->Db()->fetchAll('SELECT * FROM s_order_details   WHERE  orderID = ?', array(
            $id
        ));
        for ($i = 0; $i < count($s_order_details); $i++) {
            $stockStore                    = Shopware()->Db()->fetchAll('SELECT * FROM s_articles_details   WHERE  articleID = ?', array(
                $s_order_details[$i]['articleID']
            ));
            $s_articles_details['instock'] = $stockStore[0]['instock'] - $s_order_details[$i]['quantity'];
            $this->novalnetDbUpdate('s_articles_details ', $s_articles_details, 'articleID="' . $s_order_details[$i]['articleID'] . '"');
        }
    }

    /**
     * Update the product restore in core table
     *
     * @param array $novalnetResponse
     * @return null
     */
    public function updateStockRestore($novalnetResponse)
    {
        $id              = Shopware()->Db()->fetchOne('SELECT id FROM s_order WHERE  ordernumber = ?', array(
            $novalnetResponse['order_no']
        ));
        $s_order_details = Shopware()->Db()->fetchAll('SELECT * FROM s_order_details   WHERE  orderID = ?', array(
            $id
        ));
        for ($i = 0; $i < count($s_order_details); $i++) {
            $stockStore                    = Shopware()->Db()->fetchAll('SELECT * FROM s_articles_details   WHERE  articleID = ?', array(
                $s_order_details[$i]['articleID']
            ));
            $s_articles_details['instock'] = $stockStore[0]['instock'] + $s_order_details[$i]['quantity'];
            $this->novalnetDbUpdate('s_articles_details ', $s_articles_details, 'articleID="' . $s_order_details[$i]['articleID'] . '"');
        }
        $orderStatus = Shopware()->Db()->fetchAll('SELECT * FROM  s_core_states ');
        Shopware()->Modules()->Order()->setPaymentStatus((int) $id, (int) $orderStatus['28']['id'], false);
    }

    /**
     * Log novalnet prepayment and invoice transaction's account details in novalnet_preinvoice_transaction_detail table
     *
     * @param $datas
     * @return boolean
     */
    public function logPrepaymentInvoiceTransAccountInfo($datas)
    {
        Shopware()->Db()->insert('s_novalnet_preinvoice_transaction_detail', array(
            'order_no' => (isset($datas['order_no']) ? $datas['order_no'] : '') ,
            'tid' => $datas['tid'],
            'account_holder' => $datas['account_holder'],
            'account_number' => $datas['account_number'],
            'bank_code' => $datas['bank_code'],
            'bank_name' => $datas['bank_name'],
            'bank_city' => $datas['bank_city'],
            'amount' => $datas['amount'] * 100, // Convert into cents
            'currency' => $datas['currency'],
            'bank_iban' => $datas['bank_iban'],
            'bank_bic' => $datas['bank_bic'],
            'due_date' => $datas['due_date'],
            'date' => date('Y-m-d H:i:s'),
            'test_mode' => (!empty($datas['test_mode']) ? 1 : 0)
        ));
        return true;
    }

    /**
     * To get config details
     *
     * @param $Config
     * @return array|null
     */
    public function getNovalConfigDetails($Config)
    {
        if ($Config) {
            $nnConfig = Shopware_Plugins_Frontend_NovalPayment_lib_classes_BackendConfig::getConfigFields();
            foreach ($nnConfig as $k => $v) {
                if ($k == 'novalnet_tariff') {
                    $tariffContent = $Config[$k];
                    preg_match('/\(([^\)]*)\)/', $tariffContent, $match);
                    $tariff_val                 = explode('-', $match[1]);
                    $finalConfig['tariff_type'] = $tariff_val[1];
                    $finalConfig[$k]            = $tariff_val[0];
                } else {
                    $finalConfig[$k] = $Config[$k];
                }
            }
            return array_map('trim', $finalConfig);
        }
        return null;
    }

    /**
     * To get invoice due date details
     *
     * @param $date String
     * @return mixed
     */
    public function getInvoiceDueDate($date)
    {
        return ($this->isDigits($date)) ? date('Y-m-d', strtotime('+' . max(0, intval($date)) . ' days')) : false;
    }

    /**
     * Return the customer order details
     *
     * @param $paymentCode
     * @param $customerNo
     * @return Array
     */
    public function getCustomerOrders($paymentCode, $customerNo)
    {
        $qry = '';
        if ($paymentCode == 'novalnetsepa') {
            $qry .= 'AND configuration_details LIKE "%iban%"';
        }
        return Shopware()->Db()->fetchRow("SELECT payment_type,tid,configuration_details FROM
            s_novalnet_transaction_detail WHERE customer_id = ? AND payment_type = ?
             AND is_ref_order = '0' AND configuration_details LIKE '%create_payment_ref%' $qry ORDER BY id DESC LIMIT 1", array(
            $customerNo,
            $paymentCode
        ));
    }

    /**
     * To display the payment in frontend based on configurations
     *
     * @param $sPaymentMeans
     * @param $paymentCode
     * @return array
     */
    public function disableFrontendPayments($sPaymentMeans, $paymentCode)
    {
        foreach ($sPaymentMeans as $sPaymentId => $sPaymentValue) {
            if (array_search($paymentCode, $sPaymentValue)) {
                unset($sPaymentMeans[$sPaymentId]);
            }
        }
        return $sPaymentMeans;
    }

    /**
     * To validate the configurations
     *
     * @param $configuration
     * @param $validateAllConfiguration
     * @return boolean
     */
    public function validateBackendConfig($configuration, $validateAllConfiguration = true)
    {
        $pattern = "/^\d+\|\d+\|\d+\|\w+\|\w+\$/";
        $value   = $configuration['novalnet_vendor'] . '|' . $configuration['novalnet_product'] . '|' . $configuration['novalnet_tariff'] . '|' . $configuration['novalnet_auth_code'] . '|' . $configuration['novalnet_password'];
        preg_match($pattern, $value, $match);
        if (empty($match[0])) {
            return false;
        }
        if ($validateAllConfiguration) {
            $pattern = "/^(|\d+)\|(|\d+)\|(|\d+)$/";
            $value   = $configuration['novalnet_manual_check_limit'] . '|' . $configuration['novalnet_curl_timeout'] . '|' . $configuration['novalnet_tariff_period2_amount'];
            preg_match($pattern, $value, $match);
            if (empty($match[0])) {
                return false;
            }
            if ($this->isDigits($configuration['novalnet_tariff_period2_amount']) && empty($configuration['novalnet_tariff_period2'])) {
                return false;
            }
            if ((empty($configuration['novalnet_tariff_period2_amount']) || !$this->isDigits($configuration['novalnet_tariff_period2_amount'])) && !empty($configuration['novalnet_tariff_period2'])) {
                return false;
            }
        }
        return true;
    }

    /**
     * Novalnet individual payment information
     *
     * @param null
     * @return array
     */
    public function getPaymentTypeInfoAry()
    {
        return array(
            'novalnetcc' => array(
                'key' => 6,
                'payment_type' => 'CREDITCARD'
            ),
            'novalnetpaypal' => array(
                'key' => 34,
                'payment_type' => 'PAYPAL'
            ),
            'novalnetsepa' => array(
                'key' => 37,
                'payment_type' => 'DIRECT_DEBIT_SEPA'
            ),
            'novalnetinstant' => array(
                'key' => 33,
                'payment_type' => 'ONLINE_TRANSFER'
            ),
            'novalnetideal' => array(
                'key' => 49,
                'payment_type' => 'IDEAL'
            ),
            'novalneteps' => array(
                'key' => 50,
                'payment_type' => 'EPS'
            ),
            'novalnetgiropay' => array(
                'key' => 69,
                'payment_type' => 'GIROPAY'
            ),
            'novalnetinvoice' => array(
                'key' => 27,
                'payment_type' => 'INVOICE'
            ),
            'novalnetprepayment' => array(
                'key' => 27,
                'payment_type' => 'PREPAYMENT'
            ),
            'novalnetcashpayment' => array(
                'key' => 59,
                'payment_type' => 'CASHPAYMENT'
            ),
            'novalnetprzelewy24' => array(
                'key' => 78,
                'payment_type' => 'PRZELEWY24'
            )
        );
    }

    /**
     * Novalnet payment key
     *
     * @param $paymentName
     * @return integer|null
     */
    public function getPaymentKey($paymentName)
    {
        if ($paymentName) {
            $paymentTypesInfo = $this->getPaymentTypeInfoAry();
            return $paymentTypesInfo[$paymentName]['key'];
        }
        return null;
    }

    /**
     * Novalnet payment type
     *
     * @param $paymentName
     * @return string|null
     */
    public function getPaymentType($paymentName)
    {
        if ($paymentName) {
            $paymentTypesInfo = $this->getPaymentTypeInfoAry();
            return $paymentTypesInfo[$paymentName]['payment_type'];
        }
        return null;
    }

    /**
     * Update the s_order core table
     *
     * @param integer $orderId
     * @param integer $paymentStatus
     * @param string $message
     * @param integer $$id
     * @return boolean
     */
    public function updateOrdertable($orderId, $paymentStatus, $message, $id)
    {
        if ($paymentStatus) {
            Shopware()->Modules()->Order()->setPaymentStatus($id, (int) $paymentStatus, false);
        }
        Shopware()->Db()->query('update s_order set customercomment = CONCAT(customercomment,?) where ordernumber = ?', array(
            $message,
            $orderId
        ));
        return true;
    }

    /**
     * update real time novalnet transactions in novalnet_transaction_detail table
     *
     * @param $tid
     * @param $response
     * @param $preinvoiceTableUpdate
     * @return boolean
     */
    public function updateLiveNovalTransStatus($tid, $response, $preinvoiceTableUpdate = false)
    {
        if ($tid) {
            $param['gateway_status'] = ((isset($response['tid_status'])) ? $response['tid_status'] : 0);
            $this->novalnetDbUpdate('s_novalnet_transaction_detail', $param, 'tid=' . $tid);
            if ($preinvoiceTableUpdate) {
                $this->novalnetDbUpdate('s_novalnet_preinvoice_transaction_detail', array(
                    'amount' => $response['amount']
                ), 'tid=' . $tid);
            }
            // Update the s_order_attributes to maintain the current order details
            $sOrderAttributes['novalnet_payment_tid']            = $tid;
            $sOrderAttributes['novalnet_payment_gateway_status'] = $response['tid_status'];
            $orderID                                             = Shopware()->Db()->fetchOne('SELECT id FROM s_order WHERE ordernumber = ?', array(
                $response['order_no']
            ));
            $this->novalnetDbUpdate('s_order_attributes', $sOrderAttributes, 'orderID="' . $orderID . '"');
        }
        return true;
    }

    /**
     * Get the order payment status from database
     *
     * @param $orderId
     * @return integer|null
     */
    public function getOrderStatus($orderId)
    {
        if (empty($orderId)) {
            return null;
        }
        return Shopware()->Db()->fetchOne('SELECT cleared FROM s_order WHERE ordernumber = ?', array(
            $orderId
        ));
    }

    /**
     * Get the order language from database
     *
     * @param integer $orderId
     * @return integer|null
     */
    public function getOrderLanguageCode($orderNumber)
    {
        if (empty($orderNumber)) {
            return null;
        }
        $lang = Shopware()->Db()->fetchOne('SELECT locale FROM s_core_locales WHERE id = (SELECT language FROM
             s_order WHERE ordernumber = ?)', array(
            $orderNumber
        ));
        $lang = str_split($lang, 2);
        return $lang[0];
    }

    /**
     * Get the order paid amount from database
     *
     * @param integer $tid
     * @return integer|null
     */
    public function getOrderPaidAmount($tid)
    {
        if (empty($tid)) {
            return null;
        }
        return Shopware()->Db()->fetchOne('SELECT sum(amount) FROM s_novalnet_callback_history
            WHERE org_tid = ?', array(
            $tid
        ));
    }

    /**
     * Update prepayment/Invoice transaction order reference
     *
     * @param $datas
     * @param $intial
     * @return boolean
     */
    public function UpdatePrepaymentInvoiceTransOrderRef($datas = array(), $intial = false)
    {
        $tid = $datas['tid'];
        if (!empty($datas['order_id'])) {
            $param['order_no'] = $datas['order_id'];
        }
        if (!empty($datas['amount'])) {
            $param['amount'] = $datas['amount'];
        }
        if (!empty($datas['due_date'])) {
            $sOrderAttributes['novalnet_payment_due_date'] = $param['due_date'] = date('Y-m-d', strtotime($datas['due_date']));
        }
        $this->novalnetDbUpdate('s_novalnet_preinvoice_transaction_detail', $param, 'tid=' . $tid);
        if ($intial) {
            $sOrderAttributes['novalnet_payment_paid_amount'] = 0;
        }
        if ($sOrderAttributes) {
            $this->novalnetDbUpdate('s_order_attributes', $sOrderAttributes, 'novalnet_payment_tid=' . $tid);
        }
        return true;
    }

    /**
     * Prepare comments
     *
     * @param $paymentShortName
     * @param $response
     * @param $currency
     * @param $testMode
     * @param $orderNo
     * @param $productId
     * @param $lang
     * @param $configuration
     * @return string
     */
    public function prepareComments($paymentShortName, $response, $currency, $testMode, $orderNo, $productId, $lang, $configuration = array())
    {
        $reference = '';
        $newLine   = '<br />';
        if ($lang) {
            $this->novalnetLang = Shopware_Plugins_Frontend_NovalPayment_lib_classes_TranslationHelper::novalnetGetLanguage($lang);
        } else {
            $lang = Shopware()->Locale()->getLanguage();
        }
        $note = $this->novalnetLang['novalnet_payment_transdetails_info'] . $newLine;
        $note .= (($_SESSION['Shopware']['sOrderVariables']['sPayment']['description']) ? $_SESSION['Shopware']['sOrderVariables']['sPayment']['description'] : $this->novalnetLang['payment_name_' . $paymentShortName]) . $newLine;
        if (in_array($response['payment_id'], array(41,40))) {
            $note .= $this->novalnetLang['guarantee_text'] . $newLine ;
        }
        switch ($paymentShortName) {
            case 'novalnetinvoice':
            case 'novalnetprepayment':
                $note .= $this->novalnetLang['novalnet_tid_label'] . ':  ' . $response['tid'] . $newLine;
                if ($testMode) {
                    $note .= $this->novalnetLang['novalnet_message_test_order'] . $newLine;
                }
                
                if ($response['tid_status'] != '75') {
                    $note .= $this->novalnetLang['novalnet_payment_mail_invoice'] . $newLine;
                    if (!empty($response['due_date'])) {
                        $note .= $this->novalnetLang['novalnet_payment_valid_until'] . (($lang == 'en') ? date('d M Y', strtotime($response['due_date'])) : date('d.m.Y', strtotime($response['due_date']))) . $newLine;
                    }
                    $note .= $this->novalnetLang['novalnet_account_owner'] . $response['invoice_account_holder'] . $newLine;
                    $note .= $this->novalnetLang['novalnet_bank_iban'] . $response['invoice_iban'] . $newLine;
                    $note .= $this->novalnetLang['novalnet_bank_bic'] . $response['invoice_bic'] . $newLine;
                    $note .= $this->novalnetLang['novalnet_bank_name'] . $response['invoice_bankname'] . ' ' . trim($response['invoice_bankplace']) . $newLine;
                    $note .= $this->novalnetLang['novalnet_order_amount'] . (($lang == 'de') ? str_replace('.', ',', $response['amount']) : $response['amount']) . ' ' . $currency . $newLine;
                    $getConfig = array_filter($configuration);
                    if ($orderNo && !empty($getConfig)) {
                        $reference  = $this->novalnetLang['novalnet_invoice_note_multiple_reference']. $newLine;
                        $reference .= $this->novalnetLang['novalnet_reference1'] . ': BNR-' . trim($productId) . "-$orderNo" . $newLine;
                        $reference .= $this->novalnetLang['novalnet_reference2'] . ': TID ' . '&nbsp;' . $response['tid'] . $newLine;
                        $note .= $reference;
                    }
                }
                  if ($response['tid_status'] == '75' && $paymentShortName == 'novalnetinvoice') {
                      $note .=$this->novalnetLang['pending_guarantee_text'] . $newLine;
                  }

                break;
            case 'novalnetcashpayment':
                $note .= $this->novalnetLang['novalnet_tid_label'] . ':  ' . $response['tid'] . $newLine;
                if ($testMode) {
                    $note .= $this->novalnetLang['novalnet_message_test_order'] . $newLine;
                }
                $novalnetSlipExpiryDate = ($response['cp_due_date']) ? $response['cp_due_date'] : '';
                if ($novalnetSlipExpiryDate) {
                    $note .= $this->novalnetLang['cashpayment_slip_exp_date'] . ': ' . (($lang == 'en') ? date('d M Y', strtotime($novalnetSlipExpiryDate)) : date('d.m.Y', strtotime($novalnetSlipExpiryDate))) . $newLine;
                }
                $note .= $newLine . $this->novalnetLang['cashpayment_store'] . $newLine;
                $nearestStoreCounts = 1;
                foreach ($response as $key => $value) {
                    if (strpos($key, 'nearest_store_title') !== false) {
                        $nearestStoreCounts++;
                    }
                }
                for ($i = 1; $i < $nearestStoreCounts; $i++) {
                    $note .= $response['nearest_store_title_' . $i] . $newLine;
                    $note .= $response['nearest_store_street_' . $i] . $newLine;
                    $note .= $response['nearest_store_city_' . $i] . $newLine;
                    $note .= $response['nearest_store_zipcode_' . $i] . $newLine;
                    $note .= $response['nearest_store_country_' . $i] . $newLine . $newLine;
                }
                break;
            case 'novalnetpaypal':
                    $note .= $this->novalnetLang['novalnet_tid_label'] . ': ' . $response['tid'] . $newLine;
                    $note .= $this->novalnetLang['novalnet_paypalComment_label'] . ': ' . $response['paypal_transaction_id'] . $newLine;
                    break;
                   
            default:
                $note .= $this->novalnetLang['novalnet_tid_label'] . ': ' . $response['tid'] . $newLine;
                if ($testMode) {
                    $note .= $this->novalnetLang['novalnet_message_test_order'] . $newLine;
                }
                break;
        }
        if ($response['tid_status'] == '75' && $paymentShortName == 'novalnetsepa') {
            $note .=$this->novalnetLang['pending_guarantee_text_sepa'] . $newLine;
        }
        $note = $this->setHtmlEntity($note, 'decode');
        return $note;
    }

    /**
     * Novalnet gateway URLS
     *
     * @param null
     * @return array
     */
    public function novalnetGatewayUrl()
    {
        return array(
            'novalnetinstant_url' => 'https://payport.novalnet.de/online_transfer_payport',
            'novalnetideal_url' => 'https://payport.novalnet.de/online_transfer_payport',
            'novalnetpaypal_url' => 'https://payport.novalnet.de/paypal_payport',
            'novalnetcc_url' => 'https://payport.novalnet.de/pci_payport',
            'novalnetgiropay_url' => 'https://payport.novalnet.de/giropay',
            'novalneteps_url' => 'https://payport.novalnet.de/giropay',
            'novalnetprzelewy24_url' => 'https://payport.novalnet.de/globalbank_transfer',
            'paygate_url' => 'https://payport.novalnet.de/paygate.jsp',
            'infoport_url' => 'https://payport.novalnet.de/nn_infoport.xml',
            'novalnet_auto_api_url' => 'https://payport.novalnet.de/autoconfig'
        );
    }

    /**
     * Function to support update query
     *
     * @param $table
     * @param $data
     * @param $parameters
     * return null
     */
    public function novalnetDbUpdate($table, $data, $parameters)
    {
        $query = 'update ' . $table . ' set ';
        while (list($columns, $value) = each($data)) {
            $value = (string) ($value);
            switch ($value) {
                case 'now()':
                    $query .= $columns . ' = now(), ';
                    break;
                case 'null':
                    $query .= $columns . ' = null, ';
                    break;
                default:
                    $query .= $columns . ' = \'' . addslashes($value) . '\', ';
                    break;
            }
        }
        $query = substr($query, 0, -2) . ' where ' . $parameters;
        Shopware()->Db()->query($query);
    }

    /**
     * Returns if the payment mean is a novalnetsepa payment mean
     *
     * @param $orderNumber
     * @return array|null
     */
    public function getOrderNovalDetails($orderNumber)
    {
        if (empty($orderNumber)) {
            return null;
        }
        return Shopware()->Db()->fetchRow('SELECT payment_id, payment_key, payment_type, gateway_status, tid,currency,
        subs_id, amount, id, test_mode, additional_note, customer_id, configuration_details, lang
        FROM s_novalnet_transaction_detail
        WHERE order_no ="' . $orderNumber . '" order by id desc');
    }

    /**
     * Returns respective order payment details
     *
     * @param $orderNumber
     * @return array|null
     */
    public function getOrderNovalnetDetailsAttributes($orderNumber)
    {
        return Shopware()->Db()->fetchRow("SELECT novalnet_payment_tid, novalnet_payment_gateway_status,
            novalnet_payment_paid_amount, novalnet_payment_order_amount, novalnet_payment_current_amount,
            novalnet_payment_subs_id, novalnet_payment_due_date, novalnet_payment_type
            FROM s_order_attributes
            WHERE orderID = ? ORDER BY id DESC", array(
            $orderNumber
        ));
    }

    /**
     * To fetch the configuration values based on the language of the order
     *
     * @param $shopId
     * @return array
     */
    public function novalnetConfigElementsByShop($shopId)
    {
        $sqlnovalConfig = Shopware()->Db()->fetchAssoc('SELECT name,value,id
            FROM s_core_config_elements
            WHERE name LIKE "%novalnet%"');
        if ($shopId == 1) {
            foreach ($sqlnovalConfig as $keys => $values) {
                $nnConfigDefault[$keys] = $this->getUnserializedData($values['value']);
            }
        }
        $configKeys = array_keys($sqlnovalConfig);
        foreach ($configKeys as $keys) {
            $nnConfigElementvalues = Shopware()->Db()->fetchOne('SELECT value
                FROM s_core_config_values
                WHERE shop_id = ? AND element_id = ?', array(
                $shopId,
                $sqlnovalConfig[$keys]['id']
            ));
            $nnConfig[$keys]       = $this->getUnserializedData($nnConfigElementvalues);
        }
        foreach ($nnConfig as $key => $value) {
            if ($key == 'novalnet_tariff') {
                $getValue = ($value) ? $value : $nnConfigDefault[$key];
                preg_match('/\(([^\)]*)\)/', $getValue, $match);
                $tariff_val                 = explode('-', $match[1]);
                $nnNewConfig['tariff_type'] = $tariff_val[1];
                $nnNewConfig[$key]          = $tariff_val[0];
            } else {
                $nnNewConfig[$key] = ($value) ? $value : $nnConfigDefault[$key];
            }
        }
        return array_map('trim', $nnNewConfig);
    }

    /**
     * Log callback process in novalnet_callback_history table
     *
     * @param $datas
     * @param $updateAmount
     * @return boolean
     */
    public function insertCallbackTable($datas = array(), $updateAmount = false)
    {
        if ($datas) {
            $param['payment_type'] = $datas['payment_type'];
            $param['status']       = $datas['status'];
            $param['callback_tid'] = $datas['callback_tid'];
            $param['org_tid']      = $datas['original_tid'];
            $param['amount']       = ($datas['subs_billing'] != 1) ? $datas['amount'] : 0;
            $param['currency']     = $datas['currency'];
            $param['product_id']   = $datas['product_id'];
            $param['order_no']     = $datas['order_no'];
            $param['date']         = date('Y-m-d H:i:s');
            Shopware()->Db()->insert('s_novalnet_callback_history', $param);
            if ($updateAmount && $datas['subs_billing'] != 1) {
                Shopware()->Db()->query('UPDATE s_order_attributes SET novalnet_payment_paid_amount =
                    (novalnet_payment_paid_amount + ?)
                    WHERE orderID = (SELECT id FROM s_order WHERE ordernumber = ?)', array(
                    $datas['amount'],
                    $datas['order_no']
                ));
            }
            return true;
        }
        return false;
    }

    /**
     * Update callback comments in shop order tables
     *
     * @param array $datas
     * @return null
     */
    public function updateCallbackComments($datas = array())
    {
        if (!empty($datas['order_no'])) {
            $comments = $datas['comments'];
            if (!empty($datas['old_comments'])) {
                $comments = $datas['old_comments'] . $comments;
            }
            if ($comments) {
                $param['customercomment'] = str_replace('<br />', PHP_EOL, html_entity_decode($comments));
            }
            if (!empty($datas['orders_status_id'])) {
                Shopware()->Modules()->Order()->setPaymentStatus((int) $datas['id'], (int) $datas['orders_status_id'], false);
            }
            $this->novalnetDbUpdate('s_order', $param, 'ordernumber = "' . $datas['order_no'] . '"');
        }
    }

    /**
     * Refines the account holder name
     *
     * @param $holderName
     * @return string
     */
    public function getValidHolderName($holderName)
    {
        return str_replace(array(
            'amount=',
            '&'
        ), '', trim($holderName));
    }

    /**
     * Provides callback payments information
     *
     * @param null
     * @return array
     */
    public static function callbackPaymentsInfo()
    {
        return array(
            'Country' => array(
                'DE',
                'AT',
                'CH'
            ),
            'pinFields' => array(
                'pin',
                'sms'
            ),
            'pinPayments' => array(
                'novalnetsepa',
                'novalnetinvoice'
            )
        );
    }

    /**
     * Get novalnet affiliate config details
     *
     * @param $nnAffId
     * @param $customerNo
     * @return array|null
     */
    public function getNovalAffiliateDetails($nnAffId, $customerNo)
    {
        if (!$nnAffId && $customerNo) {
            $nnAffId = Shopware()->Db()->fetchOne('SELECT aff_id FROM s_novalnet_aff_user_detail WHERE customer_id = ? LIMIT 1', array(
                $customerNo
            ));
        }
        if (empty($nnAffId)) {
            return null;
        }
        return Shopware()->Db()->fetchRow('SELECT aff_id, aff_accesskey, aff_authcode FROM s_novalnet_aff_account_detail WHERE aff_id = ? LIMIT 1', array(
            $nnAffId
        ));
    }

    /**
     * autoconfigure the vendor details
     *
     * @param $result
     * @param $productActivationKey
     * @param $shop
     * @return void
     */
    public function autoApiConfiguration($result, $productActivationKey, $shop)
    {
        $tariffList  = array();
        $tariff_name = preg_split('/,/', $result['tariff_name']);
        $tariff_id   = preg_split('/,/', $result['tariff_id']);
        $tariff_type = preg_split('/,/', $result['tariff_type']);
        for ($i = 0; $i <= count($tariff_name); $i++) {
            $j               = $i + 1;
            $name            = str_replace('tariff_name' . $j . ':', '', $tariff_name[$i]);
            $type            = str_replace('tariff_type' . $j . ':', '', $tariff_type[$i]);
            $id              = str_replace('tariff_id' . $j . ':', '', trim($name) . '(' . $tariff_id[$i] . '-' . trim($type) . ')');
            $tariffList[$id] = $name;
        }
        $result['tariff_config'] = $tariffList;
        $orderRef                = Shopware()->Db()->fetchOne('SELECT id FROM s_novalnet_tariff WHERE shopid = ?', array(
            $shop
        ));
        if ($orderRef) {
            Shopware()->Db()->query('update s_novalnet_tariff set tariff = ? where shopid = ?', array(
                $productActivationKey,
                $shop
            ));
        } else {
            Shopware()->Db()->query('INSERT INTO s_novalnet_tariff(shopid, tariff)
                VALUES(?,?)', array(
                $shop,
                $productActivationKey
            ));
        }
        reset(array_keys($result['tariff_config']));
        $config_details   = Shopware()->Db()->fetchAssoc('SELECT name,id FROM s_core_config_elements WHERE name LIKE "%novalnet%"');
        Shopware()->Db()->query('DELETE FROM s_core_config_values WHERE shop_id = ' . $shop . '
            and element_id IN (' . $config_details['novalnet_secret_key']['id'] . ',' . $config_details['novalnet_vendor']['id'] . ',' . $config_details['novalnet_auth_code']['id'] . ',' . $config_details['novalnet_product']['id'] . ',' . $config_details['novalnet_tariff']['id'] . ',' . $config_details['novalnet_password']['id'] . ')');
        Shopware()->Db()->query('INSERT INTO s_core_config_values(element_id, value, shop_id)
            VALUES(?,?,?),(?,?,?),(?,?,?),(?,?,?),(?,?,?)', array(
            $config_details['novalnet_secret_key']['id'],
            serialize($productActivationKey),
            $shop,
            $config_details['novalnet_vendor']['id'],
            serialize($result['vendor_id']),
            $shop,
            $config_details['novalnet_auth_code']['id'],
            serialize($result['auth_code']),
            $shop,
            $config_details['novalnet_product']['id'],
            serialize($result['product_id']),
            $shop,
            $config_details['novalnet_password']['id'],
            serialize($result['access_key']),
            $shop
        ));
    }

    /**
     * Validate for users over 18 only
     *
     * @param $birthdate
     * @return boolean
     */
    public function validateAge($birthdate)
    {
        $birthday = strtotime($birthdate);
        //The age to be over, over +18
        $min      = strtotime('+18 years', $birthday);
        return (empty($birthdate) || time() < $min) ? true : false;
    }

    /**
     * Checks the guarantee payment amount, country and currency
     *
     * @param string  $amount
     * @param string $country
     * @param string $billing
     * @param string $shipping
     * @param string $currency
     * @param string $paymentname
     * @param array $config_details
     * @param boolean $field
     * @return boolean
     */
    public function isguaranteed($amount, $billing, $shipping, $country, $currency, $config_details, $paymentname, $field = false)
    {
        $address_valid = true;
        $billingAry    = array(
            'street' => $billing['street'],
            'zipcode' => $billing['zipcode'],
            'city' => $billing['city'],
            'countryId' => $billing['countryId'],
            'stateID' => $billing['stateID']
        );
        $shippingAry   = array(
            'street' => $shipping['street'],
            'zipcode' => $shipping['zipcode'],
            'city' => $shipping['city'],
            'countryId' => $shipping['countryId'],
            'stateID' => $shipping['stateID']
        );
        if ($billingAry !== $shippingAry) {
            $address_valid = false;
        }
        $min_amount = ($config_details[$paymentname . '_guaruntee_minimum']) ? $config_details[$paymentname . '_guaruntee_minimum'] : 999;
        return (round($amount) >= round($min_amount) && in_array($country, array(
            'DE',
            'AT',
            'CH'
        )) && $currency == 'EUR' && $address_valid);
    }

    /**
     * Generate Novalnet gateway parameters based on payment selection
     *
     * @param $configDetails
     * @param $nnCustomerData
     * @param $paymentShortName
     *
     * @return mixed
     */
    public function getCommonRequestParams($configDetails, $nnCustomerData, $paymentShortName)
    {
        $remoteIp  = $this->getIp();
        $serverIP  = $this->getIp('SERVER_ADDR');
        $lang      = Shopware()->Locale()->getLanguage();
        $firstname = $nnCustomerData['first_name'];
        $lastname  = $nnCustomerData['last_name'];

        if (!$firstname || !$lastname) {
            $name = $firstname . $lastname;
            list($firstname, $lastname) = preg_match('/\s/', $name) ? explode(' ', $name, 2) : array(
                $name,
                $name
            );
        }
        $urlparam = array_merge($nnCustomerData, array(
            'vendor' => $configDetails['novalnet_vendor'],
            'product' => $configDetails['novalnet_product'],
            'tariff' => $configDetails['novalnet_tariff'],
            'auth_code' => $configDetails['novalnet_auth_code'],
            'currency' => Shopware()->Config()->get('sCURRENCY'),
            'first_name' => $firstname,
            'last_name' => $lastname,
            'gender' => 'u',
            'country_code' => $nnCustomerData['country'],
            'search_in_street' => 1,
            'system_name' => 'shopware',
            'remote_ip' => $remoteIp,
            'system_version' => Shopware()->Config()->get('Version') . '-NN11.2.5',
            'system_url' => Shopware()->Front()->Router()->assemble(array(
                'controller' => 'index'
            )),
            'system_ip' => $serverIP,
            'notify_url' => trim($configDetails['novalnet_callback_notification_url']),
            'test_mode' => $configDetails[$paymentShortName . '_test_mode']
        ));
        
        $urlparam['lang'] = $urlparam['language'] = ((isset($lang) ? strtoupper($lang) : 'DE'));
        return $urlparam;
    }

    /**
     * Get the shopId
     *
     * @param reference
     * @return mixed
     */
    public function getShopId($reference)
    {
        return Shopware()->Db()->fetchOne("SELECT subshopID FROM `s_order` WHERE transactionID = ? OR ordernumber = ?", array(
            $reference,
            $reference
        ));
    }
    
    /**
     * generate unique string.
     *
     * @return string
    */
    public function getRandomString()
    {
        $randomwordarray = explode(',', '8,7,6,5,4,3,2,1,9,0,9,7,6,1,2,3,4,5,6,7,8,9,0');
        shuffle($randomwordarray);
        return substr(implode($randomwordarray, ''), 0, 16);
    }
     
    /**
    * form the guarantee error msg
    *
    * @param string  $amount
    * @param string $country
    * @param string $billing
    * @param string $shipping
    * @param string $currency
    * @param string $paymentname
    * @param array $config_details
    * @param boolean $field
    * @return string
    */
    public function guaranteedMsg($amount, $billing, $shipping, $country, $currency, $config_details, $paymentname, $field = false)
    {
        $guarantee_message = '';
        $billingAry    = array(
            'street' => $billing['street'],
            'zipcode' => $billing['zipcode'],
            'city' => $billing['city'],
            'countryId' => $billing['countryId'],
            'stateID' => $billing['stateID']
        );
        $shippingAry   = array(
            'street' => $shipping['street'],
            'zipcode' => $shipping['zipcode'],
            'city' => $shipping['city'],
            'countryId' => $shipping['countryId'],
            'stateID' => $shipping['stateID']
        );
        
        $min_amount = ($config_details[$paymentname . '_guaruntee_minimum']) ? $config_details[$paymentname . '_guaruntee_minimum'] : 999;
        $format_amount = (Shopware()->Locale()->getLanguage() != 'de') ? number_format($min_amount/100, 2) : number_format(str_replace('.', ',', $min_amount/100), 2);
        if (round($amount) < round($min_amount)) {
            $guarantee_message = sprintf($this->novalnetLang['guarantee_error_msg_amt'], $format_amount);
        }
        
        if (!in_array($country, array('DE', 'AT', 'CH'))) {
            $guarantee_message .= (!$guarantee_message) ? $this->novalnetLang['guarantee_error_msg_country'] : ', '. $this->novalnetLang['guarantee_error_msg_country'];
        }
        if ($currency != 'EUR') {
            $guarantee_message .= (!$guarantee_message) ? $this->novalnetLang['guarantee_error_msg_cur'] : ', '. $this->novalnetLang['guarantee_error_msg_cur'];
        }
        if ($billingAry !== $shippingAry) {
            $guarantee_message .= (!$guarantee_message) ? $this->novalnetLang['guarantee_error_msg_address'] : ', '. $this->novalnetLang['guarantee_error_msg_address'];
        }
        if (!empty($guarantee_message)) {
            $error_message = sprintf($this->novalnetLang['guarantee_error_msg'], $guarantee_message);
        }
        
        return $error_message;
    }
    
    /**
     * insert the Config details
     *
     * @param id
     * @param payment_params
     * @return null
     */
    public function getSubsConfig($id, $payment_params, $selectedPayment)
    {
        $order_id         = Shopware()->Db()->fetchRow('SELECT customer_id,last_order_id,payment_id FROM s_plugin_swag_abo_commerce_orders
            WHERE id = ? ', array(
            $id
        ));
        $order_number     = Shopware()->Db()->fetchOne('SELECT ordernumber FROM s_order
            WHERE id = ? ', array(
            $order_id['last_order_id']
        ));
        $paymentShortName = Shopware()->Db()->fetchOne('SELECT name FROM s_core_paymentmeans
            WHERE id = ? ', array(
            $order_id['payment_id']
        ));
        $currentPayment   = Shopware()->Db()->fetchOne('SELECT name FROM s_core_paymentmeans
            WHERE id = ? ', array(
            $selectedPayment
        ));
        $config_details   = Shopware()->Db()->fetchOne('SELECT configuration_details FROM s_novalnet_transaction_detail
            WHERE order_no = ? ', array(
            $order_number
        ));
        $config_details   = $this->getUnserializedData($config_details);
        if (empty($config_details)) {
            $config_details = $this->getNovalConfigDetails(Shopware()->Plugins()->Frontend()->NovalPayment()->Config());
        }
        $order_details['configuration_details'] = ($payment_params) ? serialize(array_merge($config_details, $payment_params)) : serialize($config_details);
        $order_details['order_no']              = $order_number;
        $order_details['customer_id']           = $order_id['customer_id'];
        $order_details['previous_payment']      = $paymentShortName;
        $order_details['current_payment']       = $currentPayment;
        $order_details['payment_changed_date']  = date('Y-m-d H:i:s');
        if (preg_match("/novalnet/i", $currentPayment)) {
            Shopware()->Db()->insert('s_novalnet_change_payment_subscription', $order_details);
        }
    }
    
    /**
     * create subscription table with payment change method.
     *
     * @param null
     * @return boolean
     */
    public static function createSubscriptionTable()
    {
        Shopware()->Db()->query('CREATE TABLE IF NOT EXISTS s_novalnet_change_payment_subscription (
                                 id int(11) unsigned AUTO_INCREMENT COMMENT "Auto increment ID",
                                 customer_id int(11) unsigned DEFAULT NULL COMMENT "Customer ID from shop",
                                 order_no varchar(30) COMMENT "Order ID from shop",
                                 previous_payment varchar(255) COMMENT "Previous Payment",
                                 current_payment varchar(255) COMMENT "Current Nvalnet Payment",
                                 configuration_details TEXT DEFAULT NULL COMMENT "Configuration Values of repective Order",
                                 payment_changed_date datetime COMMENT "Payment Changed Date",
                                 PRIMARY KEY (id),
                                 KEY order_no (order_no)
                                 ) AUTO_INCREMENT=1 COMMENT="Novalnet Subscription Payment Change History"');
        
        return true;
    }
    
    /**
     * form recurring order invoice and guarantee params.
     *
     * @param $params
     * @param $config_details
     * @param $user_details
     * @return null
     */
    public static function formParams(&$params, $config_details, $user_details = array(), $reference_details, $change_payment = false)
    {
        if (in_array($params['key'], array(
            '40',
            '41'
        ))) {
            $params['birth_date'] = $user_details['additional']['user']['birthday'];
            if ($params['key'] == '40') {
                $params['payment_type'] = 'GUARANTEED_DIRECT_DEBIT_SEPA';
            } else {
                $params['payment_type'] = 'GUARANTEED_INVOICE';
            }
        }
        if (in_array($user_details['additional']['payment']['name'], array(
            'novalnetinvoice',
            'novalnetprepayment'
        ))) {
            $params['invoice_type'] = strtoupper(str_replace('novalnet', '', $user_details['additional']['payment']['name']));
            if ($user_details['additional']['payment']['name'] == 'novalnetinvoice' && $config_details['novalnetinvoice_due_date']) {
                $params['due_date'] = date('Y-m-d', strtotime('+' . max(0, intval($config_details['novalnetinvoice_due_date'])) . ' days'));
            }
        }
        if ($change_payment) {
            $params['create_payment_ref'] = '1';
        } else {
            $params['payment_ref'] = $reference_details['tid'];
        }
    }
    
    /**
     * Based on the PHP version, we customize the unserialize function handling.
     *
     * @param $data
     *
     * @return array
    */
    public function getUnserializedData($data)
    {
        if (\PHP_VERSION_ID >= 70000) {
            $unserialized = unserialize($data, ['allowed_classes' => false]);
        } else {
            $unserialized = unserialize($data);
        }
        return $unserialized;
    }
}
