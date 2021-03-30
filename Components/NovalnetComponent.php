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
namespace ShopwarePlugin\PaymentMethods\Components;

use Shopware\Models\Payment\PaymentInstance;

class NovalnetComponent extends GenericPaymentMethod
{
    /**
     * @var object
     */
    private $nHelper;
    
    public function __construct()
    {
        $this->nHelper  = new \Shopware_Plugins_Frontend_NovalPayment_lib_classes_NovalnetHelper();
    }
    /**
     * Creates the Payment Instance for the given order
     * based on the current Payment Method policy.
     *
     * @param integer $orderId
     * @param integer $userId
     * @param integer $paymentId
     * @return null
     */
    public function createPaymentInstance($orderId, $userId, $paymentId)
    {
        $currentPayment = $_SESSION['Shopware']['sOrderVariables']['sPayment']['name'];
        if (!in_array($currentPayment, array('novalnetinvoice','novalnetprepayment','novalnetcashpayment'))) {
            return null;
        }
        $newLine = '<br />';
        $note          = $reference = '';
        $lang          = Shopware()->Locale()->getLanguage();
        $novalnetLang  =
            \Shopware_Plugins_Frontend_NovalPayment_lib_classes_TranslationHelper::novalnetGetLanguage($lang);
        $orderNumber   = Shopware()->Db()->fetchOne('select ordernumber from s_order where id = ?', array($orderId));
        $productID     = Shopware()->Plugins()->Frontend()->NovalPayment()->Config()->novalnet_product;
        if (Shopware()->Session()->novalnet[$currentPayment]['server_response']['tid_status'] != 75) {
            $reference    = $novalnetLang['novalnet_invoice_note_multiple_reference']. $newLine;
            $reference .= $novalnetLang['novalnet_reference1'] . ': BNR-' . trim($productID) . "-$orderNumber" . $newLine;
            $reference .= $novalnetLang['novalnet_reference2'] . ': TID ' . '&nbsp;' . (
                !empty(Shopware()->Session()->novalnet[$currentPayment]['server_response']['tid']) ?
                            Shopware()->Session()->novalnet[$currentPayment]['server_response']['tid'] :
                            Shopware()->Session()->novalnet[$currentPayment]['sPaymentPinTIDNumber']
            ) . $newLine;
        }
        $note = $this->nHelper->setHtmlEntity($reference, 'decode');
        Shopware()->Modules()->Order()->sComment = Shopware()->Session()->sComment .=
            $note . Shopware()->Session()->nnCustomerComment;
        Shopware()->Session()->nnComment        .=  $note;
        return null;
    }
}
