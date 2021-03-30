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

Ext.require([
    'Ext.grid.*',
    'Ext.data.*',
    'Ext.panel.*'
]);
//{namespace name=backend/order/view/novalnet}
//{block name="backend/order/view/detail/Novalnet"}
Ext.define('Shopware.apps.NovalnetOrderOperations.view.main.transAmountRefund', {

    extend:'Ext.form.Panel',

    autoScroll:true,

    stateful:true,

    initComponent:function () {
        var me = this;
        me.paymentOrderDetails = me.getorderpaymentdetailsRefund();
        me.orderPaidAmount = me.paymentOrderDetails[0];
        me.paymentOrderAmount = me.paymentOrderDetails[1];
        me.orderHolder = me.paymentOrderDetails[2];
        me.items = [
            me.createPartialRefundFieldSet()
        ];
        me.callParent(arguments);
    },
    
    getorderpaymentdetailsRefund : function () {
        var success = false;
        Ext.Ajax.request({
            url: '{url controller=NovalnetOrderOperations action=displayPaymentDetailsRefund}',
            method:'POST',
            async:false,
            params: {
                orderId: this.record.get('id'),
                number : this.record.get('number')
            },
            success: function (response) {
                var decodedResponse = Ext.decode(response.responseText);
                paidAmount = decodedResponse.orderPaidAmount;
                orderAmt = decodedResponse.orderAmount;
                orderHolder = decodedResponse.orderHolder;
                details = [paidAmount,orderAmt,orderHolder];
            }
        });
        return details;
    },

    /**
     * Creates the container for the partial amount fields
     * @return Ext.form.FieldSet
     */
    createPartialRefundFieldSet: function () {
        var me = this;
        return Ext.create('Ext.form.FieldSet', {
            defaults: {
                labelWidth: 155,
                labelStyle: 'font-weight: 700;'
            },
            layout: 'anchor',
            minWidth:250,
            items: me.createPartialRefundElements()
        });
    },

    /**
     * Creates the elements for the amount field set which is displayed on
     * bottom of the amount refund tab panel.
     * @return array - Contains the description container, the text for the partial amount refund  and the save button.
     */
    createPartialRefundElements: function () {
        var me = this;
        var clearedDate = me.record.get('orderTime');
        var clearedDate = Ext.Date.format(clearedDate, 'd-m-y');
        var currentDate = new Date();
        var currentDate = Ext.Date.format(currentDate, 'd-m-y');

        me.externalDescriptionContainer = Ext.create('Ext.container.Container', {
            style: 'color: #000000; margin: 0 0 15px 0;',
            html: '{s name=backend_novalnet_order_operations_partialrefund_field_title}Please enter the refund amount{/s} {s name=novalnet_in_cents}(in minimum unit of currency. E.g. enter 100 which is equal to 1.00):{/s}'
        });

        me.externalRefundAmountTextArea = Ext.create('Ext.form.field.Number', {
            name: 'nn_partial_refund_amount',
            id : 'nn_partial_refund_amount',
            decimalSeparator : false,
            minValue: 0,
            height: 20,
            anchor: '40%',
            value: me.paymentOrderAmount,
            enableKeyEvents: true,
            listeners:{
                'keyup': function (f, e) {
                        var value = Ext.ComponentQuery.query('#nn_partial_refund_amount')[0].getValue();
                        var value = String(value).replace(/\D/g,'');
                        Ext.getCmp('nn_partial_refund_amount').setValue(value);
                }
            },
            // Remove spinner buttons, and arrow key and mouse wheel listeners
            hideTrigger: true,
            keyNavEnabled: false,
            mouseWheelEnabled: false

        });

        me.externalRefundRefContainer = Ext.create('Ext.container.Container', {
            style: 'color: #000000; margin: 15px 0 15px 0;',
            html: '{s name=backend_novalnet_order_operations_refund_ref_field_title} Refund reference {/s}'
        });
        me.externalRefundRefTextArea = Ext.create('Ext.form.field.Text', {
            name: 'nn_refund_ref',
            id : 'nn_refund_ref',
            height: 20,
            anchor: '40%',
            cols: 1,
            allowBlank: true,
            grow: true,
            maxLength: 50,
            regexText : String,
        });

        me.externalButton = Ext.create('Ext.button.Button', {
            style: 'margin: 8px 0;',
            cls: 'small primary',
            id: 'nn_partial_refund',
            text: '{s name=novalnet_order_operations_update_button}Update{/s}',
            handler: function () {
                me.nn_amount_refund();
            }
        });
        if (clearedDate < currentDate) {
            return [me.externalDescriptionContainer , me.externalRefundAmountTextArea, me.externalRefundRefContainer, me.externalRefundRefTextArea,  me.externalButton];
        } else {
            return [me.externalDescriptionContainer , me.externalRefundAmountTextArea, me.externalButton];
        }
    },


    nn_amount_refund: function () {
        var me = this;
        var amount_refund_confirm_info = '{s name=novalnet_amount_refund_update}Are you sure you want to refund the amount?{/s}' ;
        var getvalnn = Ext.getCmp('nn_partial_refund_amount').getValue();
        if (getvalnn === null || getvalnn == 0) {
            Shopware.Notification.createStickyGrowlMessage({
                title: "{s name=window_title}{/s}",
                text: '{s name=novalnet_amount_invalid}The amount is invalid{/s}',
                width: 440,
                log: false
              });
             return;
        }
        Ext.Msg.confirm(
            "",
            amount_refund_confirm_info ,
            function (btn) {
                if (btn === 'yes') {
                    Ext.Ajax.request({
                        url: '{url controller=NovalnetOrderOperations action=novalnetRefund}',
                        method:'POST',
                        async:false,
                        params: {
                            id : me.record.get('id'),
                            number : me.record.get('number'),
                            cleared : me.record.get('cleared'),
                            languageIso : me.record.get('languageIso'),
                            currency :  me.record.get('currency'),
                            nn_order_paid_amount : me.orderPaidAmount,
                            nn_order_order_amount : me.paymentOrderAmount,
                            nn_partial_refund_amount : Ext.getCmp('nn_partial_refund_amount').getValue()
                        },

                        success: function (response) {
                            var messageText = "";
                            var decodedResponse = Ext.decode(response.responseText);
                            messageText =(typeof decodedResponse.message == "object") ? decodedResponse.message['0'] : decodedResponse.message;
                            if (decodedResponse.success) {
                                 Ext.getCmp('nn_partial_refund').disable();
                            }
                            Shopware.Msg.createGrowlMessage('',messageText, '{s name=window_title}{/s}');
                            me.record.store.load();
                        }
                    });
                }
            }
        );
    }
});
//{/block}
