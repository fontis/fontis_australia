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
 * Direct deposit payment model.
 * This allows customers to be presented with the owner's bank account details
 * so that the customer may deposit money directly.
 *
 * @category   Fontis
 * @package    Fontis_Australia
 */
class Fontis_Australia_Model_Payment_Directdeposit extends Mage_Payment_Model_Method_Abstract
{

    protected $_code  = 'directdeposit_au';
    protected $_formBlockType = 'fontis_australia_block_directdeposit_form';
    protected $_infoBlockType = 'fontis_australia_block_directdeposit_info';

    // Set to allow the admin to set whether or not payment has been received
    protected $_canCapture = true;

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
     * @return  Fontis_Australia_Model_Payment_Directdeposit
     */
    public function assignData($data)
    {
        $details = array();
        if ($this->getAccountName())
        {
            $details['account_name'] = $this->getAccountName();
        }
        if ($this->getAccountBSB()) 
        {
            $details['account_bsb'] = $this->getAccountBSB();
        }
        if ($this->getAccountNumber()) 
        {
            $details['account_number'] = $this->getAccountNumber();
        }
        if ($this->getMessage())
        {
        	$details['message'] = $this->getMessage();
        }
        if (!empty($details)) 
        {
            $this->getInfoInstance()->setAdditionalData(serialize($details));
        }
        return $this;
    }
    
	public function getAccountName()
	{
		return Mage::getStoreConfig('payment/directdeposit_au/account_name', $this->getInfoInstance()->getQuote()->getStoreId());
	}

	public function getAccountBSB()
	{
		return Mage::getStoreConfig('payment/directdeposit_au/account_bsb', $this->getInfoInstance()->getQuote()->getStoreId());
	}

	public function getAccountNumber()
	{
		return Mage::getStoreConfig('payment/directdeposit_au/account_number', $this->getInfoInstance()->getQuote()->getStoreId());
	}
	
	public function getMessage()
	{
		return Mage::getStoreConfig('payment/directdeposit_au/message', $this->getInfoInstance()->getQuote()->getStoreId());
	}

}
