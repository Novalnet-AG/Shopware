CREATE TABLE IF NOT EXISTS s_novalnet_callback_history (
id int(11) unsigned AUTO_INCREMENT COMMENT 'Auto Increment ID',
`date` datetime COMMENT 'Callback DATE TIME',
payment_type varchar(50) COMMENT 'Callback Payment Type',
status int(8) DEFAULT NULL COMMENT 'Callback Status',
callback_tid bigint(20) COMMENT 'Callback Reference ID',
org_tid bigint(20) COMMENT 'Original Transaction ID',
amount int(11) DEFAULT NULL COMMENT 'Amount in cents',
currency varchar(5) DEFAULT NULL COMMENT 'Currency',
product_id int(11) unsigned DEFAULT NULL COMMENT 'Callback Product ID',
order_no varchar(30) COMMENT 'Order ID from shop',
PRIMARY KEY (id),
KEY order_no (order_no)
)AUTO_INCREMENT=1 COMMENT='Novalnet Callback History';

CREATE TABLE IF NOT EXISTS s_novalnet_transaction_detail (
id int(11) unsigned AUTO_INCREMENT COMMENT 'Auto Increment ID',
tid bigint(20) COMMENT 'Novalnet Transaction Reference ID',
tariff_id int(11) unsigned COMMENT 'Tariff ID',
subs_id int(11) unsigned DEFAULT NULL COMMENT 'Subscription Status',
payment_id int(11) unsigned COMMENT 'Shopware Payment ID',
payment_key int(11) unsigned COMMENT 'Novalnet Payment Key',
payment_type varchar(50) COMMENT 'Executed Payment type of this order',
amount int(11) COMMENT 'Transaction amount',
currency char(5) COMMENT 'Transaction currency',
status int(8) COMMENT 'Novalnet transaction status in respone',
gateway_status int(8) COMMENT 'Novalnet transaction status',
test_mode tinyint(1) unsigned DEFAULT '0' COMMENT 'Transaction test mode status',
customer_id int(11) unsigned DEFAULT NULL COMMENT 'Customer ID from shop',
order_no varchar(30) COMMENT 'Order ID from shop',
date datetime COMMENT 'Transaction Date for reference',
active tinyint(1) unsigned DEFAULT '1' COMMENT 'Status',
process_key varchar(255) DEFAULT NULL COMMENT 'Encrypted process key',
additional_note text COMMENT 'Customer custom comments',
lang varchar(5) COMMENT 'Order language',
account_holder varchar(150) CHARACTER SET utf8 DEFAULT NULL COMMENT 'Customer holder name for reference',
configuration_details TEXT DEFAULT NULL COMMENT 'Configuration Values of repective Order',
PRIMARY KEY (id),
KEY tid (tid),
KEY payment_id (payment_id),
KEY status (status),
KEY order_no (order_no)
)AUTO_INCREMENT=1 COMMENT='Novalnet Transaction History';

CREATE TABLE IF NOT EXISTS s_novalnet_preinvoice_transaction_detail (
id int(11) unsigned AUTO_INCREMENT COMMENT 'Auto Increment ID',
order_no varchar(30) COMMENT 'Order ID from shop ',
tid bigint(20) COMMENT 'Novalnet Transaction Reference ID ',
test_mode tinyint(1) unsigned DEFAULT '0',
account_holder varchar(150) CHARACTER SET utf8 DEFAULT NULL,
account_number varchar(100) DEFAULT NULL,
bank_code varchar(100) DEFAULT NULL,
bank_name varchar(150) DEFAULT NULL,
bank_city varchar(150) DEFAULT NULL,
amount int(11),
currency char(5),
bank_iban varchar(150) DEFAULT NULL,
bank_bic varchar(100) DEFAULT NULL,
due_date date DEFAULT NULL,
date datetime,
PRIMARY KEY (id),
KEY order_no (order_no),
KEY tid (tid)
) AUTO_INCREMENT=1 COMMENT='Novalnet Invoice and Prepayment transaction account History';

CREATE TABLE IF NOT EXISTS s_novalnet_subscription_detail (
id int(11) unsigned AUTO_INCREMENT COMMENT 'Auto increment ID',
order_no varchar(30) COMMENT 'Order ID from shop',
subs_id int(11) unsigned COMMENT 'Subscription ID',
tid bigint(20) COMMENT 'Novalnet Transaction Reference ID',
parent_tid bigint(20) COMMENT 'Novalnet Parent Transaction Reference ID',
signup_date datetime COMMENT 'Subscription signup date',
termination_reason varchar(255) DEFAULT NULL COMMENT 'Subscription termination reason by merchant',
termination_at datetime DEFAULT NULL COMMENT 'Subscription terminated date',
PRIMARY KEY (id),
KEY order_no (order_no)
) AUTO_INCREMENT=1 COMMENT='Novalnet Subscription Transaction History';

CREATE TABLE IF NOT EXISTS s_novalnet_aff_account_detail (
id int(11) unsigned AUTO_INCREMENT,
vendor_id int(11) unsigned,
vendor_authcode varchar(40),
product_id int(11) unsigned,
product_url text,
activation_date datetime,
aff_id int(11) unsigned DEFAULT NULL,
aff_authcode varchar(40) DEFAULT NULL,
aff_accesskey varchar(40) DEFAULT NULL,
PRIMARY KEY (id),
KEY aff_id (aff_id)
) AUTO_INCREMENT = 1 COMMENT='Novalnet merchant / affiliate account information';

CREATE TABLE IF NOT EXISTS s_novalnet_aff_user_detail (
id int(11) AUTO_INCREMENT , 
aff_id int(11) unsigned DEFAULT NULL ,
customer_id varchar(11) DEFAULT NULL ,
aff_order_no varchar(30) DEFAULT NULL ,
PRIMARY KEY (id)
) AUTO_INCREMENT = 1 COMMENT='Novalnet affiliate user information';
