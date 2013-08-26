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
 * Australia Post allowed shipping methods
 *
 * @category   Fontis
 * @package    Fontis_Australia
 */
class Fontis_Australia_Model_Shipping_Config_Shippingmethods
{
	public function toOptionArray()
	{
		$methods = Mage::getModel('Fontis_Australia_Model_Shipping_Carrier_Australiapost')->getAllShippingMethods();
		foreach($methods as $key => $value)
		{
			$shippingMethods[] = array('value' => $key, 'label' => $value);
		}
		
		return $shippingMethods;
	}
}
