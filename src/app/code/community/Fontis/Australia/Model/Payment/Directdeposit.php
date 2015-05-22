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
 * @copyright  Copyright (c) 2014 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Direct deposit payment model.
 * This allows customers to be presented with the owner's bank account details
 * so that the customer may deposit money directly.
 *
 * @category   Fontis
 * @package    Fontis_Australia
 */
class Fontis_Australia_Model_Payment_Directdeposit extends Mage_Payment_Model_Method_Abstract
{
    const CODE = 'directdeposit_au';

    protected $_code  = self::CODE;
    protected $_formBlockType = 'Fontis_Australia_Block_Directdeposit_Form';
    protected $_infoBlockType = 'Fontis_Australia_Block_Directdeposit_Info';

    /**
     * Set to allow the admin to set whether or not payment has been received
     *
     * @var bool
     */
    protected $_canCapture = true;

    /**
     * @param $quote
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        if ($this->getConfigData('active') == 0) {
            return false;
        }

        $groupAccess = $this->getConfigData('customer_group_access');
        $group = $this->getConfigData('customer_group');

        if ($groupAccess == 0) {
            // No restrictions on access
            return true;
        } elseif ($groupAccess == 1) {
            // Only allow customer to access this method if they are part of the
            // specified group
            if ($group == $quote->getCustomerGroupId()) {
                return true;
            }
        } elseif ($groupAccess == 2) {
            // Only allow customer to access this method if they are NOT part
            // of the specified group
            if ($group != $quote->getCustomerGroupId()) {
                return true;
            }
        }

        // Default, restrict access
        return false;
    }

    /**
     * Assign data to info model instance
     *
     * @param mixed $data
     * @return Fontis_Australia_Model_Payment_Directdeposit
     */
    public function assignData($data)
    {
        $storeId = $this->getInfoInstance()->getQuote()->getStoreId();
        $details = array();

        if ($this->getAccountName()) {
            $details['account_name'] = $this->getAccountName($storeId);
        }

        if ($this->getAccountBSB()) {
            $details['account_bsb'] = $this->getAccountBSB($storeId);
        }

        if ($this->getAccountNumber()) {
            $details['account_number'] = $this->getAccountNumber($storeId);
        }

        if ($this->getMessage()) {
            $details['message'] = $this->getMessage($storeId);
        }

        if (!empty($details)) {
            $this->getInfoInstance()->setAdditionalData(serialize($details));
        }

        return $this;
    }

    /**
     * @return string
     */
    public function getAccountName($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getInfoInstance()->getQuote()->getStoreId();
        }

        return Mage::getStoreConfig('payment/directdeposit_au/account_name', $storeId);
    }

    /**
     * @return string
     */
    public function getAccountBSB($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getInfoInstance()->getQuote()->getStoreId();
        }

        return Mage::getStoreConfig('payment/directdeposit_au/account_bsb', $storeId);
    }

    /**
     * @return string
     */
    public function getAccountNumber($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getInfoInstance()->getQuote()->getStoreId();
        }

        return Mage::getStoreConfig('payment/directdeposit_au/account_number', $storeId);
    }

    /**
     * @return string
     */
    public function getMessage($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->getInfoInstance()->getQuote()->getStoreId();
        }

        return Mage::getStoreConfig('payment/directdeposit_au/message', $storeId);
    }
}
