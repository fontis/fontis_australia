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

class Fontis_Australia_Model_Observer
{
    /**
     * Adds a history entry to orders placed using AU direct deposit or BPay.
     *
     * @listen checkout_type_onepage_save_order
     * @param Varien_Event_Observer $event
     */
    public function addHistory($event)
    {
        /** @var $order Mage_Sales_Model_Order */
        $order = $event->getOrder();
        if ($order && $order->getPayment()) {
            if ($order->getPayment()->getMethod() == Fontis_Australia_Model_Payment_Directdeposit::CODE) {
                $order->addStatusHistoryComment('Order placed with Direct Deposit')->setIsCustomerNotified(false);
            } elseif ($order->getPayment()->getMethod() == Fontis_Australia_Model_Payment_Bpay::CODE) {
                $order->addStatusHistoryComment('Order placed with BPay')->setIsCustomerNotified(false);
            }
        }
    }

    /**
     * The magento.css file is moved into the skin directory in Magento 1.7 so
     * we need to look for it in both the new and old locations for backwards
     * compatibility.
     *
     * @listen controller_action_layout_render_before_checkout_onepage_index
     * @param Varien_Event_Observer $observer
     */
    public function addMagentoCss($observer)
    {
        if (!Mage::helper('australia/address')->isAddressValidationEnabled()) {
            return;
        }

        /** @var Mage_Core_Model_Layout $layout */
        $layout = Mage::getSingleton('core/layout');

        /** @var Mage_Page_Block_Html_Head $head */
        $head = $layout->getBlock('head');

        $skinBaseDir = Mage::getDesign()->getSkinBaseDir(array('_package' => 'base'));
        $cssPath = 'prototype/windows/themes/magento.css';

        if (file_exists($skinBaseDir . DS . 'lib' . DS . $cssPath)) {
            $head->addCss('lib' . DS . $cssPath);
        } else {
            $head->addItem('js_css', $cssPath);
        }
    }
}
