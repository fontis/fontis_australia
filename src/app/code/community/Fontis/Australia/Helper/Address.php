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
 * @author     Chris Norton
 * @author     Thai Phan
 * @copyright  Copyright (c) 2014 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Helper functions for address validation
 *
 * @category   Fontis
 * @package    Fontis_Australia
 */
class Fontis_Australia_Helper_Address extends Mage_Core_Helper_Abstract
{
    const XML_PATH_ADDRESS_VALIDATION_ENABLED       = 'fontis_australia/address_validation/enabled';
    const XML_PATH_ADDRESS_VALIDATION_BACKEND       = 'fontis_australia/address_validation/backend';
    const XML_PATH_DELIVERY_CHOICES_DEVELOPER_MODE  = 'fontis_australia/address_validation/delivery_choices_developer_mode';
    const XML_PATH_VALIDATE_ORDER_SAVE              = 'fontis_australia/address_validation/validate_order_save';

    // Constants for stored address validation results.
    const ADDRESS_UNKNOWN       = 0;
    const ADDRESS_VALID         = 1;
    const ADDRESS_INVALID       = 2;
    const ADDRESS_OVERRIDE      = 3;

    const ADDRESS_OVERRIDE_FLAG = 'override_address_validation';

    /**
     * Returns an array of address validation status options.
     *
     * Keys are codes, values are labels.
     *
     * @return array Status options
     */
    public function getValidationStatusOptions()
    {
        return array(
            static::ADDRESS_UNKNOWN     => 'Unknown',
            static::ADDRESS_VALID       => 'Valid',
            static::ADDRESS_INVALID     => 'Invalid',
            static::ADDRESS_OVERRIDE    => 'Overridden',
        );
    }

    /**
     * Returns the label for the given validation status code.
     *
     * Defaults to 'Unknown' if the code isn't found.
     *
     * @param int $status Validation status code
     * @return string Status label
     */
    public function getValidationStatusDescription($status)
    {
        $options = $this->getValidationStatusOptions();
        if (!isset($options[$status])) {
            $status = static::ADDRESS_UNKNOWN;
        }

        return $options[$status];
    }

    /**
     * Checks whether checkout address validation is enabled.
     *
     * @return bool
     */
    public function isAddressValidationEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_ADDRESS_VALIDATION_ENABLED);
    }

    /**
     * Gets the selected backend for address validation, e.g. Australia Post
     * Delivery Choices
     *
     * @return string
     */
    public function getAddressValidationBackend()
    {
        return Mage::getStoreConfig(self::XML_PATH_ADDRESS_VALIDATION_BACKEND);
    }

    /**
     * Checks whether developer mode is enabled for Delivery Choices.
     *
     * @return bool
     */
    public function isDeliveryChoicesDeveloperMode()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_DELIVERY_CHOICES_DEVELOPER_MODE);
    }

    /**
     * Checks if validation on order save is enabled.
     *
     * @return bool
     */
    public function isValidationOrderSaveEnabled()
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_VALIDATE_ORDER_SAVE);
    }

    /**
     * Validates an address.
     *
     * Instantiates the selected address validator and passes our arguments to
     * it, as well as doing some error checking.
     *
     * @see Fontis_Australia_Model_Address_Interface
     *
     * @param string[] $street Array of street address lines
     * @param string $state State
     * @param string $suburb City/suburb
     * @param string $postcode Postcode
     * @param string $country Country
     * @return array Array of validated address data
     */
    public function validate(array $street, $state, $suburb, $postcode, $country)
    {
        try {
            $validatorClass = Mage::getStoreConfig(self::XML_PATH_ADDRESS_VALIDATION_BACKEND);
            if (!$validatorClass) {
                Mage::helper('australia')->logMessage('Address validator class not set');
                return array();
            }
            /** @var Fontis_Australia_Model_Address_Interface $validator */
            $validator = Mage::getModel($validatorClass);
            return $validator->validateAddress($street, $state, $suburb, $postcode, $country);
        } catch (Exception $err) {
            $message = "Error validating address\nStreet: " . print_r($street, true) . "\n"
                    . "State: $state\nSuburb: $suburb\nPostcode: $postcode\nCountry: $country\n"
                    . $err->getMessage() . "\n" . $err->getTraceAsString();
            Mage::helper('australia')->logMessage($message);
            return array();
        }
    }

    /**
     * Validates the order's address and updates its 'address valid' value.
     *
     * @param Mage_Sales_Model_Order $order
     * @return array Address validation results
     * @throws Exception
     */
    public function validateOrderAddress(Mage_Sales_Model_Order $order)
    {
        /** @var Mage_Sales_Model_Order_Address $address */
        $address = $order->getShippingAddress();
        $countryModel = $address->getCountryModel();
        $result = $this->validate(
            $address->getStreet(),
            $address->getRegionCode(),
            $address->getCity(),
            $address->getPostcode(),
            $countryModel->getName()
        );

        if (!$result || !isset($result['ValidAustralianAddress'])) {
            $status = static::ADDRESS_UNKNOWN;
        } else if (isset($result['validAddress']) && $result['validAddress']) {
            $status = static::ADDRESS_VALID;
        } else {
            $status = static::ADDRESS_INVALID;
        }

        $order->setAddressValidated($status);

        return $result;
    }

    /**
     * Adds a status message to the session, based on a validation result.
     *
     * Success messages should have a leading space, since the new message is appended to the 'The order address
     * has been updated.' message.
     *
     * @param array $result Address validation result array.
     * @param Mage_Core_Model_Session_Abstract $session Session object
     */
    public function addValidationMessageToSession(array $result, Mage_Core_Model_Session_Abstract $session)
    {
        if (isset($result[Fontis_Australia_Helper_Address::ADDRESS_OVERRIDE_FLAG])) {
            $session->addSuccess(' Address successfully overridden without validation.');
        } else if (!$result || !isset($result['ValidAustralianAddress'])) {
            $session->addWarning('Unable to validate address.');
        } else if (isset($result['validAddress']) && $result['validAddress']) {
            $session->addSuccess(' Address successfully validated.');
        } else if (isset($result['suggestion'])) {
            $session->addWarning("Address is not valid; did you mean: {$result['suggestion']}");
        } else {
            $session->addWarning('Address is not valid; no suggestions available.');
        }
    }
}
