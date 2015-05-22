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
 * BPAY payment model
 *
 * @category   Fontis
 * @package    Fontis_Australia
 */
class Fontis_Australia_Model_Payment_Bpay extends Mage_Payment_Model_Method_Abstract
{
    const CODE = 'bpay';

    protected $_code  = self::CODE;
    protected $_formBlockType = 'fontis_australia_block_bpay_form';
    protected $_infoBlockType = 'fontis_australia_block_bpay_info';

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
     * Validate.
     *
     * This is just a little hack in order to generate REF from Order ID after Order is created.
     */
    public function validate()
    {
        $order = $this->getInfoInstance()->getOrder();

        if ($order) {
            // Force to generate REF from Order ID.
            $this->assignData(null);
        }

        return parent::validate();
    }

    /**
     * Assign data to info model instance
     *
     * @param mixed $data
     * @return Fontis_Australia_Model_Payment_Bpay
     */
    public function assignData($data)
    {
        $billerCode = $this->getBillerCode();
        $ref        = $this->getRef();
        
        $info = $this->getInfoInstance();
        $info->setBillerCode($billerCode);
        $info->setRef($ref);

        $details = array();

        if ($this->getBillerCode()) {
            $details['biller_code'] = $billerCode;

            if ($this->getRef()) {
                $details['ref'] = $ref;
            }
        }
        
        if (!empty($details)) {
            $this->getInfoInstance()->setAdditionalData(serialize($details));
        }
        
        return $this;
    }

    public function getBillerCode()
    {
        return $this->getConfigData('biller_code');
    }

    public function getRef()
    {
        // Check whether we will be calculating the reference code based on
        // the customer ID or the order ID.
        if ($this->getConfigData('calculate_using_customerid')) {
            $customer_id = Mage::getSingleton('customer/session')->getCustomerId();
            if ($customer_id) {
                $customer = Mage::getModel('customer/customer')->load($customer_id);
                $number = $customer->getIncrementId();
            } else {
                $customer_id = Mage::getSingleton('checkout/session')->getQuote()->getCustomerId();
                if ($customer_id) {
                    $customer = Mage::getModel('customer/customer')->load($customer_id);
                    $number = $customer->getIncrementId();
                } else {
                    return null;
                }
            }
        } else {
            $order = $this->getInfoInstance()->getOrder();

            if ($order) {
                $number = $order->getRealOrderId();
            } else {
                // Don't generate REF without Order - this is for Quote stage.
                return null;
            }
        }

        if ($this->getConfigData('use_mod_10_v_5')) {
            $ref = $this->_calculateRefMod10v5($number);
        } else {
            $ref = $this->_calculateRef($number);
        }

        return $ref;
    }

    public function getMessage()
    {
        return $this->getConfigData('message');
    }

    protected function _calculateRef($ref, $separator = '', $length = 6)
    {
        $revstr = strrev(intval($ref));
        $total = 0;
        for ($i = 0; $i < strlen($revstr); $i++) {

            if ($i % 2 == 0) {
                $multiplier = 2;
            } else {
                $multiplier = 1;
            }

            $subtotal = intval($revstr[$i]) * $multiplier;
            if ($subtotal >= 10) {
                $temp = (string)$subtotal;
                $subtotal = intval($temp[0]) + intval($temp[1]);
            }
            $total += $subtotal;
        }

        $checkDigit = (10 - ($total % 10)) % 10;
        $crn = str_pad(ltrim($ref, "0"), $length - 1, 0, STR_PAD_LEFT) . $separator . $checkDigit;

        return $crn;
    }

    /**
     * Calculate Modulus 10 Version 5.
     *
     * http://stackoverflow.com/a/11605561/747834
     *
     * @param integer $number
     *
     * @return integer
     */
    protected function _calculateRefMod10v5($number)
    {
        $number = preg_replace("/\D/", "", $number);

        // The seed number needs to be numeric
        if (!is_numeric($number)) {
            return false;
        }

        // Must be a positive number
        if ($number <= 0) {
            return false;
        }

        // Get the length of the seed number
        $length = strlen($number);

        $total = 0;

        // For each character in seed number, sum the character multiplied by its one
        // based array position (instead of normal PHP zero based numbering)
        for ($i = 0; $i < $length; $i++) {
            $total += $number{$i} * ($i + 1);
        }

        // The check digit is the result of the sum total from above mod 10
        $checkDigit = fmod($total, 10);

        // Return the original seed plus the check digit
        return $number . $checkDigit;
    }
}
