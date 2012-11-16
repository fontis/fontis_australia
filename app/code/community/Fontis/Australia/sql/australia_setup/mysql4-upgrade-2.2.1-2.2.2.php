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
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
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
    "ALTER TABLE `australia_eparcel`" 
    . " ADD `charge_code_individual` VARCHAR( 50 ) NULL DEFAULT NULL,"
    . " ADD `charge_code_business` VARCHAR( 50 ) NULL DEFAULT NULL"
);

$installer->endSetup();
