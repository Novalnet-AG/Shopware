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
////{block name="backend/order/application" append}
{include file="backend/novalnet_order_operations/view/main/window.js"}
{include file="backend/novalnet_order_operations/view/main/trans_confirm.js"}
{include file="backend/novalnet_order_operations/view/main/trans_amount_refund.js"}
{include file="backend/novalnet_order_operations/view/main/trans_amount_update.js"}
{include file="backend/novalnet_order_operations/view/main/zero_amount_booking.js"}
//{/block}
