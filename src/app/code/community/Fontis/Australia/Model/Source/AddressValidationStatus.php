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
 * @copyright  Copyright (c) 2015 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Source model for validation status options.
 *
 * @category   Fontis
 * @package    Fontis_Australia
 */
class Fontis_Australia_Model_Source_AddressValidationStatus
{
    /**
     * Returns an array of address validation status options.
     *
     * @return array
     */
    public function getOptionArray()
    {
        return Mage::helper('australia/address')->getValidationStatusOptions();
    }
}
