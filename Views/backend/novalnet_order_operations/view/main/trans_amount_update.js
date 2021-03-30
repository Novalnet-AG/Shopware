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
Ext.define('Shopware.apps.NovalnetOrderOperations.view.main.transAmountUpdate', {

    extend:'Ext.form.Panel',

    autoScroll:true,

    stateful:true,

    initComponent: function () {
        var me = this;
        me.paymentdetails = me.getorderpaymentdetails();
        me.paymentmethodName = me.paymentdetails[0];
        me.paymentOrderAmount = me.paymentdetails[1];
        me.paymentOrderDuedate = me.paymentdetails[2];
        if (me.displayDueDateField()) {
            me.items = [ me.createAmountUpdateFieldSet(), me.createDueDateFieldSet(), me.createExternalButon()];
        } else {
            me.items = [ me.createAmountUpdateFieldSet(),me.createExternalButon()];
        }
        if(me.paymentmethodName == 'novalnetcashpayment'){ 
        me.title = '{s name=backend_novalnet_order_operations_cashpayment_amount_date_update_title} Change the amount / slip expiry date  {/s}';
        }
        else if(me.paymentmethodName == 'novalnetinvoice' ||  me.paymentmethodName == 'novalnetprepayment'){
        me.title = '{s name=backend_novalnet_order_operations_amount_date_update_title} Change the amount / due date  {/s}' ;
        } else {
        me.title = '{s name=backend_novalnet_order_operations_amountupdate_title} Change the amount {/s}';
       }
        me.callParent(arguments);
    },
    
    getorderpaymentdetails : function () {
        var success = false;
        Ext.Ajax.request({
            url: '{url controller=NovalnetOrderOperations action=displayPaymentDetails}',
            method:'POST',
            async:false,
            params: {
                orderId: this.record.get('id'),
                number : this.record.get('number')
            },
            success: function (response) {
                var decodedResponse = Ext.decode(response.responseText);
                paymentname = decodedResponse.orderPaymentname;
                orderAmt = decodedResponse.orderAmount;
                orderdue = decodedResponse.orderduedate;
                details = [paymentname,orderAmt,orderdue];
            }
        });
        return details;
    },

    createAmountUpdateFieldSet: function () {
        var me = this;
        var novalnetPaymentOrderAmount = me.paymentOrderAmount;
        return Ext.create('Ext.form.FieldSet', {
            title: '{s name=backend_novalnet_order_operations_amountupdate_title} Change the amount {/s}',
            defaults: {
                labelWidth: 155,
                labelStyle: 'font-weight: 700;'
            },
            layout: 'anchor',
            minWidth:250,
            items: me.createAmountUpdateElements()
        });
    },

    createAmountUpdateElements: function () {
        var me = this;
        me.externalDescriptionContainer = Ext.create('Ext.container.Container', {
            style: 'color: #000000; margin: 0 0 15px 0;',
            html: '{s name=backend_novalnet_order_operations_amountupdate_field_title}Update transaction amount{/s} {s name=novalnet_in_cents}(in minimum unit of currency. E.g. enter 100 which is equal to 1.00){/s}'
        });

        me.externalTextArea = Ext.create('Ext.form.field.Number', {
            name: 'update_amount_field',
            id: 'update_amount_field',
            decimalSeparator : false,
            minValue: 0,
            height: 20,
            anchor: '40%',
            value: me.paymentOrderAmount,
            enableKeyEvents: true,
            listeners:{
                'keyup': function (f, e) {
                        var value = Ext.ComponentQuery.query('#update_amount_field')[0].getValue();
                        var value = String(value).replace(/\D/g,'');
                        Ext.getCmp('update_amount_field').setValue(value);
                }
            },

            // Remove spinner buttons, and arrow key and mouse wheel listeners
            hideTrigger: true,
            keyNavEnabled: false,
            mouseWheelEnabled: false
        });

        return [me.externalDescriptionContainer , me.externalTextArea];
    },

    createDueDateFieldSet :  function () {
        var me = this;
		var displayTitle = (me.paymentmethodName == 'novalnetcashpayment') ? '{s name=backend_novalnet_order_operations_cashpayment_due_date_title}Slip expiry date{/s}':'{s name=backend_novalnet_order_operations_due_date_title}Transaction due date{/s}';

        return Ext.create('Ext.form.FieldSet', {
            title: displayTitle,
            defaults: {
                labelWidth: 155,
                labelStyle: 'font-weight: 700;'
            },
            layout: 'anchor',
            minWidth:250,
            items: me.createDueDateElements()
        });
    },

    createDueDateElements: function () {
        var me = this;
		var displayTitle = (me.paymentmethodName == 'novalnetcashpayment') ? '{s name=backend_novalnet_order_operations_cashpayment_due_date_title}Slip expiry date{/s}':'{s name=backend_novalnet_order_operations_due_date_title}Transaction due date{/s}';
        var novalnetPaymentDueDate = me.paymentOrderDuedate;
        me.externalDueDateContainer = Ext.create('Ext.container.Container', {
            style: 'color: #000000; margin: 0 0 15px 0;',
            html: displayTitle,
        });

        me.externalDueDateField = Ext.create('Ext.form.field.Date', {
            name: 'invoice_due_date',
            id: 'invoice_due_date',
            format: 'Y-m-d',
            value: novalnetPaymentDueDate,
            height: 20,
            anchor: '40%',
            allowBlank: true,
        });

        return [me.externalDueDateContainer , me.externalDueDateField ];
    },

    createExternalButon : function () {
        var me = this;
        return Ext.create('Ext.button.Button', {
            style: 'margin: 8px 0px 0px 8px;',
            cls: 'small primary',
            text: '{s name=novalnet_order_operations_update_button}Update{/s}',
            id : 'amount_date_update_button',
            handler: function () {
                me.amountDateUpdate();
            }
        });
    },

    amountDateUpdate : function () {
        var me = this;
        var amount = Ext.ComponentQuery.query('#update_amount_field')[0].getValue();
        var due_date = ( Ext.getCmp('invoice_due_date')) ? Ext.ComponentQuery.query('#invoice_due_date')[0].getValue() : '';
        var orderCurrentAmount = amount/100;
        var orderCurrentAmount = Ext.util.Format.number(orderCurrentAmount , '0.00');

        var novalnetPaymentOrderAmount = me.paymentOrderAmount/100;
        var novalnetPaymentOrderAmount = Ext.util.Format.number(novalnetPaymentOrderAmount , '0.00');

        var from_date = Ext.util.Format.date(me.paymentOrderDuedate,'d.m.Y');
        var to_date = Ext.util.Format.date(due_date ,'d.m.Y');
        if(me.paymentmethodName == 'novalnetcashpayment'){
	    var amount_date_confirm_info = ('{s name=novalnet_amount_cashpayment_date_update}Are you sure you want to change the order amount / slip expiry date?{/s}');
		}else{
        var amount_date_confirm_info = ((due_date) ? '{s name=novalnet_amount_date_update}Are you sure you want to change the order amount or due date?{/s}' : '{s name=novalnet_amount_update}Are you sure you want to change the order amount?{/s}' );
	    }
        var getvalnn = Ext.ComponentQuery.query('#update_amount_field')[0].getValue();
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
            amount_date_confirm_info ,
            function (btn) {
                if (btn === 'yes') {
                    Ext.Ajax.request({
                        url: '{url controller=NovalnetOrderOperations action=amountUpdate}',
                        method:'POST',
                        async:false,
                        params: {
                            id          : me.record.get('id'),
                            number      : me.record.get('number'),
                            amount      : Ext.ComponentQuery.query('#update_amount_field')[0].getValue(),
                            currency    : me.record.get('currency'),
                            due_date    : Ext.util.Format.date(due_date, 'Y-m-d'),
                            languageIso : me.record.get('languageIso'),
                        },
                        success: function (response) {
                            var messageText = "";
                            var decodedResponse = Ext.decode(response.responseText);
                            messageText =(typeof decodedResponse.message == "object") ? decodedResponse.message['0'] : decodedResponse.message;
                            if (decodedResponse.success) {
                                Ext.getCmp('amount_date_update_button').disable();
                            }
                            Shopware.Msg.createGrowlMessage('', messageText, '{s name=window_title}{/s}');
                            me.record.store.load();
                        }
                    });
                }
            }
        );

    },

    displayDueDateField : function () {
        var success = false;
        Ext.Ajax.request({
            url: '{url controller=NovalnetOrderOperations action=displayDueDateField}',
            method:'POST',
            async:false,
            params: {
                number : this.record.get('number'),
                languageIso : this.record.get('languageIso'),
            },
            success: function (response) {
                var decodedResponse = Ext.decode(response.responseText);
                success = decodedResponse.success;
            }

        });
        return success;
    }
});
//{/block}
