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

class Shopware_Controllers_Backend_NovalnetOrderOperations extends Shopware_Controllers_Backend_ExtJs
{
    private $nHelper;
    private $novalnetLang;
    private $novalnetGatewayUrl;
    private $orderNumber;
    private $getorderId;
    private $record_attributes;
    private $remoteIp;
    private $record;
    private $lang;
    private $dateformatter;
    
    /**
     * Init method that get called automatically
     *
     * @param null
     * @return null
     */
    public function init()
    {
        $this->nHelper            = new Shopware_Plugins_Frontend_NovalPayment_lib_classes_NovalnetHelper();
        $this->novalnetGatewayUrl = $this->nHelper->novalnetGatewayUrl();
        $this->orderNumber        = $this->Request()->getParam('number');
        $this->getorderId         = $this->Request()->getParam('orderId');
        $this->record             = $this->nHelper->getOrderNovalDetails($this->orderNumber);
        $this->record_attributes  = $this->nHelper->getOrderNovalnetDetailsAttributes($this->getorderId);
        $this->lang               = ($this->record['lang']) ? $this->record['lang'] : $this->nHelper->getOrderLanguageCode($this->orderNumber);
        $this->dateformatter      = ($this->lang == 'en') ? 'd M Y' : 'd.m.Y';
        $this->novalnetLang       = Shopware_Plugins_Frontend_NovalPayment_lib_classes_TranslationHelper::novalnetGetLanguage($this->lang);
        $this->remoteIp           = $this->nHelper->getIp();
    }
    
    /**
     * Action listener to determine if the Onhold Order Operations Tab will be displayed
     *
     * @param null
     * @return boolean
     */
    public function displayOnholdOrdersTabAction()
    {
        if ($this->record['tid']) {
            return $this->View()->assign(array(
                'success' => ($this->record['amount'] > 0 && ((in_array($this->record['payment_key'], array(
                    6,
                    37,
                    40
                )) && in_array($this->record['gateway_status'], array(
                    98,
                    99
                ))) || ($this->record['gateway_status'] == 91 && in_array($this->record['payment_key'], array(
                    27,
                    41
                ))) || ($this->record['gateway_status'] == 85 && in_array($this->record['payment_key'], array(
                    34
                )))))
            ));
        }
    }
    
    /**
     * Action listener to determine if the refund order operations tab will be displayed
     *
     * @param null
     * @return boolean
     */
    public function displayAmountRefundTabAction()
    {
        $orderID      = $this->Request()->getParam('id');
        $orderDetails = $this->fetchInvoiceDetails($orderID);
        if ($this->record['tid']) {
            return $this->View()->assign(array(
                'success' => (($this->record['gateway_status'] == 100 && ($orderDetails['novalnet_payment_order_amount'] || (in_array($this->record['payment_key'], array(
                    27,
                    41
                )) && ($orderDetails['novalnet_payment_order_amount'] || $orderDetails['novalnet_payment_paid_amount'])))))
            ));
        }
    }
    
    /**
     * Action listener to determine if the Amount/Date Update Order Operations Tab will be displayed
     *
     * @param null
     * @return array
     */
    public function displayAmountUpdateTabAction()
    {
        $orderID              = $this->Request()->getParam('id');
        $orderDetails         = $this->fetchInvoiceDetails($orderID);
        $callback_paid_amount = $this->nHelper->getOrderPaidAmount($this->record['tid']);
        return $this->View()->assign(array(
            'success' => ($this->record['amount'] > 0 && ((in_array($this->record['payment_key'], array(
                37
            )) && ($this->record['gateway_status'] == 99)) || ($this->record['gateway_status'] == 100 && in_array($this->record['payment_key'], array(
                27,
                59
            )) && $callback_paid_amount < $orderDetails['novalnet_payment_current_amount'])))
        ));
    }
    
    /**
     * Action listener to determine if the Due Date Options will be displayed
     *
     * @param null
     * @return array
     */
    public function displayZeroAmountBookingTabAction()
    {
        $configDetails = $this->nHelper->getUnserializedData($this->record['configuration_details']);
        return $this->View()->assign(array(
            'success' => (in_array($this->record['payment_key'], array(
                6,
                37,
                34
            )) && $configDetails['amount'] == 0 && $configDetails['tariff_type'] == 2 && $configDetails[$this->record['payment_type'] . '_shopping_type'] == 'zero' && $this->record['amount'] == 0)
        ));
    }
    
    /**
     * Action Listener to determine payment details
     *
     * @param null
     * @return array
     */
    public function displayPaymentDetailsAction()
    {
        return $this->View()->assign(array(
            'orderPaymentname' => $this->record['payment_type'],
            'orderAmount' => $this->record_attributes['novalnet_payment_order_amount'],
            'orderduedate' => $this->record_attributes['novalnet_payment_due_date']
        ));
    }
    
    /**
     * Action Listener to determine payment details for refund process
     *
     * @param null
     * @return array
     */
    public function displayPaymentDetailsRefundAction()
    {
        $configDetails = $this->nHelper->getUnserializedData($this->record['configuration_details']);
        return $this->View()->assign(array(
            'orderPaidAmount' => $this->record_attributes['novalnet_payment_paid_amount'],
            'orderAmount' => $this->record_attributes['novalnet_payment_order_amount'],
            'orderHolder' => $configDetails['holder_name']
        ));
    }
    
    /**
     * Action listener to determine if the Due Date Options will be displayed
     *
     * @param null
     * @return array
     */
    public function displayDueDateFieldAction()
    {
        return $this->View()->assign(array(
            'success' => (in_array($this->record['payment_key'], array(
                27,
                41,
                59
            )))
        ));
    }
    
    /**
     * Confirm|Cancel the order
     *
     * @param null
     * @return mixed
     */
    public function onholdTransAction()
    {
        $request        = $this->Request()->getParams();
        $configDetails  = $this->nHelper->getUnserializedData($this->record['configuration_details']);
        $subShopId      = $this->nHelper->getShopId($this->orderNumber);
        $nnConfigStatus = $this->nHelper->novalnetConfigElementsByShop($subShopId);
        if (!$this->nHelper->validateBackendConfig($configDetails, false) || !$this->record['payment_key'] || !$this->record['tid']) {
            return $this->View()->assign(array(
                'success' => false,
                'code' => '',
                'message' => $this->novalnetLang['error_novalnet_basicparam']
            ));
        } elseif (!$request['status']) {
            return $this->View()->assign(array(
                'success' => false,
                'code' => '',
                'message' => $this->novalnetLang['novalnet_text_please_select_status']
            ));
        }
        $response = $this->nHelper->curlCallRequest(array(
            'vendor' => $configDetails['novalnet_vendor'],
            'product' => $configDetails['novalnet_product'],
            'key' => $this->record['payment_key'],
            'tariff' => $configDetails['novalnet_tariff'],
            'auth_code' => $configDetails['novalnet_auth_code'],
            'edit_status' => '1',
            'tid' => $this->record['tid'],
            'status' => $request['status'], // 100 / 103
            'remote_ip' => $this->remoteIp
        ), $this->novalnetGatewayUrl['paygate_url']);
        parse_str($response->getBody(), $data);
        if ($data['status'] == 100) {
            if ($request['status'] == 100) {
                $transComments = $message = sprintf($this->novalnetLang['trans_novalnet_confirm_successful_message'], date($this->dateformatter), date(' H:i:s'));
                if (in_array($this->record['payment_key'], array(
                    27,
                    41
                ))) {
                    $transactionDetails = Shopware()->Db()->fetchRow('select test_mode, account_holder, bank_name, bank_city, amount, currency, bank_iban, bank_bic, due_date from s_novalnet_preinvoice_transaction_detail where tid = ? ', array(
                        $this->record['tid']
                    ));
                    $message       = sprintf($this->novalnetLang['trans_novalnet_confirm_due_date'], $this->record['tid'], date($this->dateformatter, strtotime($data['due_date'])));
                    $transComments = $message . PHP_EOL;
                    $transComments .= $this->nHelper->prepareComments($this->record['payment_type'], array(
                            'invoice_account_holder' => $transactionDetails['account_holder'],
                            'invoice_bankname' => $transactionDetails['bank_name'],
                            'invoice_bankplace' => $transactionDetails['bank_city'],
                            'amount' => sprintf('%.2f', $transactionDetails['amount'] / 100),
                            'currency' => $transactionDetails['currency'],
                            'tid' => $this->record['tid'],
                            'invoice_iban' => $transactionDetails['bank_iban'],
                            'invoice_bic' => $transactionDetails['bank_bic'],
                            'tid_status' => $request['status'],
                            'due_date' => $data['due_date']
                        ), $this->record['currency'], $transactionDetails['test_mode'], $this->orderNumber, $configDetails['novalnet_product'], $this->lang, $configDetails);
                    $this->nHelper->novalnetDbUpdate('s_novalnet_preinvoice_transaction_detail', array(
                            'due_date' => $data['due_date']
                        ), 'tid=' . $this->record['tid']);
                }
                $status = ($this->record['payment_key'] == 27) ? $nnConfigStatus[$this->record['payment_type'].'_before_paymenstatus'] : $nnConfigStatus[$this->record['payment_type'].'_after_paymenstatus'];
                $this->nHelper->updateOrdertable($this->orderNumber, (int) $status, str_replace('<br />', PHP_EOL, '<br />' . $transComments . '<br />'), $request['id']);
            } else {
                $message = sprintf($this->novalnetLang['trans_novalnet_confirm_deactivated_message'], date($this->dateformatter), date('H:i:s'));
                $this->nHelper->updateOrdertable($this->orderNumber, (int) $nnConfigStatus['novalnet_onhold_order_cancelled'], str_replace('<br />', PHP_EOL, '<br />' . $message), $request['id']);
            }
            $data = array_merge($data, $this->record);
            if ($this->record['payment_key'] == '34') {
                $configDetails = array_merge($configDetails, array(
                    'paypal_transaction_id' => ($data['paypal_transaction_id']) ? $data['paypal_transaction_id'] : ''
                ));
                $this->nHelper->novalnetDbUpdate('s_novalnet_transaction_detail', array(
                    'configuration_details' => serialize($configDetails)
                ), "tid = '{$this->record['tid']}'");
            }
            $this->nHelper->updateLiveNovalTransStatus($this->record['tid'], $data);
            return $this->View()->assign(array(
                'success' => true,
                'code' => $data['status'],
                'message' => $message,
                'data' => ''
            ));
        } else {
            $message = $this->novalnetLang['error_novalnet'] . ($this->nHelper->getStatusDesc($data)) . '( Status : ' . $data['status'] . ')';
            $this->nHelper->updateLiveNovalTransStatus($this->record['tid'], $data);
            $this->nHelper->updateOrdertable($this->orderNumber, '', str_replace('<br />', PHP_EOL, '<br />' . $message), $request['id']);
            return $this->View()->assign(array(
                'success' => false,
                'code' => $data['status'],
                'message' => $message
            ));
        }
    }
    
    /**
     * Update the amount in the Novalnet server
     *
     * @param null
     * @return array
     */
    public function amountUpdateAction()
    {
        $request       = $this->Request()->getParams();
        $configDetails = $this->nHelper->getUnserializedData($this->record['configuration_details']);
        $paymentKey    = ($this->record['payment_key']) ? $this->record['payment_key'] : $this->nHelper->getPaymentKey($this->record['payment_type']);
        if (!$this->nHelper->validateBackendConfig($configDetails, false)) {
            return $this->View()->assign(array(
                'success' => false,
                'code' => '',
                'message' => $this->novalnetLang['error_novalnet_basicparam']
            ));
        } elseif (!$this->nHelper->isDigits($request['amount']) || empty($request['amount'])) {
            return $this->View()->assign(array(
                'success' => false,
                'code' => '',
                'message' => $this->novalnetLang['error_novalnet_invalidamount']
            ));
        } elseif (in_array($paymentKey, array(
            27,
            59
        )) && (!$request['due_date'] || ($request['due_date'] && (strtotime($request['due_date']) < strtotime(date('Y-m-d')))))) {
            return $this->View()->assign(array(
                'success' => false,
                'code' => '',
                'message' => $this->novalnetLang['error_novalnet_due_date']
            ));
        } elseif (!$this->record['tid'] || !$paymentKey) {
            return $this->View()->assign(array(
                'success' => false,
                'code' => '',
                'message' => $this->novalnetLang['error_novalnet_general_message']
            ));
        }
        $serverParams = array(
            'vendor' => $configDetails['novalnet_vendor'],
            'product' => $configDetails['novalnet_product'],
            'key' => $paymentKey,
            'tariff' => $configDetails['novalnet_tariff'],
            'auth_code' => $configDetails['novalnet_auth_code'],
            'edit_status' => 1,
            'tid' => $this->record['tid'],
            'status' => 100,
            'update_inv_amount' => 1,
            'amount' => $request['amount'],
            'remote_ip' => $this->remoteIp
        );
        if ($request['due_date'] || $request['cashpyment_due_date'] && in_array($this->record['payment_type'], array(
            'novalnetinvoice',
            'novalnetprepayment',
            'novalnetcashpayment'
        ))) {
            $serverParams['due_date'] = ($request['due_date']) ? date('Y-m-d', strtotime($request['due_date'])) : date('Y-m-d', strtotime($request['cashpyment_due_date']));
        }
        $response = $this->nHelper->curlCallRequest($serverParams, $this->novalnetGatewayUrl['paygate_url']);
        parse_str($response->getBody(), $data);
        if ($data['status'] == 100) {
            if (in_array($this->record['payment_type'], array(
                'novalnetinvoice',
                'novalnetprepayment'
            ))) {
                $message = sprintf($this->novalnetLang['novalnet_order_operations_duedate_update'], sprintf('%.2f', $request['amount'] / 100) . ' ' . $request['currency'], date($this->dateformatter, strtotime($request['due_date'])), date('H:i:s'));
            } elseif ($this->record['payment_type'] == 'novalnetcashpayment') {
                $message = sprintf($this->novalnetLang['novalnet_order_operations_duedate_update_cashpayment'], sprintf('%.2f', $request['amount'] / 100) . ' ' . $request['currency'], date($this->dateformatter, strtotime($request['due_date'])), date('H:i:s'));
            } else {
                $message = sprintf($this->novalnetLang['novalnet_order_operations_amountupdate'], sprintf('%.2f', $request['amount'] / 100) . ' ' . $request['currency'], date($this->dateformatter), date('H:i:s'));
            }
            $transComments    = $message . PHP_EOL;
            $sOrderAttributes = array();
            if ($paymentKey == 27) {
                $this->nHelper->updateLiveNovalTransStatus($this->record['tid'], $data, true);
                if ($request['due_date'] && $request['due_date'] != '0000-00-00') {
                    $request['due_date'] = date('Y-m-d', strtotime($request['due_date']));
                }
                $tidAccountInfo = Shopware()->Db()->fetchRow('select test_mode,account_holder, account_number, bank_code, bank_name, bank_city,
                    amount, currency, bank_iban, bank_bic, due_date from
                    s_novalnet_preinvoice_transaction_detail where tid = ? limit 1', array(
                    $this->record['tid']
                ));
                if (!$request['due_date']) {
                    $request['due_date'] = $tidAccountInfo['due_date'];
                }
                // Update new comments in orders table for invoice and prepayment payment methods
                $this->nHelper->UpdatePrepaymentInvoiceTransOrderRef(array(
                    'order_id' => $this->orderNumber,
                    'tid' => $this->record['tid'],
                    'amount' => $request['amount'],
                    'due_date' => $request['due_date']
                ));
                //Forming the invoice and prepayment comments
                $transComments .= $this->nHelper->prepareComments($this->record['payment_type'], array(
                    'invoice_account_holder' => $tidAccountInfo['account_holder'],
                    'invoice_account' => $tidAccountInfo['account_number'],
                    'invoice_bankcode' => $tidAccountInfo['bank_code'],
                    'invoice_bankname' => $tidAccountInfo['bank_name'],
                    'invoice_bankplace' => $tidAccountInfo['bank_city'],
                    'amount' => sprintf('%.2f', ($request['amount'] / 100)),
                    'currency' => $tidAccountInfo['currency'],
                    'tid' => $this->record['tid'],
                    'tid_status' => $data['status'],
                    'due_date'	=> date($this->dateformatter, strtotime($request['due_date'])),
                    'invoice_iban' => $tidAccountInfo['bank_iban'],
                    'invoice_bic' => $tidAccountInfo['bank_bic']
                ), $this->record['currency'], $tidAccountInfo['test_mode'], $this->orderNumber, $configDetails['novalnet_product'], $this->lang, $configDetails);
            } elseif ($paymentKey == 59) {
                $this->nHelper->updateLiveNovalTransStatus($this->record['tid'], $data);
                if ($request['due_date'] && $request['due_date'] != '0000-00-00') {
                    $request['due_date'] = date('Y-m-d', strtotime($request['due_date']));
                }
                $tidInfo     = Shopware()->Db()->fetchRow('select additional_note from
                    s_novalnet_transaction_detail where tid = ? limit 1', array(
                    $this->record['tid']
                ));
                $dueDate     = Shopware()->Db()->fetchRow('select novalnet_payment_due_date from
                    s_order_attributes where id = ? limit 1', array(
                    $request['id']
                ));
                $slipExpDate = date($this->dateformatter, strtotime($dueDate['novalnet_payment_due_date']));
                $slipReqDate = date($this->dateformatter, strtotime($request['due_date']));
                $transactionInfo = '<br />' . implode('', $tidInfo);
                $info = str_replace($this->novalnetLang['cashpayment_slip_exp_date'] . ': ' . $slipExpDate, $this->novalnetLang['cashpayment_slip_exp_date'] . ': ' . $slipReqDate, $transactionInfo);
                $transComments .= str_replace($this->novalnetLang['cashpayment_slip_exp_date'] . ': ' . $slipExpDate, $this->novalnetLang['cashpayment_slip_exp_date'] . ': ' . $slipReqDate, $transactionInfo);
                $this->nHelper->novalnetDbUpdate('s_novalnet_transaction_detail', array(
                'additional_note' => $info
            ), 'tid=' . $this->record['tid']);
            } else {
                $this->nHelper->updateLiveNovalTransStatus($this->record['tid'], $data); // Update status
            }
            $sOrderAttributes = array_merge($sOrderAttributes, array(
                'novalnet_payment_due_date' => $request['due_date'],
                'novalnet_payment_order_amount' => $request['amount'],
                'novalnet_payment_current_amount' => $request['amount'],
                'novalnet_payment_paid_amount' => $request['amount']
            ));
            $this->nHelper->novalnetDbUpdate('s_order_attributes', $sOrderAttributes, 'orderID="' . $request['id'] . '"');
            $this->nHelper->updateOrdertable($this->orderNumber, '', str_replace('<br />', PHP_EOL, '<br />' . $transComments), $request['id']);
            return $this->View()->assign(array(
                'success' => true,
                'code' => $data['status'],
                'message' => $message,
                'data' => ''
            ));
        } else {
            $message = $this->novalnetLang['trans_confirm_failed_message'] . ($this->nHelper->getStatusDesc($data, $this->novalnetLang['error_novalnet_general_message']));
            return $this->View()->assign(array(
                'success' => false,
                'code' => $data['status'],
                'message' => $message
            ));
        }
    }
    
    /**
     * Novalnet refund process
     *
     * @param null
     * @return mixed
     */
    public function novalnetRefundAction()
    {
        $request        = $this->Request()->getParams();
        $configDetails  = $this->nHelper->getUnserializedData($this->record['configuration_details']);
        $subShopId      = $this->nHelper->getShopId($this->orderNumber);
        $nnConfigStatus = $this->nHelper->novalnetConfigElementsByShop($subShopId);
        $paymentKey     = ($this->record['payment_key']) ? $this->record['payment_key'] : $this->nHelper->getPaymentKey($this->record['payment_type']);
        if (!$this->nHelper->validateBackendConfig($configDetails, false)) {
            return $this->View()->assign(array(
                'success' => false,
                'code' => '',
                'message' => $this->novalnetLang['error_novalnet_basicparam']
            ));
        } elseif (!$this->nHelper->isDigits($request['nn_partial_refund_amount']) || empty($request['nn_partial_refund_amount'])) {
            return $this->View()->assign(array(
                'success' => false,
                'code' => '',
                'message' => $this->novalnetLang['error_novalnet_invalidamount']
            ));
        }
        if ($request['nn_refund_payment_type'] == 'nn_sepa') {
            $nnAccountHolder = $this->Request()->getParam('nn_sepa_accountholder');
            $nnSepaIban      = $this->Request()->getParam('nn_sepa_iban');
        }
        if ($request['nn_refund_payment_type'] == 'nn_sepa' && (!$nnAccountHolder || preg_match('/[#%\^<>@$=*!]/', $nnAccountHolder) || !$nnSepaIban)) {
            return $this->View()->assign(array(
                'success' => false,
                'code' => '',
                'message' => $this->novalnetLang['novalnet_payment_validate_invalid_directdebit_message']
            ));
        }
        $refundParams = array(
            'vendor' => $configDetails['novalnet_vendor'],
            'product' => $configDetails['novalnet_product'],
            'key' => $paymentKey,
            'tariff' => $configDetails['novalnet_tariff'],
            'auth_code' => $configDetails['novalnet_auth_code'],
            'refund_request' => '1',
            'tid' => $this->record['tid'],
            'refund_param' => $request['nn_partial_refund_amount'],
            'remote_ip' => $this->remoteIp
        );
        if (preg_match('/^[A-Za-z0-9]+$/', $request['nn_refund_ref'])) {
            $refundParams['refund_ref'] = $request['nn_refund_ref'];
        }
        if ($request['nn_refund_payment_type'] == 'nn_sepa') {
            $refundParams['account_holder'] = $nnAccountHolder;
            $refundParams['iban']           = $nnSepaIban;
        }
        $response = $this->nHelper->curlCallRequest($refundParams, $this->novalnetGatewayUrl['paygate_url']);
        parse_str($response->getBody(), $data);
        if ($data['status'] == 100) {
            $orderStatus         = '';
            $orderServerResponse = $this->nHelper->getStatusDesc($data, 'Successfull');
            if ($data['tid']) {
                $message = sprintf($this->novalnetLang['message_novalnet_refund_amount_1'], $this->record['tid'], sprintf('%.2f', $request['nn_partial_refund_amount'] / 100) . ' ' . $request['currency'], $data['tid']);
            } else {
                $message = sprintf($this->novalnetLang['message_novalnet_refund_amount'], $this->record['tid'], sprintf('%.2f', $request['nn_partial_refund_amount'] / 100) . ' ' . $request['currency']);
            }
            if ($paymentKey == 6 && !empty($data['tid'])) {
                $message = sprintf($this->novalnetLang['message_novalnet_refund_amount_1'], $this->record['tid'], sprintf('%.2f', $request['nn_partial_refund_amount'] / 100) . ' ' . $request['currency'], $data['tid']);
            } elseif ($paymentKey == 34 && $data['paypal_refund_tid']) {
                $message .= ' - PayPal Ref: ' . $data['paypal_refund_tid'];
            }
            if ($data['tid'] && $paymentKey == 6) {
                $data = array_merge($data, $this->record);
                $this->nHelper->updateLiveNovalTransStatus($this->record['tid'], $data, '');
                if ($data['tid_status'] == 103) {
                    $orderStatus = $nnConfigStatus['novalnet_onhold_order_cancelled'];
                }
            } else {
                $data = array_merge($data, $this->record);
                $this->nHelper->updateLiveNovalTransStatus($this->record['tid'], $data, (($paymentKey == 27) ? true : false));
                if ($data['tid_status'] == 103) {
                    $orderStatus = $nnConfigStatus['novalnet_onhold_order_cancelled'];
                }
            }
            $this->nHelper->updateOrdertable($this->orderNumber, $orderStatus, str_replace('<br />', PHP_EOL, '<br />' . $message), $request['id']);
            return $this->View()->assign(array(
                'success' => true,
                'code' => '',
                'message' => $orderServerResponse,
                'data' => ''
            ));
        } else {
            $error = $this->nHelper->getStatusDesc($data, $this->novalnetLang['error_novalnet_general_message']);
            return $this->View()->assign(array(
                'success' => false,
                'code' => '',
                'message' => $error
            ));
        }
    }
    
    /**
     * Novalnet booking amount process
     *
     * @param null
     * @return array
     */
    public function bookAmountAction()
    {
        if (version_compare(Shopware()->Config()->version, '5.6.0', '>=')) {
            $configDetails = $this->nHelper->getNovalConfigDetails(Shopware()->Container()->get('shopware.plugin.config_reader')->getByPluginName('NovalPayment'));
        } else {
            $configDetails = $this->nHelper->getNovalConfigDetails(Shopware()->Plugins()->Frontend()->NovalPayment()->Config());
        }
        $id            = $this->Request()->getParam('id');
        $invoiceAmount = $this->Request()->getParam('invoiceAmount');
        $configDetails = $this->nHelper->getUnserializedData($this->record['configuration_details']);
        
        if (!$this->nHelper->isDigits($invoiceAmount) || empty($invoiceAmount)) {
            return $this->View()->assign(array(
                'success' => false,
                'code' => '',
                'message' => $this->novalnetLang['error_novalnet_invalidamount']
            ));
        }
        $param   = array();
        $formAry = array(
            'currency',
            'key',
            'payment_type',
            'vendor',
            'auth_code',
            'product',
            'tariff',
            'first_name',
            'last_name',
            'email',
            'customer_no',
            'street',
            'city',
            'country_code',
            'country',
            'zip',
            'tel',
            'fax',
            'remote_ip',
            'lang',
            'system_name',
            'system_version',
            'system_url',
            'system_ip'
        );
        foreach ($formAry as $key) {
            $param[$key] = $configDetails[$key];
        }
        $param            = array_merge($param, array(
            'test_mode' => ($configDetails['test_mode']) ? $configDetails['test_mode'] : 0,
            'gender' => 'u',
            'search_in_street' => 1,
            'payment_ref' => $this->record['tid'],
            'order_no' => $this->orderNumber,
            'amount' => $invoiceAmount
        ));
        $novalnetResponse = $this->nHelper->curlCallRequest($param, $this->novalnetGatewayUrl['paygate_url']);
        parse_str($novalnetResponse->getBody(), $response);
        if ($response['status'] == 100) {
            $customercomment = $this->novalnetLang['novalnet_payment_transdetails_info'] . '<br />';
            $customercomment .= $this->novalnetLang['payment_name_' . $this->record['payment_type']] . '<br />';
            $customercomment .= $this->novalnetLang['novalnet_tid_label'] . ": " . $response['tid'] . '<br />';
            if ($response['test_mode']) {
                $customercomment .= $this->novalnetLang['novalnet_message_test_order'] . '<br />';
            }
            $customercomment .= '<br />' . sprintf($this->novalnetLang['novalnet_booked_message'], sprintf('%.2f', $invoiceAmount / 100) . ' ' . $param['currency'], $response['tid']);
            $customercomment = str_replace('<br />', PHP_EOL, $customercomment);
            $this->nHelper->novalnetDbUpdate('s_order_attributes', array(
                'novalnet_payment_tid' => $response['tid'],
                'novalnet_payment_gateway_status' => $response['tid_status'],
                'novalnet_payment_paid_amount' => $invoiceAmount,
                'novalnet_payment_order_amount' => $invoiceAmount,
                'novalnet_payment_current_amount' => $invoiceAmount
            ), "orderID = '{$id}'");
            $this->nHelper->novalnetDbUpdate('s_novalnet_transaction_detail', array(
                'tid' => $response['tid'],
                'gateway_status' => $response['tid_status'],
                'amount' => $invoiceAmount
            ), "order_no = '{$this->orderNumber}'");
            $this->nHelper->novalnetDbUpdate('s_order', array(
                'temporaryID' => $response['tid'],
                'transactionID' => $response['tid'],
                'customercomment' => $customercomment
            ), "ordernumber = '{$this->orderNumber}'");
            return $this->View()->assign(array(
                'success' => true,
                'code' => '',
                'message' => 'success'
            ));
        } else {
            return $this->View()->assign(array(
                'success' => false,
                'code' => '',
                'message' => $this->nHelper->getStatusDesc($response, $this->novalnetLang['novalnet_payment_validate_payment'])
            ));
        }
    }
    
    /**
     * To return the amount details of order
     *
     * @param $orderID, $orderNumber
     * @return array|null
     */
    public function fetchInvoiceDetails($orderID, $orderNumber = '')
    {
        return Shopware()->Db()->fetchRow('SELECT novalnet_payment_paid_amount, novalnet_payment_order_amount,
            novalnet_payment_current_amount,(SELECT SUM(amount) FROM
            s_novalnet_callback_history WHERE order_no = ?) as
            callback_paid_total FROM s_order_attributes where orderID = ?', array(
            $orderNumber,
            $orderID
        ));
    }
    
    /**
     * Novalnet auto configuration
     *
     * @param null
     * @return string
     */
    public function autofillConfigurationAction()
    {
        $productActivationKey = trim($this->Request()->novalnet_secret_key);
        $shop_id              = $this->Request()->shop;
        $getshop_ary          = explode('[', $shop_id);
        $getshop_id           = trim(str_replace(']', '', $getshop_ary[1]));
        $serverIP             = $this->nHelper->getIp('SERVER_ADDR');
        $remoteIP             = $this->nHelper->getIp();
        $serverParams         = array(
            'system_ip' => $serverIP,
            'remote_ip' => $remoteIP,
            'api_config_hash' => $productActivationKey,
            'lang' => strtoupper(Shopware()->Locale()->getLanguage())
        );
        $response             = $this->nHelper->curlCallRequest($serverParams, $this->novalnetGatewayUrl['novalnet_auto_api_url']);
        $resultArr            = (array) json_decode($response->getBody());
        $sNovalError          = empty($resultArr['vendor_id']) ? $this->nHelper->getStatusDesc($resultArr) : '';
        if (!empty($resultArr['vendor_id'])) {
            $this->nHelper->autoApiConfiguration($resultArr, $productActivationKey, $getshop_id);
            $this->View()->assign(array(
                'success' => true,
                'data' => $resultArr
            ));
        } else {
            return $this->View()->assign(array(
                'sNovalError' => $sNovalError
            ));
        }
    }
    
    /**
     * Novalnet tariff configuration
     *
     * @param null
     * @return array
     */
    public function getTariffAction()
    {
        $fieldName = $this->Request()->getParam('field_name');
        $ini = strpos($fieldName, '[');
        $ini += strlen('[');
        $len = strpos($fieldName, ']', $ini) - $ini;
        $shopId = substr($fieldName, $ini, $len);
        
        $tariffRecord         = Shopware()->Db()->fetchOne('SELECT tariff FROM s_novalnet_tariff WHERE  shopid = ?', array(
            $shopId
        ));
        $productActivationKey = trim($tariffRecord);
        if (!empty($productActivationKey)) {
            $serverIP     = $this->nHelper->getIp('SERVER_ADDR');
            $remoteIP     = $this->nHelper->getIp();
            $serverParams = array(
                'system_ip' => $serverIP,
                'remote_ip' => $remoteIP,
                'api_config_hash' => $productActivationKey,
                'lang' => strtoupper(Shopware()->Locale()->getLanguage())
            );
            $response     = $this->nHelper->curlCallRequest($serverParams, $this->novalnetGatewayUrl['novalnet_auto_api_url']);
            $resultArr    = (array) json_decode($response->getBody());
            if (!empty($resultArr['vendor_id'])) {
                $tariffList  = array();
                $tariff_name = preg_split('/,/', $resultArr['tariff_name']);
                $tariff_id   = preg_split('/,/', $resultArr['tariff_id']);
                $tariff_type = preg_split('/,/', $resultArr['tariff_type']);
                for ($i = 0; $i <= count($tariff_name); $i++) {
                    $j               = $i + 1;
                    $name            = str_replace('tariff_name' . $j . ':', '', $tariff_name[$i]);
                    $type            = str_replace('tariff_type' . $j . ':', '', $tariff_type[$i]);
                    $id              = str_replace('tariff_id' . $j . ':', '', trim($name) . '(' . $tariff_id[$i] . '-' . trim($type) . ')');
                    $tariffList[$id] = $name;
                }
                foreach ($tariffList as $key => $value) {
                    $tariffs[] = array(
                        'id' => $key
                    );
                }
                $this->View()->assign(array(
                    'success' => true,
                    'data' => $tariffs
                ));
            } else {
                $this->View()->assign(array(
                    'success' => true,
                    'data' => array(
                        'id' => $this->novalnetLang['config_description_novalnet_tariff_val']
                    )
                ));
            }
        } else {
            $this->View()->assign(array(
                'success' => true,
                'data' => array(
                    'id' => $this->novalnetLang['config_description_novalnet_tariff_val']
                )
            ));
        }
    }
}
