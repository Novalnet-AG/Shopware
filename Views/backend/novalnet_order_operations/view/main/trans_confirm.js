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
Ext.define('Shopware.apps.NovalnetOrderOperations.view.main.transConfirm', {

    extend:'Ext.form.Panel',

    autoScroll:true,

    stateful:true,

    initComponent:function () {
        var me = this;
        me.items = [me.createOnholdFieldSet()];
        me.callParent(arguments);
    },

     /**
     * Creates the container for the onhold fields
     * @return Ext.form.FieldSet
     */
    createOnholdFieldSet: function () {
        var me = this;
        return Ext.create('Ext.form.FieldSet', {
            title: '{s name=backend_novalnet_order_transaction_sub_title}Manage Transaction process{/s}',
            defaults: {
                labelWidth: 155,
                labelStyle: 'font-weight: 700;'
            },
            layout: 'anchor',
            minWidth:250,
            items: me.createOnholdElements()
        });
    },

    createOnholdElements: function () {
        var me = this;
        var subscription_stop_types = Ext.create('Ext.data.Store', {
            fields: ['abbr', 'name'],
            data: [{
                "abbr": "100",
                "name": "{s name=backend_novalnet_order_debit_field}Confirm{/s}"
            }, {
                "abbr": "103",
                "name": "{s name=backend_novalnet_order_debit_cancel}Cancel{/s}"
            }]
        });


        me.externalDescriptionContainer = Ext.create('Ext.container.Container', {
            style: 'color: #000000; margin: 0 0 15px 0;',
            html: '{s name=novalnet_text_please_select_status}Please select status{/s}'
        });

        // Create the combo box, attached to the states data store
        me.OnholdOtpions =  Ext.create('Ext.form.ComboBox', {
            id : 'novalnet_onhold_trans',
            name : 'novalnet_onhold_trans',
            store: subscription_stop_types,
            enableKeyEvents: true,
            forceSelection: true,
            queryMode: 'local',
            displayField: 'name',
            valueField: 'abbr',
            emptyText: "{s name=novalnet_select_type}Select{/s}",
            listeners: {
                select: function ( value) {
                }
            }
        });

        me.externalButton = Ext.create('Ext.button.Button', {
            style: 'margin: 8px 0;',
            cls: 'small primary',
            text: '{s name=backend_novalnet_transaction_update_button}Update{/s}',
            id : 'novalnet_onhold_trans_button',
            handler: function () {
                var get_status = Ext.ComponentQuery.query('#novalnet_onhold_trans')[0].getValue();
                var amount_confirm_info = ((get_status == 100) ? '{s name=novalnet_amount_confirm}Are you sure you want to capture the payment?{/s}' : '{s name=novalnet_amount_cancel}Are you sure you want to cancel the payment?{/s}' );
                var getvalnn = Ext.ComponentQuery.query('#novalnet_onhold_trans')[0].getValue();
                if (getvalnn === null) {
                    Shopware.Notification.createStickyGrowlMessage({
                        title: "{s name=window_title}{/s}",
                        text: '{s name=novalnet_text_please_select_status}Please select status{/s}',
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
                                url: '{url controller=NovalnetOrderOperations action=onholdTrans}',
                                method:'POST',
                                async:false,
                                params: {
                                    id          : me.record.get('id'),
                                    languageIso : me.record.get('languageIso'),
                                    number : me.record.get('number'),
                                    status : Ext.ComponentQuery.query('#novalnet_onhold_trans')[0].getValue()
                                },
                                success: function (response) {
                                    var messageText = "";
                                    var decodedResponse = Ext.decode(response.responseText);
                                    messageText =(typeof decodedResponse.message == "object") ? decodedResponse.message['0'] : decodedResponse.message;
                                    if (decodedResponse.success) {
                                        Ext.getCmp('novalnet_onhold_trans_button').disable();
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

        return [me.externalDescriptionContainer, me.OnholdOtpions, me.externalButton];
    }
});
//{/block}
