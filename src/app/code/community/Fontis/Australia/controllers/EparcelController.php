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
 * Controller handling order export requests.
 */
class Fontis_Australia_EparcelController extends Mage_Adminhtml_Controller_Action
{
    const ADMINHTML_SALES_ORDER_INDEX = 'adminhtml/sales_order/index';

    /**
     * Generate and export a CSV file for the given orders.
     */
    public function exportAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $orders = $this->getRequest()->getPost('order_ids', array());

            try {
                // Build the CSV and retrieve its path
                $filePath = Mage::getModel('australia/shipping_carrier_eparcel_export_csv')->exportOrders($orders);

                // Download the file
                $this->_prepareDownloadResponse( basename($filePath), file_get_contents($filePath) );
            } catch (Fontis_Australia_Model_Shipping_Carrier_Eparcel_Export_Exception $e) {
                Mage::getSingleton('core/session')->addError($e->getMessage());

                $this->_redirect(self::ADMINHTML_SALES_ORDER_INDEX);
            }
            catch(Exception $e)
            {
                Mage::getSingleton('core/session')->addError('An error occurred. ' . $e->getMessage());

                $this->_redirect(self::ADMINHTML_SALES_ORDER_INDEX);
            }
        } else {
            $this->_redirect(self::ADMINHTML_SALES_ORDER_INDEX);
        }
    }
}
