<?php
/**
 * Natural Candle Supply
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @category   Fontis
 * @package    Fontis_Australia
 * @author     Tomas Dermisek
 * @copyright  Copyright (c) 2014 Natural Candle Supply
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installer = $this;
$installer->startSetup();

/** 
 * 
 * Added stock_id for storing of Warehouse ID 
 * See: Multi Warehouse Extension: http://innoexts.com/promotion/multi-warehouse/
 * Version: 1.2.0.11 
 * 
 */
$installer->run(
"ALTER TABLE {$this->getTable('australia_eparcel')}
 ADD stock_id INT NOT NULL DEFAULT '0' COMMENT 'Warehouse ID for Multi Warehouse extension';"
);

$installer->run("
    ALTER TABLE {$this->getTable('australia_eparcel')} 
    DROP INDEX `dest_country`, 
    ADD UNIQUE `dest_country` ( `website_id` , `dest_country_id` , `dest_region_id` , `dest_zip` , `condition_name` , `condition_to_value` , `delivery_type`, `stock_id`)
");

$installer->endSetup();
