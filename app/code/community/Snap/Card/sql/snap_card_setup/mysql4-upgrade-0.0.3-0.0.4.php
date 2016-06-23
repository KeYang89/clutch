<?php
/**
 * Install data tables
 *
 * @category    Snap
 * @package     Snap_Card
 * @author      alex
 */

/** @var $installer Mage_Sales_Model_Resource_Setup */
$installer = new Mage_Sales_Model_Resource_Setup();

$installer->startSetup();

$installer->run("ALTER TABLE `{$installer->getTable('snap_card/entity')}` ADD `last_modified_at` DATETIME DEFAULT '0000-00-00 00:00:00' AFTER `created_at`");
$installer->run("ALTER TABLE `{$installer->getTable('snap_card/entity')}` CHANGE COLUMN `total` `point_balance` DECIMAL(12,4) NOT NULL DEFAULT '0.0000'");
$installer->run("ALTER TABLE `{$installer->getTable('snap_card/entity')}` CHANGE COLUMN `balance` `cashback` DECIMAL(12,4) NOT NULL DEFAULT '0.0000'");
$installer->run("ALTER TABLE `{$installer->getTable('snap_card/entity')}` ADD `punch_balance` DECIMAL(12,4) NOT NULL DEFAULT '0.0000' AFTER `point_balance`");


$installer->run("DROP TABLE IF EXISTS `snap_giftcard_usage`");

$installer->endSetup();