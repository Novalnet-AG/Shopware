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

class Shopware_Plugins_Frontend_NovalPayment_lib_classes_NovalnetSql
{

    /**
     * Add tables for Novalnet Operations
     *
     * @param null
     * @return boolean
     */
    public static function novalnetSqlOperations()
    {
        $insertNovalnetTables = true;
        $tableCheck10 = Shopware()->Db()->fetchOne(
            'SELECT COUNT(*) as exists_tbl
            FROM information_schema.tables
            WHERE table_name IN (?)
            AND table_schema = database()',
            array(
                's_novalnet_transaction_detail'
            )
        );
        if ($tableCheck10) {
            $insertNovalnetTables = false;
        }
        //Import Novalnet Package SQL tables
        $sql_file = dirname(__FILE__) . '/sql/db.sql';
        $sql_lines = file_get_contents($sql_file);
        $sql_linesArr = explode(';', $sql_lines);
        foreach ($sql_linesArr as $sql) {
            if (trim($sql) > '') {
                Shopware()->Db()->query($sql);
            }
        }
        // version 10 - 2 update
        $result = Shopware()->Db()->fetchCol('DESC s_novalnet_transaction_detail');
        if (!in_array('lang', $result)) {
            Shopware()->Db()->query(
                'ALTER TABLE s_novalnet_transaction_detail ADD lang varchar(5) COMMENT "Order language"'
            );
        }
        $result = Shopware()->Db()->fetchCol('DESC s_novalnet_subscription_detail');
        if (!in_array('parent_tid', $result)) {
            Shopware()->Db()->query(
                'ALTER TABLE s_novalnet_subscription_detail ADD parent_tid varchar(30) COMMENT
                "Novalnet Parent Transaction Reference ID"'
            );
        }
        $alterVersion = true;
        $tableCheck11 = Shopware()->Db()->fetchOne(
            'SELECT COUNT(*) as exists_tbl
            FROM information_schema.tables
            WHERE table_name IN (?)
            AND table_schema = database()',
            array(
                's_novalnet_version'
            )
        );
        if ($tableCheck11) {
            $alterVersion = false;
        }
        if ($alterVersion) {
            $sqlFile =  dirname(__FILE__) . '/sql/db_version11.sql';
            $sqlLines = file_get_contents($sqlFile);
            $sqlLinesArr = explode(';', $sqlLines);
            foreach ($sqlLinesArr as $sql) {
                if (trim($sql) > '') {
                    Shopware()->Db()->query($sql);
                }
            }
        }
        Shopware()->Db()->query(
            'CREATE TABLE IF NOT EXISTS s_novalnet_tariff (
                id int(11) unsigned AUTO_INCREMENT COMMENT "Auto Increment ID",
                shopid int(11) NOT NULL COMMENT "Merchant sub shopID",
                tariff TEXT DEFAULT NULL COMMENT "Novalnet tariff values",
                PRIMARY KEY (id),
                KEY shopid (shopid)
            )AUTO_INCREMENT=1 COMMENT="Shop tariff information"'
        );
        return true;
    }

    /**
     * To check the model exists
     *
     * @param null
     * @return boolean
     */
    public function novalnetOrderAttributesExist()
    {
        $result = Shopware()->Db()->fetchCol('DESC s_order_attributes');
        if (!in_array('novalnet_payment_paid_amount', $result)) {
            return false;
        }
        return true;
    }
    
    /**
     * To check the subs column exists are not
     *
     * @param null
     * @return boolean
     */
    public function novalnetSubsAttributesExist()
    {
        $result = Shopware()->Db()->fetchCol('DESC s_order_attributes');
        if (!in_array('novalnet_payment_subs_id', $result)) {
            return false;
        }
        return true;
    }
}
