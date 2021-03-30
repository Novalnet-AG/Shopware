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

class Shopware_Plugins_Frontend_NovalPayment_lib_classes_CreatePayment
{

    /**
     * Returns the Novalnet Payment Creation Data
     *
     * @return array
     */
    public function createPaymentModuleData()
    {
        $nnPayments		= Shopware_Plugins_Frontend_NovalPayment_Bootstrap::novalnetPayments();
        $componentPayments = array(
            'novalnetinvoice',
            'novalnetprepayment',
            'novalnetcc',
            'novalnetsepa',
            'novalnetpaypal'
        );
        $NovalLang   = Shopware_Plugins_Frontend_NovalPayment_lib_classes_TranslationHelper::novalnetGetLanguage(
            Shopware()->Locale()->getLanguage()
        );
        foreach ($nnPayments as $paymentName) {
            $Description = (in_array(
                $paymentName,
                array(
                    'novalnetpaypal',
                    'novalnetinstant',
                    'novalnetideal',
                    'novalneteps',
                    'novalnetgiropay',
                    'novalnetprzelewy24'
                )
            )) ? $NovalLang['frontend_description_novalnet_redirection'] : ((in_array(
                $paymentName,
                array(
                    'novalnetinvoice',
                    'novalnetprepayment',
                )
            )) ? $NovalLang['frontend_description_novalnetinvoice_prepayment'] :
            $NovalLang['frontend_description_'.$paymentName]);
            $nnPaymentInfo[$paymentName] = array(
                            'name' => $paymentName,
                            'description' => $NovalLang['payment_name_'.$paymentName],
                            'template' => (in_array(
                                $paymentName,
                                $componentPayments
                            ) && $paymentName != 'novalnetprepayment') ? $paymentName.'.tpl' :
                            'novalnetlogo.tpl' ,
                            'class' => (in_array(
                                $paymentName,
                                $componentPayments
                            )) ? $paymentName.'.php' : '',
                            'action' => 'NovalPayment',
                            'active' => 0,
                            'position' => 0,
                            'pluginID' => 'NULL',
                            'additionalDescription' => $Description
                    );
        }
        return $nnPaymentInfo;
    }
}
