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

/**
 * Controller handling order export requests.
 */
class Fontis_Australia_ClickandsendController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Export Orders to CSV
     *
     * This action exports orders to a CSV file and downloads the file.
     * The orders to be exported depend on the HTTP POST param "order_ids".
     */
    public function exportAction()
    {
        $orders = $this->getRequest()->getPost('order_ids', array());

        try {
            // Build the CSV and retrieve its path
            $filePath = Mage::getModel('australia/shipping_carrier_clickandsend_export_csv')->exportOrders($orders);

            // Download the file
            $this->_prepareDownloadResponse(basename($filePath), file_get_contents($filePath));
        } catch (Fontis_Australia_Model_Shipping_Carrier_Clickandsend_Export_Exception $e) {
            Mage::getSingleton('core/session')->addError($e->getMessage());

            $this->_redirect('adminhtml/sales_order/index');
        } catch (Exception $e) {
            Mage::getSingleton('core/session')->addError('An error occurred. ' . $e->getMessage());

            $this->_redirect('adminhtml/sales_order/index');
        }
    }
}
