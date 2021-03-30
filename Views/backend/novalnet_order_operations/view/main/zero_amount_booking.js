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
Ext.define('Shopware.apps.NovalnetOrderOperations.view.main.displayZeroAmountBooking', {

    extend:'Ext.form.Panel',

    autoScroll:true,

    stateful:true,

    initComponent:function () {
        var me = this;
        me.items = [me.createFieldSet()];
        me.callParent(arguments);
    },

     /**
     * Creates the container for the onhold fields
     * @return Ext.form.FieldSet
     */
    createFieldSet: function () {
        var me = this;
        return Ext.create('Ext.form.FieldSet', {
            title: '{s name=backend_novalnet_order_zero_amount_sub_title}Book transaction{/s}',
            defaults: {
                labelWidth: 155,
                labelStyle: 'font-weight: 700;'
            },
            layout: 'anchor',
            minWidth:250,
            items: me.createElements()
        });
    },

    createElements: function () {
        var me = this;

        me.externalDescriptionContainer = Ext.create('Ext.container.Container', {
            style: 'color: #000000; margin: 0 0 15px 0;',
            html: '{s name=backend_novalnet_order_zero_amount_value}Please enter amount{/s} {s name=novalnet_in_cents}(in minimum unit of currency. E.g. enter 100 which is equal to 1.00){/s}'
        });

        // Create the combo box, attached to the states data store
         me.externalTextArea = Ext.create('Ext.form.field.Number', {
                name: 'book_amount_field',
                id: 'book_amount_field',
                decimalSeparator : false,
                minValue: 0,
                height: 20,
                anchor: '40%',
                cols: 1,
                value: me.record.get('invoiceAmount') * 100,
                allowBlank: true,
                grow: true,

            // Remove spinner buttons, and arrow key and mouse wheel listeners
                hideTrigger: true,
                keyNavEnabled: false,
                mouseWheelEnabled: false
            });

            me.externalButton = Ext.create('Ext.button.Button', {
                style: 'margin: 8px 0;',
                cls: 'small primary',
                text: '{s name=backend_novalnet_transaction_update_button}Update{/s}',
                id : 'novalnet_trans_button',
                handler: function () {
                    var amount_confirm_info = '{s name=novalnet_amount_booking_confirm}Are you sure you want to book the order amount?{/s}' ;
                    var getvalnn = Ext.ComponentQuery.query('#book_amount_field')[0].getValue();
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
                        amount_confirm_info ,
                        function (btn) {
                            if (btn === 'yes') {
                                Ext.Ajax.request({
                                    url: '{url controller=NovalnetOrderOperations action=bookAmount}',
                                    method:'POST',
                                    async:false,
                                    params: {
                                        id               : me.record.get('id'),
                                        languageIso      : me.record.get('languageIso'),
                                        number           : me.record.get('number'),
                                        invoiceAmount    : Ext.ComponentQuery.query('#book_amount_field')[0].getValue()
                                    },
                                    success: function (response) {
                                        var messageText = "";
                                        var decodedResponse = Ext.decode(response.responseText);
                                        messageText =(typeof decodedResponse.message == "object") ? decodedResponse.message['0'] : decodedResponse.message;
                                        if (decodedResponse.success) {
                                            Ext.getCmp('novalnet_trans_button').disable();
                                        }
                                        Shopware.Msg.createGrowlMessage('', messageText, '{s name=window_title}{/s}');
                                        me.record.store.load();
                                    }
                                });
                            }
                        }
                    );
                }
            });

            return [me.externalDescriptionContainer, me.externalTextArea, me.externalButton];
    }
});
//{/block}

