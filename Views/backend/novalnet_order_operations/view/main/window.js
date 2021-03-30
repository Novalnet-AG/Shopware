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

//{namespace name=backend/order/view/novalnet}
//{block name="backend/order/view/detail/window" append}
Ext.define('Shopware.apps.NovalnetOrderOperations.view.main.Window', {

    override:'Shopware.apps.Order.view.detail.Window',

    initComponent:function () {
        var me = this;
        me.callParent(arguments);
    },

    createTabPanel: function () {
    
        var me = this;
        var tabPanel = me.callParent(arguments);
        if (me.displayOnholdOrdersTab()) {
            tabPanel.add(Ext.create('Shopware.apps.NovalnetOrderOperations.view.main.transConfirm', {
                title:              '{s name=backend_novalnet_order_transaction_confirmation_title}Manage Transaction{/s}',
                id:                 'nnOrderOperationsTabOneOnhold',
                historyStore:       me.historyStore,
                record:             me.record,
                orderStatusStore:   me.orderStatusStore,
                paymentStatusStore: me.paymentStatusStore
            }));
        }
        if (me.displayAmountRefundTab()) {
            tabPanel.add(Ext.create('Shopware.apps.NovalnetOrderOperations.view.main.transAmountRefund', {
                title:              '{s name=backend_novalnet_order_transamountrefund_title}Refund{/s}',
                id:                 'nnOrderOperationsTabAmountRefund',
                historyStore:       me.historyStore,
                record:             me.record,
                orderStatusStore:   me.orderStatusStore,
                paymentStatusStore: me.paymentStatusStore
             }));
        }
    
        if (me.displayAmountUpdateTab()) {
            tabPanel.add(Ext.create('Shopware.apps.NovalnetOrderOperations.view.main.transAmountUpdate', {
                title:              '{s name=backend_novalnet_order_operations_amount_date_update_title}Change the amount / due date{/s}',
                id:                 'nnOrderOperationsTabAmountUpdate',
                historyStore:       me.historyStore,
                record:             me.record,
                orderStatusStore:   me.orderStatusStore,
                paymentStatusStore: me.paymentStatusStore
                }));
        }
        
        if (me.displayZeroAmountBookingTab()) {
            tabPanel.add(Ext.create('Shopware.apps.NovalnetOrderOperations.view.main.displayZeroAmountBooking', {
                title:              '{s name=backend_novalnet_order_zero_amount_sub_title}Book transaction{/s}',
                id:                 'nnOrderOperationsTabZeroAmountBooking',
                historyStore:       me.historyStore,
                record:             me.record,
                orderStatusStore:   me.orderStatusStore,
                paymentStatusStore: me.paymentStatusStore
            }));
        }
        
        return tabPanel;
    },

    displayOnholdOrdersTab:     function () {

        var result = false;
        Ext.Ajax.request({
            url:     '{url controller=NovalnetOrderOperations action=displayOnholdOrdersTab}',
            method:  'POST',
            async:   false,
            params:  {
                number : this.record.get('number')
            },
            success: function (response) {
            
                var decodedResponse = Ext.decode(response.responseText);
                result = decodedResponse.success;
            }
        });
        return result;
    },

    displayAmountRefundTab  : function () {
        var result = false;
        Ext.Ajax.request({
            url:     '{url controller=NovalnetOrderOperations action=displayAmountRefundTab}',
            method:  'POST',
            async:   false,
            params:  {
                number : this.record.get('number'),
                id : this.record.get('id')
            },
            success: function (response) {
            
                var decodedResponse = Ext.decode(response.responseText);
                result = decodedResponse.success;
            }
        });
        return result;
    },

    displayAmountUpdateTab  : function () {
        var result = false;
        Ext.Ajax.request({
            url:     '{url controller=NovalnetOrderOperations action=displayAmountUpdateTab}',
            method:  'POST',
            async:   false,
            params:  {
                number : this.record.get('number'),
                id : this.record.get('id'),
                amount : this.record.get('invoiceAmount')
            },
            success: function (response) {
            
                var decodedResponse = Ext.decode(response.responseText);
                result = decodedResponse.success;
            }
        });
        return result;
    },
    
    displayZeroAmountBookingTab : function () {
        var result = false;
        Ext.Ajax.request({
            url:     '{url controller=NovalnetOrderOperations action=displayZeroAmountBookingTab}',
            method:  'POST',
            async:   false,
            params:  {
                number : this.record.get('number'),
                id : this.record.get('id')
            },
            success: function (response) {
            
                var decodedResponse = Ext.decode(response.responseText);
                result = decodedResponse.success;
            }
        });
        return result;
    },
    
});
//{/block}
