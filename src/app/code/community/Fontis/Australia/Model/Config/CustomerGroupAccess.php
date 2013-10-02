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
 * Listing of possible options for restricting access based on customer groups
 *
 * @category   Fontis
 * @package    Fontis_Australia
 */
class Fontis_Australia_Model_Config_CustomerGroupAccess
{
	public function toOptionArray()
	{
		return array(
			array('value' => 0, 'label' => Mage::helper('adminhtml')->__('No restrictions')),
			array('value' => 1, 'label' => Mage::helper('adminhtml')->__('Only allow group ...')),
			array('value' => 2, 'label' => Mage::helper('adminhtml')->__('Allow all groups except ...'))
		);
	}
}
