CREATE TABLE IF NOT EXISTS s_novalnet_version (
version varchar(10) NOT NULL,
KEY version (version)
) COMMENT='Novalnet version information';

INSERT INTO s_novalnet_version VALUES ('11.2.5');

ALTER TABLE s_novalnet_transaction_detail ADD is_ref_order enum('0','1') DEFAULT '0' COMMENT 'Novalnet reference order';
ALTER TABLE s_novalnet_transaction_detail DROP COLUMN process_key,DROP COLUMN account_holder,DROP COLUMN active;
