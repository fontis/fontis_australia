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
 * BPAY payment model
 *
 * @category   Fontis
 * @package    Fontis_Australia
 */
class Fontis_Australia_Model_Payment_Bpay extends Mage_Payment_Model_Method_Abstract
{
    protected $_code  = 'bpay';
    protected $_formBlockType = 'fontis_australia_block_bpay_form';
    protected $_infoBlockType = 'fontis_australia_block_bpay_info';
    
    protected $_ref = null;

	public function isAvailable($quote = null)
	{
        if($this->getConfigData('active') == 0)
        {
            return false;
        }
        
		$groupAccess = $this->getConfigData('customer_group_access');
		$group = $this->getConfigData('customer_group');
		
		if($groupAccess == 0)
		{
			// No restrictions on access
			return true;
		}
		elseif($groupAccess == 1)
		{
			// Only allow customer to access this method if they are part of the
			// specified group
			if($group == $quote->getCustomerGroupId())
			{
				return true;
			}
		}
		elseif($groupAccess == 2)
		{
			// Only allow customer to access this method if they are NOT part
			// of the specified group
			if($group != $quote->getCustomerGroupId())
			{
				return true;
			}
		}
		
		// Default, restrict access
		return false;
	}

    /**
     * Assign data to info model instance
     *
     * @param   mixed $data
     * @return  Fontis_Australia_Model_Payment_Method_Bpay
     */
    public function assignData($data)
    {
        $info = $this->getInfoInstance();
        $info->setBillerCode($this->getBillerCode());
        $info->setRef($this->getRef());
        
		$details = array();
		if ($this->getBillerCode())
		{
			$details['biller_code'] = $this->getBillerCode();
			
			if($this->getRef())
			{
				$details['ref'] = $this->getRef();
			}
		}
        if (!empty($details)) 
        {
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
		if($this->_ref)	{
			return $this->_ref;
		} else {
			// Check whether we will be calculating the reference code based on
			// the customer ID or the order ID.
			if($this->getConfigData('calculate_using_customerid')) {
			    $customer_id = Mage::getSingleton('customer/session')->getCustomerId();
        		if($customer_id) {
			        $customer = Mage::getModel('customer/customer')->load($customer_id);
				    $this->_ref = $this->_calculateRef($customer->getIncrementId());
			    } else {
			        $customer_id = Mage::getSingleton('checkout/session')->getQuote()->getCustomerId();
				    if($customer_id) {
				        $customer = Mage::getModel('customer/customer')->load($customer_id);
					    $this->_ref = $this->_calculateRef($customer->getIncrementId());
				    } else {
				        return null;
				    }
				}
			} else {
				$order_id = Mage::getSingleton('checkout/session')->getLastRealOrderId();
				$this->_ref = $this->_calculateRef($order_id);
			}			
			//$this->assignData();
		}		
		return $this->_ref;
	}
	
	public function getMessage()
	{
		return $this->getConfigData('message');
	}
	
	protected function _calculateRef($ref, $seperator = '', $crn_length = 6)
    {
	    $revstr = strrev(intval($ref));
	    $total = 0;
	    for ($i = 0;$i < strlen($revstr); $i++) {

		    if ($i%2 == 0) {
			    $multiplier = 2;
		    }
		    else $multiplier = 1;

		    $sub_total = intval($revstr[$i]) * $multiplier;
		    if ($sub_total >= 10) {
			    $temp = (string) $sub_total;
			    $sub_total = intval($temp[0]) + intval($temp[1]);
		    }
		    $total += $sub_total;
	    }

	    $check_digit = (10 - ($total % 10))%10;
	    $crn = str_pad(ltrim($ref, "0"),$crn_length-1,0,STR_PAD_LEFT) .$seperator. $check_digit;
	    return $crn;
    }
}
