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
    public function autoload()
    {
        require_once(__DIR__ . '/../lib/vendor/autoload.php');
    }

    /**
     * Adds a history entry to orders placed using AU direct deposit or BPay.
     */
    public function addHistory($event)
    {
        $order = $event->getOrder();
        if($order && $order->getPayment()) {
            if($order->getPayment()->getMethod() == 'directdeposit_au') {
                $order->addStatusHistoryComment('Order placed with Direct Deposit')
                    ->setIsCustomerNotified(false);
            } else if($order->getPayment()->getMethod() == 'bpay') {
                $order->addStatusHistoryComment('Order placed with BPay')
                    ->setIsCustomerNotified(false);
            }
        }
    }

    /**
     * The magento.css file is moved into the skin directory in Magento 1.7 so
     * we need to look for it in both the new and old locations for backwards
     * compatibility.
     *
     * @param $observer
     */
    public function addMagentoCss($observer)
    {
        if (Mage::helper('australia/address')->isAddressValidationEnabled()) {
            /** @var Mage_Core_Model_Layout $layout */
            $layout = Mage::getSingleton('core/layout');
            /** @var Mage_Page_Block_Html_Head $head */
            $head = $layout->getBlock('head');
            $skinBaseDir = Mage::getDesign()->getSkinBaseDir(array('_package' => 'base'));
            $magentoCss = 'prototype/windows/themes/magento.css';
            if (file_exists($skinBaseDir . DS . 'lib' . DS . $magentoCss)) {
                $head->addCss('lib' . DS . $magentoCss);
            } else {
                $head->addItem('js_css', $magentoCss);
            }
        }
    }
}
