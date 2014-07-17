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
 * Collection of Australia Post utility functions.
 */
class Fontis_Australia_Helper_Australiapost extends Mage_Core_Helper_Data
{
    const XML_PATH_AUSTRALIA_POST_DEVELOPER_MODE = 'carriers/australiapost/developer_mode';

    /**
     * Returns whether the Australia Post functionality is enabled in the
     * backend.
     *
     * @return bool
     */
    public function isActive()
    {
        return Mage::getStoreConfig('carriers/australiapost/active');
    }

    /**
     * Checks whether developer mode is enabled for the Australia Post shipping
     * rates API.
     *
     * @return bool
     */
    public function isAustraliaPostDeveloperMode()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_AUSTRALIA_POST_DEVELOPER_MODE);
    }

    /**
     * Get the country ID, e.g. "AU" or "US"
     *
     * @return string
     */
    public function getCountryId()
    {
        return Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getData('country_id');
    }

    /**
     * Determines whether "Signature on Delivery" is enabled and available for
     * the current destination. This is a domestic option.
     *
     * @return bool
     */
    public function isSignatureOnDelivery()
    {
        return Fontis_Australia_Model_Shipping_Carrier_Australiapost_Source_Visibility::OPTIONAL == $this->getSignatureOnDelivery() &&
            $this->getCountryId() == 'AU';
    }

    /**
     * Returns the visibility of "Signature of Delivery" shipping methods.
     *
     * @return Fontis_Australia_Model_Shipping_Carrier_Australiapost_Source_Visibility
     */
    public function getSignatureOnDelivery()
    {
        return Mage::getStoreConfig('carriers/australiapost/signature_on_delivery');
    }

    /**
     * Determines whether "Extra Cover" is enabled for the current destination.
     *
     * @return bool
     */
    public function isExtraCover()
    {
        return Fontis_Australia_Model_Shipping_Carrier_Australiapost_Source_Visibility::OPTIONAL == $this->getExtraCover();
    }

    /**
     * Returns the visibility of "Extra Cover" shipping methods.
     *
     * @return Fontis_Australia_Model_Shipping_Carrier_Australiapost_Source_Visibility
     */
    public function getExtraCover()
    {
        return Mage::getStoreConfig('carriers/australiapost/extra_cover');
    }

    /**
     * Determines whether "Pick Up" is enabled and available for
     * the current destination. This is an international option.
     *
     * @return bool
     */
    public function isPickUp()
    {
        return Fontis_Australia_Model_Shipping_Carrier_Australiapost_Source_Visibility::OPTIONAL == $this->getPickUp() &&
            $this->getCountryId() != 'AU';
    }

    /**
     * Returns the visibility of "Extra Cover" shipping methods.
     *
     * @return Fontis_Australia_Model_Shipping_Carrier_Australiapost_Source_Visibility
     */
    public function getPickUp()
    {
        return Mage::getStoreConfig('carriers/australiapost/pick_up');
    }

    /**
     * Get all the simple items in an order.
     *
     * @param Mage_Shipping_Model_Rate_Request $order
     * @return array
     */
    public function getAllSimpleItems(Mage_Shipping_Model_Rate_Request $order)
    {
        $items = array();
        foreach ($order->getAllItems() as $item) {
            if ($item->getProductType() == 'simple') {
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * Get the attribute value for a product, e.g. its length attribute. If the
     * order only has one item and we've set which product attribute we want to
     * to get the attribute value from, use that product attribute. For all
     * other cases, because we can't assume the dimensions of the order, just
     * use the default config setting.
     *
     * @param $order
     * @param $attribute
     * @return string
     */
    public function getAttribute($order, $attribute)
    {
        $items = $this->getAllSimpleItems($order);
        if (count($items) == 1) {
            $attributeCode = Mage::getStoreConfig('carriers/australiapost/' . $attribute . '_attribute');
            if (empty($attributeCode)) {
                return Mage::getStoreConfig('carriers/australiapost/default_' . $attribute);
            }
            $_attribute = $items[0]->getData($attributeCode);
            if (empty($_attribute)) {
                return Mage::getStoreConfig('carriers/australiapost/default_' . $attribute);
            }
            return $_attribute;
        } else {
            return Mage::getStoreConfig('carriers/australiapost/default_' . $attribute);
        }
    }
}
