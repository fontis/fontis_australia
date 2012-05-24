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

class Fontis_Australia_Block_Directdeposit_Info extends Mage_Payment_Block_Info
{

    protected $_accountName;
    protected $_accountBSB;
    protected $_accountNumber;
    protected $_message;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('fontis/australia/payment/directdeposit/info.phtml');
    }

    /**
     * Gets the bank account name as set by the admin.
     *
     * @return string
     */
    public function getAccountName()
    {
        if (is_null($this->_accountName)) {
            $this->_convertAdditionalData();
        }
        return $this->_accountName;
    }

    /**
     * Gets the bank account BSB (bank, state, branch) as set by the admin.
     *
     * @return string
     */
    public function getAccountBSB()
    {
        if (is_null($this->_accountBSB)) {
            $this->_convertAdditionalData();
        }
        return $this->_accountBSB;
    }
    
    /**
     * Gets the bank account number as set by the admin.
     *
     * @return string
     */
    public function getAccountNumber()
    {
        if (is_null($this->_accountNumber)) {
            $this->_convertAdditionalData();
        }
        return $this->_accountNumber;
    }
    
	/**
	 * Gets the bank account number as set by the admin.
	 *
	 * @return string
	 */
	public function getMessage()
	{
		if (is_null($this->_message)) {
			$this->_convertAdditionalData();
		}
		return $this->_message;
	}    

    /**
     * Converts serialised additional data into a more usable form.
     *
     * @return Fontis_Australia_Block_Info_Directdeposit
     */
    protected function _convertAdditionalData()
    {
        $details = @unserialize($this->getInfo()->getAdditionalData());
        if (is_array($details)) {
            $this->_accountName = isset($details['account_name']) ? (string) $details['account_name'] : '';
            $this->_accountBSB = isset($details['account_bsb']) ? (string) $details['account_bsb'] : '';
            $this->_accountNumber = isset($details['account_number']) ? (string) $details['account_number'] : '';
            $this->_message = isset($details['message']) ? (string) $details['message'] : '';
        } else {
            $this->_accountName = '';
            $this->_accountBSB = '';
            $this->_accountNumber = '';
            $this->_message = '';
        }
        return $this;
    }

}
