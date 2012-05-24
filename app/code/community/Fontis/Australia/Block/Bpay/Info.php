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


class Fontis_Australia_Block_Bpay_Info extends Mage_Payment_Block_Info
{

    protected $_billerCode;
    protected $_ref;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('fontis/australia/payment/bpay/info.phtml');
    }

    /**
     * Gets the bank account name as set by the admin.
     *
     * @return string
     */
    public function getBillerCode()
    {
        if (is_null($this->_billerCode)) {
            $this->_convertAdditionalData();
        }
        return $this->_billerCode;
    }

    /**
     * Gets the ref code for this customer.
     *
     * @return string
     */
    public function getRef()
    {
        if (is_null($this->_ref)) {
            $this->_convertAdditionalData();
        }
        return $this->_ref;
    }    

    /**
     * Gets any additional data saved by the BPAY payment module.
     *
     * @return Fontis_Australia_Block_Bpay_Info
     */
    protected function _convertAdditionalData()
    {
        $details = @unserialize($this->getInfo()->getAdditionalData());
        if (is_array($details)) {
            $this->_billerCode = isset($details['biller_code']) ? (string) $details['biller_code'] : '';
            $this->_ref = isset($details['ref']) ? (string) $details['ref'] : '';
        } else {
            $this->_billerCode = '';
            $this->_ref = '';
        }
        return $this;
    }

}
