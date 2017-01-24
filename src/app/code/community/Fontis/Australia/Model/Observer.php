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

        $skinBaseDir = Mage::getDesign()->getSkinBaseDir(array('_package' => 'base', '_theme' => 'default'));
        $cssPath = 'prototype/windows/themes/magento.css';

        if (file_exists($skinBaseDir . DS . 'lib' . DS . $cssPath)) {
            $head->addCss('lib' . DS . $cssPath);
        } else {
            $head->addItem('js_css', $cssPath);
        }
    }

    /**
     * After order placement, validate the shipping address and store the result.
     *
     * @listen sales_model_service_quote_submit_success
     * @param Varien_Event_Observer $observer
     */
    public function validateCompletedOrderAddress(Varien_Event_Observer $observer)
    {
        /** @var Fontis_Australia_Helper_Address $helper */
        $helper = Mage::helper('australia/address');
        if (!$helper->isValidationOrderSaveEnabled()) {
            return;
        }

        try {
            /** @var Mage_Sales_Model_Order $order */
            $order = $observer->getOrder();
            if (!$order || !$order->getShippingAddress()) {
                return;
            }

            $helper->validateOrderAddress($order);
            $order->save();
        } catch (Exception $err) {
            Mage::logException($err);
        }
    }

    /**
     * After a shipping address is edited, validate it and store the result.
     *
     * @listen controller_action_postdispatch_adminhtml_sales_order_addressSave
     * @param Varien_Event_Observer $observer
     */
    public function validateAdminOrderAddressSave(Varien_Event_Observer $observer)
    {
        /** @var Fontis_Australia_Helper_Address $helper */
        $helper = Mage::helper('australia/address');
        if (!$helper->isValidationOrderSaveEnabled()) {
            return;
        }

        /** @var Mage_Adminhtml_Model_Session $session */
        $session = Mage::getSingleton('adminhtml/session');
        $errors = $session->getMessages()->getErrors();

        if (count($errors) > 0) {
            // We had an error when saving the address, so we'll skip validation
            $session->addWarning('Address validation has been skipped.');
            return;
        }

        try {
            /** @var Mage_Adminhtml_Controller_Action $controller */
            $controller = $observer->getControllerAction();
            if (!$controller) {
                return;
            }

            $addressId = $controller->getRequest()->getParam('address_id');
            /** @var Mage_Sales_Model_Order_Address $address */
            $address = Mage::getModel('sales/order_address')->load($addressId);
            if ($address->getAddressType() !== Mage_Sales_Model_Order_Address::TYPE_SHIPPING) {
                return;
            }
            $order = $address->getOrder();
            if (!$order) {
                return;
            }

            $ignoreValidation = $controller->getRequest()->getParam('override_validation');

            if ($ignoreValidation) {
                $result = array(
                    Fontis_Australia_Helper_Address::ADDRESS_OVERRIDE_FLAG => true
                );
                $order->setAddressValidated(Fontis_Australia_Helper_Address::ADDRESS_OVERRIDE);
            } else {
                // Validate the address and store the result on the order.
                $result = $helper->validateOrderAddress($order);
            }

            $order->save();

            // Use the result to add a message to the session telling the
            // admin about the validation status.
            $helper->addValidationMessageToSession($result, $session);
        } catch (Exception $err) {
            Mage::logException($err);
        }
    }
}
