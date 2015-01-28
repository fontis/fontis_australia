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
 *
 * @category   Fontis
 * @package    Fontis_Australia
 */
class Fontis_Australia_Helper_Australiapost extends Mage_Core_Helper_Data
{
    const XML_PATH_AUSTRALIA_POST_DEVELOPER_MODE = 'carriers/australiapost/developer_mode';

    /**
     * Determine if a provided config option is optional
     *
     * @param int $value Config value to check
     * @return bool
     */
    protected function isOptional($value)
    {
        return (bool)($value == Fontis_Australia_Model_Shipping_Carrier_Australiapost_Source_Visibility::OPTIONAL);
    }

    /**
     * Determine if the shipping address country is Australia
     *
     * @return bool
     */
    protected function isAustralia()
    {
        return $this->getCountryId() == Fontis_Australia_Helper_Data::AUSTRALIA_COUNTRY_CODE;
    }

    /**
     * Returns whether the Australia Post functionality is enabled in the backend
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
        return $this->isOptional($this->getSignatureOnDelivery()) && $this->isAustralia();
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
        return $this->isOptional($this->getExtraCover());
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
        return $this->isOptional($this->getPickUp()) && $this->isAustralia();
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
     * @param Mage_Shipping_Model_Rate_Request|Mage_Sales_Model_Order $order Order to retrieve items for
     *
     * @return array List of simple products from order
     */
    public function getAllSimpleItems($order)
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
     * other cases just use the default config setting, since we can't assume
     * the dimensions of the order.
     *
     * @param Mage_Shipping_Model_Rate_Request $request Order object
     * @param string $attribute Attribute code
     *
     * @return string Attribute value
     */
    public function getAttribute(Mage_Shipping_Model_Rate_Request $request, $attribute)
    {
        // Check if an appropriate product attribute has been assigned in the backend and, if not,
        // just return the default weight value as later code won't work
        $attributeCode = Mage::getStoreConfig('carriers/australiapost/' . $attribute . '_attribute');
        if (!$attributeCode) {
            return Mage::getStoreConfig('carriers/australiapost/default_' . $attribute);
        }

        $items = $this->getAllSimpleItems($request);
        if (count($items) == 1) {
            $attributeValue = $items[0]->getData($attributeCode);
            if (empty($attributeValue)) {
                return Mage::getStoreConfig('carriers/australiapost/default_' . $attribute);
            }

            return $attributeValue;
        } else {
            return Mage::getStoreConfig('carriers/australiapost/default_' . $attribute);
        }
    }
}
