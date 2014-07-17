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
 * @author     Thai Phan
 * @copyright  Copyright (c) 2014 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * System configuration backend type for address validation
 */
class Fontis_Australia_Model_Address_Config_Backend
{
    const AUSTRALIA_POST = 'australia/address_australiapost';

    /**
     * Returns an array of the available address validation backends.
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::AUSTRALIA_POST, 'label' => Mage::helper('australia')->__('Australia Post Delivery Choices'))
        );
    }
}
