<?php
/**
 * Fontis Australia Extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * @category   Fontis
 * @package    Fontis_Australia
 * @author     Jonathan Melnick
 * @copyright  Copyright (c) 2012 Doghouse Media Pty Ltd (http://www.dhmedia.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

$installer = $this;
$installer->startSetup();

$installer->run(
    "ALTER TABLE {$this->getTable('australia_eparcel')}"
    . " ADD `charge_code_individual` VARCHAR( 50 ) NULL DEFAULT NULL,"
    . " ADD `charge_code_business` VARCHAR( 50 ) NULL DEFAULT NULL"
);

$installer->run("
ALTER TABLE {$this->getTable('australia_eparcel')} DROP INDEX `dest_country`, ADD UNIQUE `dest_country` ( `website_id` , `dest_country_id` , `dest_region_id` , `dest_zip` , `condition_name` , `condition_to_value` , `delivery_type`)
");

$installer->endSetup();
