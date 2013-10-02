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
 * @copyright  Copyright (c) 2009 Fontis Pty. Ltd. (http://www.fontis.com.au)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
 
/**
 * BPAY-specific helper
 */
class Fontis_Australia_Helper_Bpay extends Mage_Core_Helper_Abstract
{
    public function bpayInfoBlock($billerCode, $customerReferenceNumber)
    {
        $acceptCreditCards = Mage::getStoreConfig('payment/bpay/accept_credit_cards');

        $output = '    
        <div id="bpay" style="border: 2px solid rgb(16, 32, 75); margin: 0px; padding: 6px; width: 238px; font-family: Arial,Helvetica,sans-serif; background-color: rgb(255, 255, 255);">
        <div class="bpayLogo" style="border: 2px solid rgb(20, 44, 97); width: 51px; height: 87px; float: left;">
            <img src="' . Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN) . 'frontend/default/default/images/fontis/bpay.png' . '" height="82" width="51">
        </div>
        <div class="customerReferenceBox" style="border: 2px solid rgb(20, 44, 97); padding: 0px 8px; height: 87px; width: 158px; margin-left: 60px; margin-bottom: 4px;">
            <p class="customerReferenceBoxText" style="font-size: 13px; text-transform: capitalize; color: rgb(20, 44, 97); white-space: nowrap; line-height: 22px; font-weight: normal;">
            <b>Biller Code</b>: ' . $billerCode . '<br>
            <b>Ref</b>: ' . $customerReferenceNumber . '<br>
            </p>
        </div>';
        
        if($acceptCreditCards) {
            $output .= '<div>
                <p class="billerTextHeading" style="font-size: 11px; text-transform: capitalize; color: rgb(20, 44, 97); white-space: nowrap; line-height: 20px; font-weight: bold;">Telephone &amp; Internet Banking &mdash; BPAY&reg;</p>
                <p class="billerText" style="font-size: 11px; color: rgb(20, 44, 97);">Contact your bank or financial institution to make this payment from your cheque, savings, debit, credit card or transaction account. More info: www.bpay.com.au</p>
            </div>';
        } else {
            $output .= '<div>
                <p class="billerTextHeading" style="font-size: 11px; text-transform: capitalize; color: rgb(20, 44, 97); white-space: nowrap; line-height: 20px; font-weight: bold;">Telephone &amp; Internet Banking &mdash; BPAY&reg;</p>
                <p class="billerText" style="font-size: 11px; color: rgb(20, 44, 97);">Contact your bank or financial institution to make this payment from your cheque, savings, debit or transaction account. More info: www.bpay.com.au</p>
            </div>';
        }
        
        $output .= '</div>';  
        return $output;  
    }
}
