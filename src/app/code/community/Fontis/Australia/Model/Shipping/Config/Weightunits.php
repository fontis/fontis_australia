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
 * @author     Chris Norton
 * @copyright  Copyright (c) 2008 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Australia Post shipping weight unit selector and conversion values.
 * All conversion rates are to convert into grams, which is the required unit
 * of weight used by the Australia Post DRC.
 *
 * @category   Fontis
 * @package    Fontis_Australia
 */
class Fontis_Australia_Model_Shipping_Config_Weightunits
{
	public function toOptionArray()
	{
		return array(
			array('value' => 1000, 'label' => Mage::helper('adminhtml')->__('Kilograms (kg)')),
			array('value' => 1, 'label' => Mage::helper('adminhtml')->__('Grams (g)')),
			array('value' => 453.59, 'label' => Mage::helper('adminhtml')->__('Pounds (lb)')),
			array('value' => 28.35, 'label' => Mage::helper('adminhtml')->__('Ounces (oz)'))
		);
	}
}
